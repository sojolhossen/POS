<?php

namespace App\Services;

use App\Enums\StockTransactionType;
use App\Models\ProductVariant;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseReceiving;
use App\Models\PurchaseReceivingItem;
use App\Models\StockBalance;
use App\Models\StockLedger;
use App\Models\Store;
use App\Models\Supplier;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;

class PurchaseService
{
    public function __construct(
        protected LandedCostService $landedCostService,
        protected AuditService $auditService
    ) {}

    /**
     * Create a new Purchase Order in Draft or Submitted status.
     */
    public function createPurchaseOrder(
        Supplier $supplier,
        Store $destinationStore,
        array $items,
        float $additionalCosts,
        User $user,
        ?string $notes = null
    ): PurchaseOrder {
        if (!$supplier->is_active) {
            throw new Exception("Cannot create Purchase Order for an inactive supplier.");
        }

        return DB::transaction(function () use ($supplier, $destinationStore, $items, $additionalCosts, $user, $notes) {
            $poNumber = SequenceService::generate('PO', $destinationStore->code);

            $totalAmount = 0.0;
            foreach ($items as $item) {
                $totalAmount += ((int) $item['quantity']) * ((float) $item['unit_cost']);
            }

            $po = PurchaseOrder::create([
                'po_number' => $poNumber,
                'supplier_id' => $supplier->id,
                'destination_store_id' => $destinationStore->id,
                'status' => 'submitted',
                'total_amount' => $totalAmount,
                'additional_costs' => $additionalCosts,
                'notes' => $notes,
                'created_by' => $user->id,
            ]);

            foreach ($items as $item) {
                PurchaseOrderItem::create([
                    'purchase_order_id' => $po->id,
                    'product_variant_id' => $item['product_variant_id'],
                    'ordered_quantity' => (int) $item['quantity'],
                    'unit_cost' => (float) $item['unit_cost'],
                    'landed_unit_cost' => (float) $item['unit_cost'],
                ]);
            }

            AuditService::log(
                action: 'purchase_order.created',
                auditable: $po,
                oldValues: null,
                newValues: ['po_number' => $poNumber, 'total' => $totalAmount],
                reason: "Created Purchase Order {$poNumber}"
            );

            return $po;
        });
    }

    /**
     * Approve a Purchase Order (Super Admin only).
     */
    public function approvePurchaseOrder(PurchaseOrder $po, User $user): PurchaseOrder
    {
        if (!$user->isSuperAdmin()) {
            throw new Exception("Only the Super Admin can approve Purchase Orders.");
        }

        if (!$po->canBeApproved()) {
            throw new Exception("Purchase Order {$po->po_number} is already {$po->status}.");
        }

        return DB::transaction(function () use ($po, $user) {
            $oldStatus = $po->status;
            $po->update([
                'status' => 'approved',
                'approved_by' => $user->id,
                'approved_at' => now(),
            ]);

            AuditService::log(
                action: 'purchase_order.approved',
                auditable: $po,
                oldValues: ['status' => $oldStatus],
                newValues: ['status' => 'approved'],
                reason: "Approved Purchase Order {$po->po_number}"
            );

            return $po;
        });
    }

    /**
     * Process receiving against an approved Purchase Order.
     * Supports partial receiving, shortages, excesses, and damaged stock.
     */
    public function receiveStock(
        PurchaseOrder $po,
        Store $receivingStore,
        array $receivingItems,
        float $actualAdditionalCosts,
        User $user,
        ?string $billNumber = null,
        ?string $notes = null
    ): PurchaseReceiving {
        if (!$po->isApproved()) {
            throw new Exception("Cannot receive stock: Purchase Order {$po->po_number} has not been approved by the Super Admin.");
        }

        return DB::transaction(function () use ($po, $receivingStore, $receivingItems, $actualAdditionalCosts, $user, $billNumber, $notes) {
            $receivingNumber = SequenceService::generate('RCV', $receivingStore->code);

            // Pre-calculate landed costs proportionally
            $itemsForCosting = [];
            foreach ($receivingItems as $line) {
                $itemsForCosting[] = [
                    'product_variant_id' => $line['product_variant_id'],
                    'quantity_accepted' => (int) $line['quantity_accepted'],
                    'unit_cost' => (float) $line['unit_cost'],
                ];
            }

            $costedLines = $this->landedCostService->calculateLineLandedCosts($itemsForCosting, $actualAdditionalCosts);
            $costMap = [];
            foreach ($costedLines as $cl) {
                $costMap[$cl['product_variant_id']] = $cl;
            }

            $totalBillAmount = 0.0;
            foreach ($receivingItems as $line) {
                $totalBillAmount += ((int) $line['quantity_accepted']) * ((float) $line['unit_cost']);
            }

            // Create Receiving Record
            $receiving = PurchaseReceiving::create([
                'receiving_number' => $receivingNumber,
                'purchase_order_id' => $po->id,
                'supplier_id' => $po->supplier_id,
                'store_id' => $receivingStore->id,
                'supplier_bill_number' => $billNumber,
                'bill_amount' => $totalBillAmount,
                'additional_costs' => $actualAdditionalCosts,
                'notes' => $notes,
                'received_by' => $user->id,
                'received_at' => now(),
            ]);

            // Process each received variant line
            foreach ($receivingItems as $line) {
                $poItem = PurchaseOrderItem::where('purchase_order_id', $po->id)
                    ->where('product_variant_id', $line['product_variant_id'])
                    ->firstOrFail();

                $qtyReceived = (int) $line['quantity_received'];
                $qtyAccepted = (int) $line['quantity_accepted'];
                $qtyDamaged = (int) ($line['quantity_damaged'] ?? 0);
                $qtyShortage = max(0, $poItem->ordered_quantity - ($poItem->accepted_quantity + $qtyAccepted));
                $qtyExcess = max(0, ($poItem->accepted_quantity + $qtyAccepted) - $poItem->ordered_quantity);

                $costInfo = $costMap[$line['product_variant_id']];
                $landedUnitCost = $costInfo['landed_unit_cost'];
                $allocatedAdditional = $costInfo['allocated_additional_cost'];

                // Update PO Item cumulative statistics
                $poItem->increment('received_quantity', $qtyReceived);
                $poItem->increment('accepted_quantity', $qtyAccepted);
                $poItem->increment('damaged_quantity', $qtyDamaged);
                $poItem->update([
                    'shortage_quantity' => $qtyShortage,
                    'excess_quantity' => $qtyExcess,
                    'landed_unit_cost' => $landedUnitCost,
                ]);

                // Record line in PurchaseReceivingItem
                PurchaseReceivingItem::create([
                    'purchase_receiving_id' => $receiving->id,
                    'purchase_order_item_id' => $poItem->id,
                    'product_variant_id' => $poItem->product_variant_id,
                    'scanned_barcode' => $line['scanned_barcode'] ?? null,
                    'quantity_received' => $qtyReceived,
                    'quantity_accepted' => $qtyAccepted,
                    'quantity_shortage' => $qtyShortage,
                    'quantity_excess' => $qtyExcess,
                    'quantity_damaged' => $qtyDamaged,
                    'unit_cost' => (float) $line['unit_cost'],
                    'allocated_additional_cost' => $allocatedAdditional,
                    'landed_unit_cost' => $landedUnitCost,
                    'notes' => $line['notes'] ?? null,
                ]);

                // Update variant WAC
                $variant = ProductVariant::findOrFail($poItem->product_variant_id);
                if ($qtyAccepted > 0) {
                    $this->landedCostService->updateWeightedAverageCost($variant, $qtyAccepted, $landedUnitCost);
                }

                // Row-lock and update inventory in stock_balances for receivingStore
                $balance = StockBalance::where('store_id', $receivingStore->id)
                    ->where('product_variant_id', $variant->id)
                    ->lockForUpdate()
                    ->first();

                if (!$balance) {
                    $balance = StockBalance::create([
                        'store_id' => $receivingStore->id,
                        'product_variant_id' => $variant->id,
                        'quantity_on_hand' => 0,
                        'quantity_reserved' => 0,
                        'damaged_quantity' => 0,
                    ]);
                }

                $balanceBefore = $balance->quantity_on_hand;
                $balanceAfter = $balanceBefore + $qtyAccepted;

                // Only accepted quantity is added to sellable stock
                $balance->increment('quantity_on_hand', $qtyAccepted);

                // Damaged quantity is recorded separately
                if ($qtyDamaged > 0) {
                    $balance->increment('damaged_quantity', $qtyDamaged);
                }

                // Immutable Stock Ledger Entry
                if ($qtyAccepted > 0) {
                    StockLedger::create([
                        'transaction_type' => StockTransactionType::WAREHOUSE_RECEIVE,
                        'reference_number' => $receivingNumber,
                        'store_id' => $receivingStore->id,
                        'product_variant_id' => $variant->id,
                        'quantity' => $qtyAccepted,
                        'balance_after' => $balanceAfter,
                        'unit_cost' => $landedUnitCost,
                        'user_id' => $user->id,
                        'notes' => "Received from PO {$po->po_number} (Supplier: {$po->supplier->name})",
                    ]);
                }
            }

            // Update Supplier Liability Balance
            $po->supplier->increment('outstanding_balance', $totalBillAmount);

            // Determine if PO is fully received or partially received
            $allCompleted = true;
            foreach ($po->items()->get() as $item) {
                if ($item->received_quantity < $item->ordered_quantity) {
                    $allCompleted = false;
                    break;
                }
            }

            $newPoStatus = $allCompleted ? 'completed' : 'partially_received';
            $po->update([
                'status' => $newPoStatus,
                'completed_at' => $allCompleted ? now() : null,
            ]);

            AuditService::log(
                action: 'purchase_order.received',
                auditable: $receiving,
                oldValues: null,
                newValues: [
                    'po_number' => $po->po_number,
                    'receiving_number' => $receivingNumber,
                    'bill_amount' => $totalBillAmount,
                    'status' => $newPoStatus
                ],
                reason: "Stock received against PO {$po->po_number}"
            );

            return $receiving;
        });
    }
}
