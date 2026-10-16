<?php

namespace App\Services;

use App\Enums\StockAdjustmentStatus;
use App\Enums\StockTransactionType;
use App\Models\ProductVariant;
use App\Models\StockAdjustment;
use App\Models\StockBalance;
use App\Models\StockLedger;
use App\Models\Store;
use App\Models\User;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    /**
     * Atomically record a stock movement with row locking to prevent race conditions and overselling.
     */
    public static function recordStockMovement(
        StockTransactionType $type,
        int $variantId,
        int $storeId,
        int $quantity, // Positive for addition, negative for deduction
        ?int $relatedStoreId = null,
        float $unitCost = 0.0,
        ?string $referenceNumber = null,
        ?string $notes = null,
        ?User $actor = null
    ): StockLedger {
        $user = $actor ?? Auth::user();
        if (!$user) {
            throw new DomainException('A valid user actor is required for stock movements.');
        }

        return DB::transaction(function () use (
            $type,
            $variantId,
            $storeId,
            $quantity,
            $relatedStoreId,
            $unitCost,
            $referenceNumber,
            $notes,
            $user
        ) {
            // Find or create stock balance row, then lock for update
            $balance = StockBalance::firstOrCreate(
                [
                    'product_variant_id' => $variantId,
                    'store_id' => $storeId,
                ],
                [
                    'quantity_on_hand' => 0,
                    'quantity_reserved' => 0,
                    'reorder_level' => 5,
                ]
            );

            // Row lock
            $balance = StockBalance::where('id', $balance->id)->lockForUpdate()->first();

            $newOnHand = $balance->quantity_on_hand + $quantity;

            // Block overselling / negative stock
            if ($newOnHand < 0) {
                $variant = ProductVariant::with('product')->find($variantId);
                $variantName = $variant ? $variant->fullName : "Variant #{$variantId}";
                throw new DomainException(
                    "Insufficient stock for {$variantName} at Store #{$storeId}. Current stock: {$balance->quantity_on_hand}, Requested deduction: " . abs($quantity)
                );
            }

            // Update balance
            $balance->quantity_on_hand = $newOnHand;
            $balance->save();

            // Create immutable ledger row
            $ledger = StockLedger::create([
                'transaction_type' => $type,
                'product_variant_id' => $variantId,
                'store_id' => $storeId,
                'related_store_id' => $relatedStoreId,
                'quantity' => $quantity,
                'balance_after' => $newOnHand,
                'unit_cost' => $unitCost,
                'reference_number' => $referenceNumber,
                'notes' => $notes,
                'user_id' => $user->id,
                'created_at' => now(),
            ]);

            AuditService::log(
                action: "stock.{$type->value}",
                auditable: $balance,
                oldValues: ['quantity_on_hand' => $balance->getOriginal('quantity_on_hand')],
                newValues: ['quantity_on_hand' => $newOnHand, 'quantity_delta' => $quantity],
                reason: $notes ?? "Stock movement: {$type->value} ({$quantity})",
                actor: $user
            );

            return $ledger;
        });
    }

    /**
     * Set opening stock balance for initial migration.
     */
    public static function setOpeningStock(
        int $variantId,
        int $storeId,
        int $quantity,
        float $unitCost = 0.0,
        ?string $notes = 'Initial opening stock balance',
        ?User $user = null
    ): StockLedger {
        return self::recordStockMovement(
            type: StockTransactionType::OPENING_BALANCE,
            variantId: $variantId,
            storeId: $storeId,
            quantity: $quantity,
            relatedStoreId: null,
            unitCost: $unitCost,
            referenceNumber: 'OPN-' . date('Ymd') . "-{$variantId}",
            notes: $notes,
            actor: $user
        );
    }

    /**
     * Receive stock into Central Warehouse before distribution to retail stores.
     */
    public static function receiveIntoWarehouse(
        int $variantId,
        int $warehouseId,
        int $quantity,
        float $unitCost = 0.0,
        ?string $referenceNumber = null,
        ?string $notes = null,
        ?User $user = null
    ): StockLedger {
        $store = Store::findOrFail($warehouseId);
        if (!$store->is_warehouse) {
            throw new DomainException("Store #{$warehouseId} ({$store->name}) is not designated as a warehouse.");
        }

        $ref = $referenceNumber ?? ('REC-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5)));

        return self::recordStockMovement(
            type: StockTransactionType::WAREHOUSE_RECEIVE,
            variantId: $variantId,
            storeId: $warehouseId,
            quantity: $quantity,
            relatedStoreId: null,
            unitCost: $unitCost,
            referenceNumber: $ref,
            notes: $notes ?? 'Warehouse stock intake',
            actor: $user
        );
    }

    /**
     * Submit a stock adjustment proposal. Does NOT modify stock until approved by Super Admin.
     */
    public static function requestAdjustment(
        int $storeId,
        int $variantId,
        int $proposedQuantity,
        string $reason,
        User $requester
    ): StockAdjustment {
        if (empty(trim($reason))) {
            throw new DomainException('A mandatory reason is required for submitting a stock adjustment.');
        }

        $balance = StockBalance::firstOrCreate(
            ['store_id' => $storeId, 'product_variant_id' => $variantId],
            ['quantity_on_hand' => 0]
        );

        $oldQuantity = $balance->quantity_on_hand;
        $adjustmentQuantity = $proposedQuantity - $oldQuantity;

        $adjustment = StockAdjustment::create([
            'store_id' => $storeId,
            'product_variant_id' => $variantId,
            'old_quantity' => $oldQuantity,
            'proposed_quantity' => $proposedQuantity,
            'adjustment_quantity' => $adjustmentQuantity,
            'reason' => $reason,
            'status' => StockAdjustmentStatus::PENDING,
            'requested_by' => $requester->id,
        ]);

        AuditService::log(
            action: 'stock.adjustment_requested',
            auditable: $adjustment,
            oldValues: ['quantity' => $oldQuantity],
            newValues: ['proposed' => $proposedQuantity, 'diff' => $adjustmentQuantity],
            reason: $reason,
            actor: $requester
        );

        return $adjustment;
    }

    /**
     * Approve stock adjustment. ONLY Super Admin is authorized.
     */
    public static function approveAdjustment(StockAdjustment $adjustment, User $admin): StockLedger
    {
        if (!$admin->isSuperAdmin()) {
            throw new AuthorizationException('Only the Super Admin may approve stock adjustments.');
        }

        if ($adjustment->status !== StockAdjustmentStatus::PENDING) {
            throw new DomainException('Only pending adjustments can be approved.');
        }

        return DB::transaction(function () use ($adjustment, $admin) {
            $balance = StockBalance::where('store_id', $adjustment->store_id)
                ->where('product_variant_id', $adjustment->product_variant_id)
                ->lockForUpdate()
                ->firstOrFail();

            $delta = $adjustment->proposed_quantity - $balance->quantity_on_hand;

            $type = $delta >= 0
                ? StockTransactionType::ADJUSTMENT_INCREASE
                : StockTransactionType::ADJUSTMENT_DECREASE;

            $ledger = self::recordStockMovement(
                type: $type,
                variantId: $adjustment->product_variant_id,
                storeId: $adjustment->store_id,
                quantity: $delta,
                relatedStoreId: null,
                unitCost: 0.0,
                referenceNumber: "ADJ-{$adjustment->id}",
                notes: "Approved adjustment #{$adjustment->id}: {$adjustment->reason}",
                actor: $admin
            );

            $adjustment->update([
                'status' => StockAdjustmentStatus::APPROVED,
                'approved_by' => $admin->id,
                'approved_at' => now(),
            ]);

            AuditService::log(
                action: 'stock.adjustment_approved',
                auditable: $adjustment,
                oldValues: ['status' => 'pending'],
                newValues: ['status' => 'approved', 'approved_by' => $admin->id],
                reason: $adjustment->reason,
                actor: $admin
            );

            return $ledger;
        });
    }

    /**
     * Reject stock adjustment.
     */
    public static function rejectAdjustment(StockAdjustment $adjustment, User $admin, string $rejectionReason): void
    {
        if (!$admin->isSuperAdmin()) {
            throw new AuthorizationException('Only the Super Admin may reject stock adjustments.');
        }

        if ($adjustment->status !== StockAdjustmentStatus::PENDING) {
            throw new DomainException('Only pending adjustments can be rejected.');
        }

        $adjustment->update([
            'status' => StockAdjustmentStatus::REJECTED,
            'approved_by' => $admin->id,
            'rejection_reason' => $rejectionReason,
            'approved_at' => now(),
        ]);

        AuditService::log(
            action: 'stock.adjustment_rejected',
            auditable: $adjustment,
            oldValues: ['status' => 'pending'],
            newValues: ['status' => 'rejected', 'rejection_reason' => $rejectionReason],
            reason: $rejectionReason,
            actor: $admin
        );
    }
}
