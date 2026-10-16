<?php

namespace App\Services;

use App\Enums\StockTransactionType;
use App\Models\Customer;
use App\Models\DailyCashRegister;
use App\Models\LoyaltyPointLedger;
use App\Models\PosSale;
use App\Models\PosSaleItem;
use App\Models\PosSalePayment;
use App\Models\ProductVariant;
use App\Models\Promotion;
use App\Models\StockBalance;
use App\Models\StockLedger;
use App\Models\Store;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;

class PosCheckoutService
{
    public function __construct(
        protected AuditService $auditService
    ) {}

    /**
     * Complete POS retail checkout transaction atomically.
     */
    public function checkout(
        Store $store,
        User $cashier,
        array $items, // array of ['product_variant_id' => X, 'quantity' => Y]
        array $payments, // array of ['payment_method' => 'cash'|'bkash'|'card'|..., 'amount' => A, 'tendered_amount' => T, 'reference' => R]
        ?Customer $customer = null,
        ?string $promotionCode = null,
        int $loyaltyPointsToRedeem = 0,
        ?string $idempotencyKey = null,
        ?string $notes = null
    ): PosSale {
        // Idempotency check: Return existing completed sale if identical key provided
        if (!empty($idempotencyKey)) {
            $existing = PosSale::where('idempotency_key', $idempotencyKey)->first();
            if ($existing) {
                return $existing;
            }
        }

        // Daily Register Check: Cash register must be open for the store today
        $today = now()->format('Y-m-d');
        $register = DailyCashRegister::where('store_id', $store->id)
            ->where('business_date', $today)
            ->first();

        if (!$register || !$register->isOpen()) {
            throw new Exception("Daily Cash Register is closed or not opened for store {$store->name} for {$today}. Please open the register before processing sales.");
        }

        return DB::transaction(function () use ($store, $cashier, $items, $payments, $customer, $promotionCode, $loyaltyPointsToRedeem, $idempotencyKey, $notes, $register) {
            // Find active promotion definition if provided
            $promotion = null;
            if (!empty($promotionCode)) {
                $promotion = Promotion::active()->where('code', $promotionCode)->first();
            }

            // Step 1: Re-fetch and row-lock product variants and store stock balances
            $totalSubtotal = 0.0;
            $totalDiscount = 0.0;
            $saleLines = [];

            foreach ($items as $itemData) {
                $variantId = (int) $itemData['product_variant_id'];
                $qty = (int) $itemData['quantity'];

                if ($qty <= 0) {
                    throw new Exception("Item quantity must be greater than zero.");
                }

                // Row-lock variant and balance
                $variant = ProductVariant::with('product.category')
                    ->where('id', $variantId)
                    ->lockForUpdate()
                    ->firstOrFail();

                if (!$variant->is_active || !$variant->product->is_active) {
                    throw new Exception("Product {$variant->full_name} is inactive and cannot be sold.");
                }

                $balance = StockBalance::where('store_id', $store->id)
                    ->where('product_variant_id', $variantId)
                    ->lockForUpdate()
                    ->first();

                $available = $balance ? $balance->available_quantity : 0;
                if ($available < $qty) {
                    throw new Exception("Insufficient stock for {$variant->full_name}. Requested: {$qty}, Available: {$available}. Overselling prevented.");
                }

                // Always take authoritative unit price from database, never from client request
                $unitPrice = (float) $variant->effective_selling_price;
                $lineSubtotal = $unitPrice * $qty;

                // Evaluate promotion discount (non-stacking, single promotion rule)
                $lineDiscount = 0.0;
                if ($promotion && (!$promotion->category_id || $promotion->category_id == $variant->product->category_id)) {
                    $lineDiscount = round($lineSubtotal * ($promotion->discount_percentage / 100), 2);
                }

                $lineTotal = $lineSubtotal - $lineDiscount;

                $totalSubtotal += $lineSubtotal;
                $totalDiscount += $lineDiscount;

                $saleLines[] = [
                    'variant' => $variant,
                    'balance' => $balance,
                    'quantity' => $qty,
                    'unit_price' => $unitPrice,
                    'discount_amount' => $lineDiscount,
                    'line_total' => $lineTotal,
                ];
            }

            // Step 2: Handle Loyalty Points Redemption (1 point = ৳1 BDT)
            $loyaltyDiscount = 0.0;
            $pointsRedeemed = 0;
            if ($customer && $loyaltyPointsToRedeem > 0) {
                // Lock customer record
                $lockedCustomer = Customer::where('id', $customer->id)->lockForUpdate()->first();
                if ($lockedCustomer->loyalty_points < $loyaltyPointsToRedeem) {
                    throw new Exception("Customer only has {$lockedCustomer->loyalty_points} loyalty points available.");
                }

                $maxRedeemable = (int) floor($totalSubtotal - $totalDiscount);
                $pointsRedeemed = min($loyaltyPointsToRedeem, $maxRedeemable);
                $loyaltyDiscount = (float) $pointsRedeemed;
            }

            $postDiscountTaxableAmount = max(0, $totalSubtotal - $totalDiscount - $loyaltyDiscount);

            // Step 3: Tax Calculation (Configurable rate on post-discount amount, standard 5% VAT in Bangladesh fashion retail)
            $taxRate = (float) config('pos.tax_rate', 5.00);
            $taxAmount = round($postDiscountTaxableAmount * ($taxRate / 100), 2);

            // Step 4: Final Total Payable rounded to nearest whole BDT (Standard rounding rule)
            $rawPayable = $postDiscountTaxableAmount + $taxAmount;
            $totalPayable = round($rawPayable);
            $roundingAdjustment = round($totalPayable - $rawPayable, 2);

            // Step 5: Validate Payments (Split payment verification)
            $totalPaid = 0.0;
            $totalCashTendered = 0.0;
            $totalCashChange = 0.0;
            $paymentRecords = [];

            foreach ($payments as $p) {
                $method = strtolower($p['payment_method']);
                $amount = round((float) $p['amount'], 2);
                $tendered = isset($p['tendered_amount']) ? round((float) $p['tendered_amount'], 2) : $amount;

                if ($amount <= 0) {
                    continue;
                }

                $change = 0.0;
                if ($method === 'cash') {
                    if ($tendered < $amount) {
                        throw new Exception("Tendered cash (৳{$tendered}) is less than allocated cash payment (৳{$amount}).");
                    }
                    $change = round($tendered - $amount, 2);
                    $totalCashTendered += $tendered;
                    $totalCashChange += $change;
                }

                $totalPaid += $amount;
                $paymentRecords[] = [
                    'payment_method' => $method,
                    'amount' => $amount,
                    'tendered_amount' => $tendered,
                    'change_amount' => $change,
                    'card_last_four' => $p['card_last_four'] ?? null,
                    'transaction_reference' => $p['reference'] ?? null,
                ];
            }

            if ($totalPaid < $totalPayable) {
                throw new Exception("Total payments (৳{$totalPaid}) are less than total payable (৳{$totalPayable}).");
            }

            // Step 6: Generate Concurrency-Safe Invoice Number
            $invoiceNumber = SequenceService::generate('INV', $store->code);

            // Step 7: Loyalty points earned on purchase (1 point per ৳100 whole spent)
            $pointsEarned = (int) floor($totalPayable / 100);

            // Create POS Sale Header
            $sale = PosSale::create([
                'invoice_number' => $invoiceNumber,
                'store_id' => $store->id,
                'cashier_id' => $cashier->id,
                'customer_id' => $customer?->id,
                'customer_name' => $customer?->name,
                'customer_phone' => $customer?->phone,
                'subtotal_amount' => $totalSubtotal,
                'discount_amount' => $totalDiscount,
                'promotion_id' => $promotion?->id,
                'tax_rate' => $taxRate,
                'tax_amount' => $taxAmount,
                'rounding_adjustment' => $roundingAdjustment,
                'total_payable' => $totalPayable,
                'total_paid' => $totalPaid,
                'change_returned' => $totalCashChange,
                'loyalty_points_earned' => $pointsEarned,
                'loyalty_points_redeemed' => $pointsRedeemed,
                'loyalty_discount_amount' => $loyaltyDiscount,
                'status' => 'completed',
                'idempotency_key' => $idempotencyKey,
                'notes' => $notes,
            ]);

            // Create POS Sale Line Items & Deduct Stock Atomically
            foreach ($saleLines as $line) {
                $variant = $line['variant'];
                $balance = $line['balance'];
                $qty = $line['quantity'];

                PosSaleItem::create([
                    'pos_sale_id' => $sale->id,
                    'product_variant_id' => $variant->id,
                    'barcode' => $variant->barcode,
                    'product_name' => $variant->product->name,
                    'size' => $variant->size,
                    'color' => $variant->color,
                    'quantity' => $qty,
                    'unit_price' => $line['unit_price'],
                    'discount_amount' => $line['discount_amount'],
                    'tax_amount' => 0.00,
                    'line_total' => $line['line_total'],
                ]);

                // Atomically decrement stock
                $balanceBefore = $balance->quantity_on_hand;
                $balanceAfter = $balanceBefore - $qty;
                $balance->decrement('quantity_on_hand', $qty);

                // Insert into immutable stock ledger
                StockLedger::create([
                    'transaction_type' => StockTransactionType::SALE,
                    'reference_number' => $invoiceNumber,
                    'store_id' => $store->id,
                    'product_variant_id' => $variant->id,
                    'quantity' => -$qty,
                    'balance_after' => $balanceAfter,
                    'unit_cost' => $variant->weighted_average_cost ?? $variant->purchase_price,
                    'user_id' => $cashier->id,
                    'notes' => "POS Sale {$invoiceNumber}",
                    'created_at' => now(),
                ]);
            }

            // Create Payment Allocations
            $netCashReceived = 0.0;
            foreach ($paymentRecords as $pr) {
                PosSalePayment::create([
                    'pos_sale_id' => $sale->id,
                    'payment_method' => $pr['payment_method'],
                    'amount' => $pr['amount'],
                    'tendered_amount' => $pr['tendered_amount'],
                    'change_amount' => $pr['change_amount'],
                    'card_last_four' => $pr['card_last_four'],
                    'transaction_reference' => $pr['transaction_reference'],
                    'status' => 'settled',
                    'created_by' => $cashier->id,
                ]);

                if ($pr['payment_method'] === 'cash') {
                    $netCashReceived += $pr['amount'];
                }
            }

            // Step 8: Update Customer Profile & Loyalty Ledger
            if ($customer) {
                $lockedCustomer = Customer::where('id', $customer->id)->lockForUpdate()->first();
                $lockedCustomer->increment('total_spend', $totalPayable);

                if ($pointsRedeemed > 0) {
                    $lockedCustomer->decrement('loyalty_points', $pointsRedeemed);
                    LoyaltyPointLedger::create([
                        'customer_id' => $lockedCustomer->id,
                        'event_type' => 'redeemed',
                        'points' => -$pointsRedeemed,
                        'balance_after' => $lockedCustomer->loyalty_points,
                        'reference_type' => PosSale::class,
                        'reference_id' => $sale->id,
                        'notes' => "Redeemed on invoice {$invoiceNumber}",
                        'created_by' => $cashier->id,
                    ]);
                }

                if ($pointsEarned > 0) {
                    $lockedCustomer->increment('loyalty_points', $pointsEarned);
                    LoyaltyPointLedger::create([
                        'customer_id' => $lockedCustomer->id,
                        'event_type' => 'earned',
                        'points' => $pointsEarned,
                        'balance_after' => $lockedCustomer->loyalty_points,
                        'reference_type' => PosSale::class,
                        'reference_id' => $sale->id,
                        'notes' => "Earned on invoice {$invoiceNumber}",
                        'created_by' => $cashier->id,
                    ]);
                }
            }

            // Step 9: Update Daily Cash Register
            if ($netCashReceived > 0) {
                $register->increment('cash_sales_amount', $netCashReceived);
                $register->increment('cash_in_hand_expected', $netCashReceived);
            }

            AuditService::log(
                action: 'pos.checkout',
                auditable: $sale,
                oldValues: null,
                newValues: ['invoice_number' => $invoiceNumber, 'total_payable' => $totalPayable, 'store_id' => $store->id],
                reason: "POS checkout completed {$invoiceNumber}"
            );

            return $sale;
        });
    }
}
