<?php

namespace App\Services;

use App\Enums\StockTransactionType;
use App\Models\Customer;
use App\Models\DailyCashRegister;
use App\Models\LoyaltyPointLedger;
use App\Models\PosReturn;
use App\Models\PosReturnItem;
use App\Models\PosSale;
use App\Models\PosSaleItem;
use App\Models\ProductVariant;
use App\Models\StockBalance;
use App\Models\StockLedger;
use App\Models\Store;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;

class PosReturnService
{
    public function __construct(
        protected AuditService $auditService
    ) {}

    /**
     * Process product return or exchange with 7-day policy enforcement.
     */
    public function processReturn(
        PosSale $sale,
        Store $store,
        User $cashier,
        array $itemsToReturn, // array of ['pos_sale_item_id' => X, 'quantity' => Q, 'condition' => 'sellable'|'damaged'|'defective', 'reason' => R]
        string $returnType, // 'refund' or 'exchange'
        string $settlementMethod, // 'cash', 'bkash', 'card', 'exchange'
        bool $isReceiptless = false,
        ?User $managerApprover = null,
        ?PosSale $replacementSale = null,
        ?string $refundReference = null,
        ?string $reason = null
    ): PosReturn {
        // Enforce 7-day return policy
        $withinWindow = $sale->isWithinReturnWindow(7);
        if (!$withinWindow && !$managerApprover) {
            throw new Exception("Sale invoice {$sale->invoice_number} is older than the 7-day return policy. Store Manager approval is mandatory.");
        }

        // Receiptless returns mandate Store Manager approval
        if ($isReceiptless && (!$managerApprover || !$managerApprover->isStoreManager() && !$managerApprover->isSuperAdmin())) {
            throw new Exception("Receiptless return requires authorized Store Manager or Super Admin approval.");
        }

        return DB::transaction(function () use ($sale, $store, $cashier, $itemsToReturn, $returnType, $settlementMethod, $isReceiptless, $managerApprover, $replacementSale, $refundReference, $reason) {
            $returnNumber = SequenceService::generate('RET', $store->code);

            $totalReturnedValue = 0.0;
            $returnLines = [];

            foreach ($itemsToReturn as $itemData) {
                $saleItemId = (int) $itemData['pos_sale_item_id'];
                $qty = (int) $itemData['quantity'];
                $condition = strtolower($itemData['condition'] ?? 'sellable');
                $itemReason = $itemData['reason'] ?? 'Customer Return';

                if ($qty <= 0) {
                    continue;
                }

                $saleItem = PosSaleItem::where('id', $saleItemId)
                    ->where('pos_sale_id', $sale->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($qty > $saleItem->returnableQuantity()) {
                    throw new Exception("Cannot return {$qty} units of {$saleItem->product_name}. Maximum returnable remaining: {$saleItem->returnableQuantity()}.");
                }

                // Calculate net price paid per unit by customer (honoring line discounts and promotions)
                $itemQuantity = max(1, (int) $saleItem->quantity);
                $itemLineTotal = (float) $saleItem->line_total; // net amount charged for this line after discounts
                $effectiveUnitPaid = round($itemLineTotal / $itemQuantity, 2);

                $returnable = $saleItem->returnableQuantity();
                $prevItemRefundTotal = (float) PosReturnItem::where('original_pos_sale_item_id', $saleItem->id)->sum('line_total');
                $totalItemPaid = round($effectiveUnitPaid * $itemQuantity, 2);

                if ($qty === $returnable) {
                    // Full remaining return: ensure exact sum matches net paid without penny rounding drift
                    $lineTotal = max(0.0, round($totalItemPaid - $prevItemRefundTotal, 2));
                    $unitRefund = $qty > 0 ? round($lineTotal / $qty, 2) : 0.0;
                } else {
                    $unitRefund = $effectiveUnitPaid;
                    $lineTotal = round($unitRefund * $qty, 2);
                }

                $totalReturnedValue += $lineTotal;

                $returnLines[] = [
                    'sale_item' => $saleItem,
                    'quantity' => $qty,
                    'unit_price' => $unitRefund,
                    'line_total' => $lineTotal,
                    'condition' => $condition,
                    'reason' => $itemReason,
                ];
            }

            if ($totalReturnedValue <= 0) {
                throw new Exception("No valid items selected for return.");
            }

            // Cap total refund to remaining payable amount on original invoice to strictly prevent over-refunding
            $prevSaleRefundTotal = (float) PosReturn::where('original_pos_sale_id', $sale->id)->sum('total_returned_value');
            $maxRefundPossible = max(0.0, round((float) $sale->total_payable - $prevSaleRefundTotal, 2));
            if ($totalReturnedValue > $maxRefundPossible) {
                $totalReturnedValue = $maxRefundPossible;
            }

            $replacementValue = $replacementSale ? (float) $replacementSale->total_payable : 0.0;
            // Net settlement amount: Negative means cash/refund to customer; positive means customer pays difference
            $netSettlement = round($replacementValue - $totalReturnedValue, 2);

            $posReturn = PosReturn::create([
                'return_number' => $returnNumber,
                'original_pos_sale_id' => $sale->id,
                'replacement_pos_sale_id' => $replacementSale?->id,
                'store_id' => $store->id,
                'customer_id' => $sale->customer_id,
                'processed_by' => $cashier->id,
                'approved_by' => $managerApprover?->id,
                'is_receiptless' => $isReceiptless,
                'return_type' => $returnType,
                'total_returned_value' => $totalReturnedValue,
                'replacement_value' => $replacementValue,
                'net_settlement_amount' => $netSettlement,
                'settlement_method' => $settlementMethod,
                'refund_reference' => $refundReference,
                'reason' => $reason ?? ($returnType === 'exchange' ? 'Customer Exchange' : 'Customer Return & Refund'),
            ]);

            // Process each item and adjust inventory
            foreach ($returnLines as $rl) {
                $saleItem = $rl['sale_item'];
                $qty = $rl['quantity'];
                $condition = $rl['condition'];
                $variantId = $saleItem->product_variant_id;

                PosReturnItem::create([
                    'pos_return_id' => $posReturn->id,
                    'original_pos_sale_item_id' => $saleItem->id,
                    'product_variant_id' => $variantId,
                    'quantity' => $qty,
                    'unit_price' => $rl['unit_price'],
                    'line_total' => $rl['line_total'],
                    'condition' => $condition,
                    'reason_description' => $rl['reason'],
                ]);

                // Update cumulative returned quantity on sale item
                $saleItem->increment('quantity_returned', $qty);

                // Row-lock store stock balance
                $balance = StockBalance::where('store_id', $store->id)
                    ->where('product_variant_id', $variantId)
                    ->lockForUpdate()
                    ->first();

                if (!$balance) {
                    $balance = StockBalance::create([
                        'store_id' => $store->id,
                        'product_variant_id' => $variantId,
                        'quantity_on_hand' => 0,
                        'quantity_reserved' => 0,
                        'damaged_quantity' => 0,
                    ]);
                }

                if ($condition === 'sellable') {
                    // Only sellable returns go back to available sellable inventory
                    $balanceBefore = $balance->quantity_on_hand;
                    $balanceAfter = $balanceBefore + $qty;
                    $balance->increment('quantity_on_hand', $qty);

                    StockLedger::create([
                        'transaction_type' => StockTransactionType::RETURN,
                        'reference_number' => $returnNumber,
                        'store_id' => $store->id,
                        'product_variant_id' => $variantId,
                        'quantity' => $qty,
                        'balance_after' => $balanceAfter,
                        'user_id' => $cashier->id,
                        'notes' => "Return from invoice {$sale->invoice_number} (Condition: Sellable)",
                        'created_at' => now(),
                    ]);
                } else {
                    // Damaged / defective stock goes strictly to quarantine stock
                    $balance->increment('damaged_quantity', $qty);
                }
            }

            // Proportional loyalty points reversal
            if ($sale->customer_id && $sale->loyalty_points_earned > 0 && $sale->total_payable > 0) {
                $customer = Customer::where('id', $sale->customer_id)->lockForUpdate()->first();
                $returnRatio = min(1.0, $totalReturnedValue / $sale->total_payable);
                $pointsToReverse = (int) round($sale->loyalty_points_earned * $returnRatio);

                if ($pointsToReverse > 0 && $customer) {
                    $customer->decrement('loyalty_points', min($customer->loyalty_points, $pointsToReverse));
                    LoyaltyPointLedger::create([
                        'customer_id' => $customer->id,
                        'event_type' => 'reversed',
                        'points' => -$pointsToReverse,
                        'balance_after' => $customer->loyalty_points,
                        'reference_type' => PosReturn::class,
                        'reference_id' => $posReturn->id,
                        'notes' => "Points reversed from return {$returnNumber}",
                        'created_by' => $cashier->id,
                    ]);
                }
            }

            // Update daily cash register if cash refund was issued
            if ($settlementMethod === 'cash' && $netSettlement < 0) {
                $cashRefundAmount = abs($netSettlement);
                $today = now()->format('Y-m-d');
                $register = DailyCashRegister::where('store_id', $store->id)
                    ->where('business_date', $today)
                    ->first();

                if ($register && $register->isOpen()) {
                    $register->increment('cash_refunds_amount', $cashRefundAmount);
                    $register->decrement('cash_in_hand_expected', $cashRefundAmount);
                }
            }

            // Update Sale Status
            $allReturned = true;
            foreach ($sale->items as $item) {
                if ($item->quantity_returned < $item->quantity) {
                    $allReturned = false;
                    break;
                }
            }
            $sale->update(['status' => $allReturned ? 'refunded' : 'partially_refunded']);

            AuditService::log(
                action: 'pos.return_processed',
                auditable: $posReturn,
                oldValues: null,
                newValues: [
                    'return_number' => $returnNumber,
                    'original_invoice' => $sale->invoice_number,
                    'total_returned' => $totalReturnedValue,
                    'return_type' => $returnType
                ],
                reason: "Processed POS return {$returnNumber} for invoice {$sale->invoice_number}"
            );

            return $posReturn;
        });
    }
}
