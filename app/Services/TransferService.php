<?php

namespace App\Services;

use App\Enums\StockTransactionType;
use App\Models\ProductVariant;
use App\Models\StockBalance;
use App\Models\StockLedger;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\Store;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;

class TransferService
{
    public function __construct(
        protected AuditService $auditService
    ) {}

    /**
     * Store Manager or Admin requests stock transfer.
     */
    public function requestTransfer(
        Store $sourceStore,
        Store $destinationStore,
        array $items,
        User $user,
        ?string $notes = null
    ): StockTransfer {
        if ($sourceStore->id === $destinationStore->id) {
            throw new Exception("Source and destination stores cannot be the same.");
        }

        return DB::transaction(function () use ($sourceStore, $destinationStore, $items, $user, $notes) {
            $referenceNumber = SequenceService::generate('TR', $sourceStore->code);

            $transfer = StockTransfer::create([
                'reference_number' => $referenceNumber,
                'source_store_id' => $sourceStore->id,
                'destination_store_id' => $destinationStore->id,
                'status' => 'requested',
                'notes' => $notes,
                'created_by' => $user->id,
            ]);

            foreach ($items as $item) {
                StockTransferItem::create([
                    'stock_transfer_id' => $transfer->id,
                    'product_variant_id' => $item['product_variant_id'],
                    'quantity_requested' => (int) $item['quantity'],
                    'quantity_dispatched' => 0,
                    'quantity_received' => 0,
                    'quantity_discrepancy' => 0,
                ]);
            }

            AuditService::log(
                action: 'transfer.requested',
                auditable: $transfer,
                oldValues: null,
                newValues: ['reference' => $referenceNumber, 'source' => $sourceStore->code, 'destination' => $destinationStore->code],
                reason: "Transfer requested {$referenceNumber}"
            );

            return $transfer;
        });
    }

    /**
     * Authorize / Approve a transfer request.
     */
    public function approveTransfer(StockTransfer $transfer, User $user): StockTransfer
    {
        if (!$user->isSuperAdmin()) {
            throw new Exception("Unauthorized! Only Super Admin can approve stock transfers.");
        }

        if (!in_array($transfer->status, ['draft', 'requested'])) {
            throw new Exception("Transfer {$transfer->reference_number} cannot be approved in status {$transfer->status}.");
        }

        return DB::transaction(function () use ($transfer, $user) {
            $oldStatus = $transfer->status;
            $transfer->update([
                'status' => 'approved',
                'approved_by' => $user->id,
                'approved_at' => now(),
            ]);

            AuditService::log(
                action: 'transfer.approved',
                auditable: $transfer,
                oldValues: ['status' => $oldStatus],
                newValues: ['status' => 'approved', 'approved_by' => $user->name],
                reason: "Stock transfer approved by Super Admin ({$user->name})"
            );

            return $transfer;
        });
    }

    /**
     * Warehouse Staff or Manager dispatches stock.
     * Decreases source store available stock immediately.
     */
    public function dispatchTransfer(
        StockTransfer $transfer,
        array $dispatchedQuantities,
        User $user
    ): StockTransfer {
        if (!in_array($transfer->status, ['approved', 'requested'])) {
            throw new Exception("Transfer {$transfer->reference_number} must be approved before dispatch.");
        }

        return DB::transaction(function () use ($transfer, $dispatchedQuantities, $user) {
            foreach ($dispatchedQuantities as $key => $qty) {
                $qty = (int) $qty;
                if ($qty <= 0) {
                    continue;
                }

                $item = StockTransferItem::where('stock_transfer_id', $transfer->id)
                    ->where(function ($q) use ($key) {
                        $q->where('product_variant_id', $key)
                          ->orWhere('id', $key);
                    })
                    ->firstOrFail();

                $variantId = $item->product_variant_id;

                // Row-lock source store stock
                $sourceBalance = StockBalance::where('store_id', $transfer->source_store_id)
                    ->where('product_variant_id', $variantId)
                    ->lockForUpdate()
                    ->first();

                if (!$sourceBalance || $sourceBalance->quantity_on_hand < $qty) {
                    $avail = $sourceBalance ? $sourceBalance->quantity_on_hand : 0;
                    throw new Exception("Insufficient stock at source store to dispatch variant ID {$variantId}. Required: {$qty}, Available: {$avail}.");
                }

                $balanceBefore = $sourceBalance->quantity_on_hand;
                $balanceAfter = $balanceBefore - $qty;

                // Decrement source inventory
                $sourceBalance->decrement('quantity_on_hand', $qty);
                $item->update(['quantity_dispatched' => $qty]);

                // Write immutable stock ledger entry
                StockLedger::create([
                    'transaction_type' => StockTransactionType::TRANSFER_DISPATCH,
                    'reference_number' => $transfer->reference_number,
                    'store_id' => $transfer->source_store_id,
                    'related_store_id' => $transfer->destination_store_id,
                    'product_variant_id' => $variantId,
                    'quantity' => -$qty,
                    'balance_after' => $balanceAfter,
                    'user_id' => $user->id,
                    'notes' => "Dispatched for transfer {$transfer->reference_number}",
                    'created_at' => now(),
                ]);
            }

            $transfer->update([
                'status' => 'in_transit',
                'dispatched_by' => $user->id,
                'dispatched_at' => now(),
            ]);

            AuditService::log(
                action: 'transfer.dispatched',
                auditable: $transfer,
                oldValues: ['status' => 'approved'],
                newValues: ['status' => 'in_transit'],
                reason: "Dispatched transfer {$transfer->reference_number}"
            );

            return $transfer;
        });
    }

    /**
     * Destination Store Manager confirms receipt.
     * Increases destination store available stock only on confirmation.
     * Records any discrepancies or damaged quantities.
     */
    public function receiveTransfer(
        StockTransfer $transfer,
        array $receivedQuantities,
        User $user,
        array $damagedQuantities = [],
        array $discrepancyReasons = []
    ): StockTransfer {
        if ($transfer->status !== 'in_transit') {
            throw new Exception("Transfer {$transfer->reference_number} is not in transit (current status: {$transfer->status}).");
        }

        return DB::transaction(function () use ($transfer, $receivedQuantities, $user, $damagedQuantities, $discrepancyReasons) {
            $hasDiscrepancy = false;

            foreach ($transfer->items as $item) {
                $variantId = $item->product_variant_id;
                $itemId = $item->id;

                $qtyReceived = isset($receivedQuantities[$itemId])
                    ? (int) $receivedQuantities[$itemId]
                    : (isset($receivedQuantities[$variantId]) ? (int) $receivedQuantities[$variantId] : $item->quantity_dispatched);

                $qtyDamaged = isset($damagedQuantities[$itemId])
                    ? (int) $damagedQuantities[$itemId]
                    : (isset($damagedQuantities[$variantId]) ? (int) $damagedQuantities[$variantId] : 0);

                $qtyAccepted = max(0, $qtyReceived);
                $qtyDiscrepancy = $item->quantity_dispatched - ($qtyReceived + $qtyDamaged);

                if ($qtyDiscrepancy !== 0 || $qtyDamaged > 0) {
                    $hasDiscrepancy = true;
                }

                $reason = $discrepancyReasons[$itemId] ?? ($discrepancyReasons[$variantId] ?? null);

                $item->update([
                    'quantity_received' => $qtyReceived,
                    'quantity_discrepancy' => $qtyDiscrepancy,
                    'discrepancy_reason' => $reason,
                ]);

                // Row-lock and update destination stock balance
                $destBalance = StockBalance::where('store_id', $transfer->destination_store_id)
                    ->where('product_variant_id', $variantId)
                    ->lockForUpdate()
                    ->first();

                if (!$destBalance) {
                    $destBalance = StockBalance::create([
                        'store_id' => $transfer->destination_store_id,
                        'product_variant_id' => $variantId,
                        'quantity_on_hand' => 0,
                        'quantity_reserved' => 0,
                        'damaged_quantity' => 0,
                    ]);
                }

                $balanceBefore = $destBalance->quantity_on_hand;
                $balanceAfter = $balanceBefore + $qtyAccepted;

                // Only accepted goods go into sellable inventory
                if ($qtyAccepted > 0) {
                    $destBalance->increment('quantity_on_hand', $qtyAccepted);

                    StockLedger::create([
                        'transaction_type' => StockTransactionType::TRANSFER_RECEIVE,
                        'reference_number' => $transfer->reference_number,
                        'store_id' => $transfer->destination_store_id,
                        'related_store_id' => $transfer->source_store_id,
                        'product_variant_id' => $variantId,
                        'quantity' => $qtyAccepted,
                        'balance_after' => $balanceAfter,
                        'user_id' => $user->id,
                        'notes' => "Received transfer {$transfer->reference_number}",
                        'created_at' => now(),
                    ]);
                }

                // Damaged stock is stored separately in quarantine
                if ($qtyDamaged > 0) {
                    $destBalance->increment('damaged_quantity', $qtyDamaged);
                }
            }

            $finalStatus = 'completed';
            $transfer->update([
                'status' => $finalStatus,
                'received_by' => $user->id,
                'received_at' => now(),
            ]);

            $roleTitle = $user->role_name ?? 'Staff';
            $destName = $transfer->destinationStore?->name ?? 'Destination Store';

            AuditService::log(
                action: 'transfer.received',
                auditable: $transfer,
                oldValues: ['status' => 'in_transit'],
                newValues: [
                    'status' => $finalStatus,
                    'received_by_id' => $user->id,
                    'received_by_name' => $user->name,
                    'receiver_role' => $user->role instanceof \App\Enums\UserRole ? $user->role->value : (string) $user->role,
                    'store' => $destName,
                ],
                reason: "Confirmed receipt of transfer {$transfer->reference_number} by {$user->name} ({$roleTitle}) at {$destName}"
            );

            return $transfer;
        });
    }
}
