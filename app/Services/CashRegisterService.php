<?php

namespace App\Services;

use App\Models\DailyCashRegister;
use App\Models\FinancialAccount;
use App\Models\Store;
use App\Models\StoreCashTransfer;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;

class CashRegisterService
{
    public function __construct(
        protected AuditService $auditService,
        protected FinancialAccountService $accountService
    ) {}

    /**
     * Get suggested opening cash based on previous day closing balance for the store.
     */
    public function getSuggestedOpeningCash(Store $store): array
    {
        $today = now()->format('Y-m-d');

        // Check for latest register strictly before today
        $prevRegister = DailyCashRegister::where('store_id', $store->id)
            ->where('business_date', '<', $today)
            ->latest('business_date')
            ->first();

        // Fallback: check any closed register
        if (!$prevRegister) {
            $prevRegister = DailyCashRegister::where('store_id', $store->id)
                ->where('status', 'closed')
                ->latest('business_date')
                ->first();
        }

        if ($prevRegister) {
            $carryAmount = $prevRegister->closing_cash_counted !== null
                ? (float) $prevRegister->closing_cash_counted
                : (float) $prevRegister->cash_in_hand_expected;

            return [
                'has_previous' => true,
                'suggested_amount' => $carryAmount,
                'previous_date' => $prevRegister->business_date->format('d M Y'),
                'previous_register_id' => $prevRegister->id,
                'previous_counted' => $prevRegister->closing_cash_counted !== null,
                'is_unclosed' => $prevRegister->isOpen(),
            ];
        }

        return [
            'has_previous' => false,
            'suggested_amount' => null,
            'previous_date' => null,
            'previous_register_id' => null,
            'previous_counted' => false,
            'is_unclosed' => false,
        ];
    }

    /**
     * Get unclosed previous cash register for a store strictly before today.
     */
    public function getUnclosedPreviousRegister(Store $store): ?DailyCashRegister
    {
        $today = now()->format('Y-m-d');
        return DailyCashRegister::where('store_id', $store->id)
            ->where('business_date', '<', $today)
            ->where('status', 'open')
            ->latest('business_date')
            ->first();
    }

    /**
     * Open daily cash register for store for current business day.
     */
    public function openRegister(
        Store $store,
        User $user,
        float $openingCash,
        ?string $discrepancyReason = null
    ): DailyCashRegister {
        $today = now()->format('Y-m-d');

        return DB::transaction(function () use ($store, $user, $openingCash, $discrepancyReason, $today) {
            $existing = DailyCashRegister::where('store_id', $store->id)
                ->where('business_date', $today)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                if ($existing->isOpen()) {
                    throw new Exception("Cash register is already open for {$store->name} for today ({$today}).");
                }
                throw new Exception("Cash register for {$today} is already closed. Please request an authorized supervisor to reopen.");
            }

            // If an unclosed register exists from a previous date, auto-close it with expected cash
            // to preserve ledger audit trail and prevent dangling unclosed sessions.
            $unclosed = $this->getUnclosedPreviousRegister($store);
            if ($unclosed) {
                $this->closeRegister(
                    $unclosed,
                    $user,
                    (float) $unclosed->cash_in_hand_expected,
                    "Auto-closed on next business day ({$today}) opening by {$user->name}."
                );
            }

            // Check suggested opening cash carryover
            $suggested = $this->getSuggestedOpeningCash($store);
            $discrepancy = 0.00;
            if ($suggested['has_previous']) {
                $discrepancy = round($openingCash - (float) $suggested['suggested_amount'], 2);
            }

            $register = DailyCashRegister::create([
                'store_id' => $store->id,
                'business_date' => $today,
                'opening_cash' => $openingCash,
                'opening_discrepancy_amount' => $discrepancy,
                'opening_discrepancy_reason' => $discrepancy != 0.0 ? $discrepancyReason : null,
                'opened_by' => $user->id,
                'opened_at' => now(),
                'cash_sales_amount' => 0.00,
                'cash_refunds_amount' => 0.00,
                'cash_expenses_amount' => 0.00,
                'cash_transfers_amount' => 0.00,
                'cash_in_hand_expected' => $openingCash,
                'status' => 'open',
            ]);

            AuditService::log(
                action: 'cash_register.opened',
                auditable: $register,
                oldValues: null,
                newValues: [
                    'store_id' => $store->id,
                    'opening_cash' => $openingCash,
                    'opening_discrepancy' => $discrepancy,
                    'date' => $today,
                ],
                reason: "Opened daily cash register for {$store->name} on {$today} with ৳{$openingCash}"
            );

            return $register;
        });
    }

    /**
     * Close the register and lock the business day.
     */
    public function closeRegister(
        DailyCashRegister $register,
        User $user,
        float $closingCashCounted,
        ?string $discrepancyReason = null
    ): DailyCashRegister {
        if (!$register->isOpen()) {
            throw new Exception("Cash register is already {$register->status}.");
        }

        return DB::transaction(function () use ($register, $user, $closingCashCounted, $discrepancyReason) {
            $register = DailyCashRegister::where('id', $register->id)->lockForUpdate()->first();
            $expected = (float) $register->cash_in_hand_expected;
            $discrepancy = round($closingCashCounted - $expected, 2);

            if ($discrepancy != 0.0 && empty($discrepancyReason)) {
                throw new Exception("Cash discrepancy of ৳{$discrepancy} detected. A discrepancy reason is mandatory to close the register.");
            }

            $register->update([
                'closing_cash_counted' => $closingCashCounted,
                'discrepancy_amount' => $discrepancy,
                'discrepancy_reason' => $discrepancyReason,
                'status' => 'closed',
                'closed_by' => $user->id,
                'closed_at' => now(),
            ]);

            AuditService::log(
                action: 'cash_register.closed',
                auditable: $register,
                oldValues: ['status' => 'open'],
                newValues: ['status' => 'closed', 'counted' => $closingCashCounted, 'discrepancy' => $discrepancy],
                reason: "Closed daily cash register for {$register->store->name} on {$register->business_date->format('Y-m-d')}"
            );

            return $register;
        });
    }

    /**
     * Reopen a closed cash register (Store Manager or Super Admin only).
     */
    public function reopenRegister(
        DailyCashRegister $register,
        User $user,
        string $reason
    ): DailyCashRegister {
        if (!$user->isSuperAdmin() && !$user->isStoreManager()) {
            throw new Exception("Only a Store Manager or Super Admin can reopen a closed cash register.");
        }

        if ($register->isOpen()) {
            throw new Exception("Cash register is already open.");
        }

        return DB::transaction(function () use ($register, $user, $reason) {
            $register->update([
                'status' => 'reopened',
                'reopened_by' => $user->id,
                'reopened_at' => now(),
                'reopen_reason' => $reason,
            ]);

            AuditService::log(
                action: 'cash_register.reopened',
                auditable: $register,
                oldValues: ['status' => 'closed'],
                newValues: ['status' => 'reopened', 'reason' => $reason],
                reason: "Reopened daily cash register for {$register->store->name}"
            );

            return $register;
        });
    }

    /**
     * Record cash sale amount in the daily register.
     */
    public function recordCashSale(Store $store, float $amount): void
    {
        $today = now()->format('Y-m-d');
        $register = DailyCashRegister::where('store_id', $store->id)
            ->where('business_date', $today)
            ->first();

        if ($register && $register->isOpen()) {
            $register->increment('cash_sales_amount', $amount);
            $register->increment('cash_in_hand_expected', $amount);
        }
    }

    /**
     * Record cash refund amount in the daily register.
     */
    public function recordCashRefund(Store $store, float $amount): void
    {
        $today = now()->format('Y-m-d');
        $register = DailyCashRegister::where('store_id', $store->id)
            ->where('business_date', $today)
            ->first();

        if ($register && $register->isOpen()) {
            $register->increment('cash_refunds_amount', $amount);
            $register->decrement('cash_in_hand_expected', $amount);
        }
    }

    /**
     * Record cash expense amount in the daily register.
     */
    public function recordCashExpense(Store $store, float $amount): void
    {
        $today = now()->format('Y-m-d');
        $register = DailyCashRegister::where('store_id', $store->id)
            ->where('business_date', $today)
            ->first();

        if ($register && $register->isOpen()) {
            $register->increment('cash_expenses_amount', $amount);
            $register->decrement('cash_in_hand_expected', $amount);
        }
    }

    /**
     * Transfer cash from store register to store owner / company financial account.
     * Immediately deducts expected cash from active register.
     */
    public function transferCashToOwner(
        Store $store,
        User $user,
        float $amount,
        string $transferMethod,
        ?int $financialAccountId = null,
        ?string $recipientName = null,
        ?string $referenceNumber = null,
        ?string $notes = null,
        ?string $attachmentPath = null
    ): StoreCashTransfer {
        if ($amount <= 0) {
            throw new Exception("Transfer amount must be greater than zero.");
        }

        $today = now()->format('Y-m-d');

        return DB::transaction(function () use ($store, $user, $amount, $transferMethod, $financialAccountId, $recipientName, $referenceNumber, $notes, $attachmentPath, $today) {
            $register = DailyCashRegister::where('store_id', $store->id)
                ->where('business_date', $today)
                ->lockForUpdate()
                ->first();

            if (!$register || !$register->isOpen()) {
                throw new Exception("An open daily cash register is required for store '{$store->name}' to transfer cash.");
            }

            if ($amount > (float) $register->cash_in_hand_expected) {
                throw new Exception("Transfer amount (৳" . number_format($amount, 2) . ") exceeds available cash drawer balance (৳" . number_format($register->cash_in_hand_expected, 2) . ").");
            }

            $transferNumber = SequenceService::generate('REM', $store->code);

            // Deduct immediately from register expected cash
            $register->increment('cash_transfers_amount', $amount);
            $register->decrement('cash_in_hand_expected', $amount);

            $transfer = StoreCashTransfer::create([
                'store_id' => $store->id,
                'daily_cash_register_id' => $register->id,
                'transfer_number' => $transferNumber,
                'amount' => $amount,
                'transfer_method' => $transferMethod,
                'financial_account_id' => $financialAccountId,
                'recipient_name' => $recipientName ?? 'Store Owner',
                'reference_number' => $referenceNumber,
                'notes' => $notes,
                'attachment_path' => $attachmentPath,
                'status' => 'pending',
                'transferred_by' => $user->id,
                'transferred_at' => now(),
            ]);

            AuditService::log(
                action: 'cash_transfer.initiated',
                auditable: $transfer,
                oldValues: ['drawer_expected' => (float) $register->cash_in_hand_expected + $amount],
                newValues: ['drawer_expected' => (float) $register->cash_in_hand_expected, 'transfer_amount' => $amount],
                reason: "Initiated cash remittance {$transferNumber} of ৳{$amount} from {$store->name} to owner"
            );

            return $transfer;
        });
    }

    /**
     * Super Admin approves cash transfer from store.
     * Deposits into company financial account (if designated).
     */
    public function approveCashTransfer(StoreCashTransfer $transfer, User $admin): StoreCashTransfer
    {
        if (!$admin->isSuperAdmin()) {
            throw new Exception("Only Super Admin is authorized to verify and approve store cash remittances.");
        }

        if ($transfer->status !== 'pending') {
            throw new Exception("Transfer {$transfer->transfer_number} is already {$transfer->status}.");
        }

        return DB::transaction(function () use ($transfer, $admin) {
            $transfer = StoreCashTransfer::where('id', $transfer->id)->lockForUpdate()->first();

            $transfer->update([
                'status' => 'approved',
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
            ]);

            // Deposit into financial account if selected
            if ($transfer->financial_account_id) {
                $account = FinancialAccount::find($transfer->financial_account_id);
                if ($account) {
                    $this->accountService->deposit(
                        account: $account,
                        amount: (float) $transfer->amount,
                        transactionType: 'remittance_deposit',
                        user: $admin,
                        referenceType: StoreCashTransfer::class,
                        referenceId: $transfer->id,
                        referenceNumber: $transfer->transfer_number,
                        description: "Store remittance from {$transfer->store->name} ({$transfer->transfer_number})"
                    );
                }
            }

            AuditService::log(
                action: 'cash_transfer.approved',
                auditable: $transfer,
                oldValues: ['status' => 'pending'],
                newValues: ['status' => 'approved', 'reviewed_by' => $admin->id],
                reason: "Approved cash remittance {$transfer->transfer_number} (৳{$transfer->amount})"
            );

            return $transfer;
        });
    }

    /**
     * Super Admin rejects cash transfer.
     * Automatically reverses and re-credits the amount back into the store drawer.
     */
    public function rejectCashTransfer(StoreCashTransfer $transfer, User $admin, string $reason): StoreCashTransfer
    {
        if (!$admin->isSuperAdmin()) {
            throw new Exception("Only Super Admin is authorized to reject store cash remittances.");
        }

        if ($transfer->status !== 'pending') {
            throw new Exception("Transfer {$transfer->transfer_number} is already {$transfer->status}.");
        }

        if (empty(trim($reason))) {
            throw new Exception("A rejection reason is mandatory.");
        }

        return DB::transaction(function () use ($transfer, $admin, $reason) {
            $transfer = StoreCashTransfer::where('id', $transfer->id)->lockForUpdate()->first();

            $transfer->update([
                'status' => 'rejected',
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
                'rejection_reason' => $reason,
            ]);

            $amount = (float) $transfer->amount;

            // Re-credit back into daily cash register
            $origRegister = $transfer->dailyCashRegister;
            if ($origRegister && $origRegister->isOpen()) {
                $origRegister->decrement('cash_transfers_amount', $amount);
                $origRegister->increment('cash_in_hand_expected', $amount);
            } else {
                // Check if today's register is currently open
                $todayRegister = DailyCashRegister::where('store_id', $transfer->store_id)
                    ->where('business_date', now()->format('Y-m-d'))
                    ->whereIn('status', ['open', 'reopened'])
                    ->first();

                if ($todayRegister) {
                    $todayRegister->increment('cash_in_hand_expected', $amount);
                } elseif ($origRegister) {
                    $origRegister->decrement('cash_transfers_amount', $amount);
                    $origRegister->increment('cash_in_hand_expected', $amount);
                }
            }

            AuditService::log(
                action: 'cash_transfer.rejected',
                auditable: $transfer,
                oldValues: ['status' => 'pending'],
                newValues: ['status' => 'rejected', 'rejection_reason' => $reason],
                reason: "Rejected cash remittance {$transfer->transfer_number}: ৳{$amount} re-credited back into store cash drawer"
            );

            return $transfer;
        });
    }
}
