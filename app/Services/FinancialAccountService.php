<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\FinancialAccount;
use App\Models\FinancialAccountTransaction;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;

class FinancialAccountService
{
    public function __construct(
        protected AuditService $auditService
    ) {}

    /**
     * Record a deposit into an account.
     */
    public function deposit(
        FinancialAccount $account,
        float $amount,
        string $transactionType,
        User $user,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $referenceNumber = null,
        ?string $description = null
    ): FinancialAccountTransaction {
        if ($amount <= 0) {
            throw new Exception("Deposit amount must be greater than zero.");
        }

        return DB::transaction(function () use ($account, $amount, $transactionType, $user, $referenceType, $referenceId, $referenceNumber, $description) {
            $account = FinancialAccount::where('id', $account->id)->lockForUpdate()->first();
            $account->increment('current_balance', $amount);

            $tx = FinancialAccountTransaction::create([
                'financial_account_id' => $account->id,
                'transaction_type' => $transactionType,
                'amount' => $amount,
                'fee_amount' => 0.00,
                'balance_after' => $account->current_balance,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'reference_number' => $referenceNumber,
                'description' => $description,
                'reconciliation_status' => 'unreconciled',
                'user_id' => $user->id,
            ]);

            AuditService::log(
                action: 'financial_account.deposit',
                auditable: $account,
                oldValues: ['balance' => $account->current_balance - $amount],
                newValues: ['balance' => $account->current_balance, 'amount' => $amount],
                reason: $description ?? "Deposit of ৳{$amount} to {$account->name}"
            );

            return $tx;
        });
    }

    /**
     * Record a withdrawal from an account.
     */
    public function withdraw(
        FinancialAccount $account,
        float $amount,
        string $transactionType,
        User $user,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $referenceNumber = null,
        ?string $description = null
    ): FinancialAccountTransaction {
        if ($amount <= 0) {
            throw new Exception("Withdrawal amount must be greater than zero.");
        }

        return DB::transaction(function () use ($account, $amount, $transactionType, $user, $referenceType, $referenceId, $referenceNumber, $description) {
            $account = FinancialAccount::where('id', $account->id)->lockForUpdate()->first();

            if ($account->current_balance < $amount) {
                throw new Exception("Insufficient funds in {$account->name}. Required: ৳{$amount}, Available: ৳{$account->current_balance}.");
            }

            $account->decrement('current_balance', $amount);

            $tx = FinancialAccountTransaction::create([
                'financial_account_id' => $account->id,
                'transaction_type' => $transactionType,
                'amount' => -$amount,
                'fee_amount' => 0.00,
                'balance_after' => $account->current_balance,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'reference_number' => $referenceNumber,
                'description' => $description,
                'reconciliation_status' => 'unreconciled',
                'user_id' => $user->id,
            ]);

            AuditService::log(
                action: 'financial_account.withdrawal',
                auditable: $account,
                oldValues: ['balance' => $account->current_balance + $amount],
                newValues: ['balance' => $account->current_balance, 'amount' => $amount],
                reason: $description ?? "Withdrawal of ৳{$amount} from {$account->name}"
            );

            return $tx;
        });
    }

    /**
     * Atomic Account-to-Account transfer with optional separate transfer fee.
     * Never double-counts transfer as revenue or expense.
     * Fees are posted separately to categorized expenses.
     */
    public function transfer(
        FinancialAccount $sourceAccount,
        FinancialAccount $destinationAccount,
        float $amount,
        float $feeAmount,
        User $user,
        ?string $notes = null
    ): array {
        if ($sourceAccount->id === $destinationAccount->id) {
            throw new Exception("Source and destination accounts must be different.");
        }

        if ($amount <= 0) {
            throw new Exception("Transfer amount must be greater than zero.");
        }

        $totalSourceDeduction = $amount + $feeAmount;

        return DB::transaction(function () use ($sourceAccount, $destinationAccount, $amount, $feeAmount, $totalSourceDeduction, $user, $notes) {
            $source = FinancialAccount::where('id', $sourceAccount->id)->lockForUpdate()->first();
            $dest = FinancialAccount::where('id', $destinationAccount->id)->lockForUpdate()->first();

            if ($source->current_balance < $totalSourceDeduction) {
                throw new Exception("Insufficient funds in {$source->name} including fee. Required: ৳{$totalSourceDeduction}, Available: ৳{$source->current_balance}.");
            }

            $transferRef = SequenceService::generate('TRF', 'HQ');

            // 1. Deduct principal + fee from source
            $source->decrement('current_balance', $totalSourceDeduction);
            $outTx = FinancialAccountTransaction::create([
                'financial_account_id' => $source->id,
                'transaction_type' => 'transfer_out',
                'amount' => -$amount,
                'fee_amount' => $feeAmount,
                'balance_after' => $source->current_balance,
                'related_account_id' => $dest->id,
                'reference_number' => $transferRef,
                'description' => "Transfer to {$dest->name}: " . ($notes ?? 'Inter-account transfer'),
                'user_id' => $user->id,
            ]);

            // 2. Add principal to destination
            $dest->increment('current_balance', $amount);
            $inTx = FinancialAccountTransaction::create([
                'financial_account_id' => $dest->id,
                'transaction_type' => 'transfer_in',
                'amount' => $amount,
                'fee_amount' => 0.00,
                'balance_after' => $dest->current_balance,
                'related_account_id' => $source->id,
                'reference_number' => $transferRef,
                'description' => "Transfer from {$source->name}: " . ($notes ?? 'Inter-account transfer'),
                'user_id' => $user->id,
            ]);

            // 3. If fee was charged (e.g. bKash cashout 1.5% or bank remittance fee), record as Expense & separate transaction
            $feeExpense = null;
            if ($feeAmount > 0) {
                FinancialAccountTransaction::create([
                    'financial_account_id' => $source->id,
                    'transaction_type' => 'fee',
                    'amount' => -$feeAmount,
                    'fee_amount' => 0.00,
                    'balance_after' => $source->current_balance,
                    'related_account_id' => $dest->id,
                    'reference_number' => $transferRef,
                    'description' => "Transfer fee for {$transferRef}",
                    'user_id' => $user->id,
                ]);

                $bankFeeCategory = ExpenseCategory::firstOrCreate(
                    ['code' => 'BANK_FEES'],
                    ['name' => 'Bank Charges & Transfer Fees', 'is_active' => true]
                );

                $feeExpense = Expense::create([
                    'expense_number' => SequenceService::generate('EXP', 'HQ'),
                    'store_id' => $source->store_id ?? \App\Models\Store::first()?->id ?? 1,
                    'expense_category_id' => $bankFeeCategory->id,
                    'financial_account_id' => $source->id,
                    'amount' => $feeAmount,
                    'expense_date' => now()->format('Y-m-d'),
                    'payment_method' => 'bank_transfer',
                    'title' => "Transfer fee for {$transferRef}",
                    'description' => "Fee incurred transferring from {$source->name} to {$dest->name}",
                    'status' => 'paid',
                    'created_by' => $user->id,
                    'approved_by' => $user->id,
                    'approved_at' => now(),
                    'paid_at' => now(),
                ]);
            }

            AuditService::log(
                action: 'financial_account.transfer',
                auditable: $source,
                oldValues: ['source_balance' => $source->current_balance + $totalSourceDeduction, 'dest_balance' => $dest->current_balance - $amount],
                newValues: ['source_balance' => $source->current_balance, 'dest_balance' => $dest->current_balance, 'amount' => $amount, 'fee' => $feeAmount],
                reason: "Inter-account transfer {$transferRef} from {$source->name} to {$dest->name}"
            );

            return [
                'transfer_reference' => $transferRef,
                'out_transaction' => $outTx,
                'in_transaction' => $inTx,
                'fee_expense' => $feeExpense,
            ];
        });
    }

    /**
     * Mark an account transaction as reconciled.
     */
    public function reconcileTransaction(FinancialAccountTransaction $tx, User $user): FinancialAccountTransaction
    {
        $tx->update([
            'reconciliation_status' => 'reconciled',
            'reconciled_at' => now(),
            'reconciled_by' => $user->id,
        ]);

        AuditService::log(
            action: 'financial_account.reconcile',
            auditable: $tx->account,
            oldValues: ['status' => 'unreconciled'],
            newValues: ['status' => 'reconciled'],
            reason: "Reconciled transaction ID {$tx->id} ({$tx->reference_number})"
        );

        return $tx;
    }
}
