<?php

namespace App\Services;

use App\Enums\StockTransactionType;
use App\Models\ProductVariant;
use App\Models\StockBalance;
use App\Models\StockLedger;
use App\Models\Store;
use App\Models\Supplier;
use App\Models\SupplierReturn;
use App\Models\SupplierReturnItem;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;

class SupplierReturnService
{
    public function __construct(
        protected AuditService $auditService
    ) {}

    /**
     * Process return to supplier.
     */
    public function processSupplierReturn(
        Supplier $supplier,
        Store $store,
        array $items, // array of ['product_variant_id' => X, 'quantity' => Q, 'unit_cost' => C, 'condition' => 'damaged'|'excess', 'reason' => R]
        string $settlementType, // 'credit_note', 'cash_refund', 'bank_refund'
        User $user,
        ?string $settlementReference = null,
        ?string $notes = null
    ): SupplierReturn {
        return DB::transaction(function () use ($supplier, $store, $items, $settlementType, $user, $settlementReference, $notes) {
            $returnNumber = SequenceService::generate('SUPRET', $store->code);

            $totalAmount = 0.0;
            foreach ($items as $item) {
                $totalAmount += ((int) $item['quantity']) * ((float) $item['unit_cost']);
            }

            $supplierReturn = SupplierReturn::create([
                'return_number' => $returnNumber,
                'supplier_id' => $supplier->id,
                'store_id' => $store->id,
                'status' => 'completed',
                'total_amount' => $totalAmount,
                'settlement_type' => $settlementType,
                'settlement_reference' => $settlementReference,
                'reason' => $notes,
                'created_by' => $user->id,
                'actioned_by' => $user->id,
                'actioned_at' => now(),
            ]);

            foreach ($items as $item) {
                $variantId = (int) $item['product_variant_id'];
                $qty = (int) $item['quantity'];
                $unitCost = (float) $item['unit_cost'];
                $condition = strtolower($item['condition'] ?? 'damaged');

                SupplierReturnItem::create([
                    'supplier_return_id' => $supplierReturn->id,
                    'product_variant_id' => $variantId,
                    'quantity' => $qty,
                    'unit_cost' => $unitCost,
                    'condition' => $condition,
                    'item_reason' => $item['reason'] ?? null,
                ]);

                // Reduce inventory from store
                $balance = StockBalance::where('store_id', $store->id)
                    ->where('product_variant_id', $variantId)
                    ->lockForUpdate()
                    ->first();

                if (!$balance) {
                    throw new Exception("No stock balance found for variant ID {$variantId} at store {$store->name}.");
                }

                if ($condition === 'damaged') {
                    if ($balance->damaged_quantity < $qty) {
                        throw new Exception("Insufficient damaged quarantine stock to return to supplier. Required: {$qty}, Available: {$balance->damaged_quantity}.");
                    }
                    $balance->decrement('damaged_quantity', $qty);
                } else {
                    if ($balance->available_quantity < $qty) {
                        throw new Exception("Insufficient sellable stock to return to supplier. Required: {$qty}, Available: {$balance->available_quantity}.");
                    }
                    $balanceBefore = $balance->quantity_on_hand;
                    $balanceAfter = $balanceBefore - $qty;
                    $balance->decrement('quantity_on_hand', $qty);

                    StockLedger::create([
                        'transaction_type' => StockTransactionType::RETURN_SUPPLIER,
                        'reference_number' => $returnNumber,
                        'store_id' => $store->id,
                        'product_variant_id' => $variantId,
                        'quantity' => -$qty,
                        'balance_after' => $balanceAfter,
                        'unit_cost' => $unitCost,
                        'user_id' => $user->id,
                        'notes' => "Supplier return {$returnNumber} to {$supplier->name}",
                        'created_at' => now(),
                    ]);
                }
            }

            // Adjust supplier liability
            if ($settlementType === 'credit_note') {
                $supplier->decrement('outstanding_balance', $totalAmount);
            }

            AuditService::log(
                action: 'supplier.return_processed',
                auditable: $supplierReturn,
                oldValues: null,
                newValues: ['return_number' => $returnNumber, 'total' => $totalAmount, 'supplier' => $supplier->name],
                reason: "Processed supplier return {$returnNumber}"
            );

            return $supplierReturn;
        });
    }
}
