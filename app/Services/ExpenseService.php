<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\ExpenseApprovalRule;
use App\Models\ExpenseCategory;
use App\Models\FinancialAccount;
use App\Models\Store;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;

class ExpenseService
{
    public function __construct(
        protected FinancialAccountService $accountService,
        protected CashRegisterService $cashRegisterService,
        protected AuditService $auditService
    ) {}

    /**
     * Check if a user role can approve an expense of given amount.
     */
    public function canApprove(User $user, float $amount, ?int $storeId = null): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        $rule = ExpenseApprovalRule::where('role', $user->role->value)
            ->where(function ($q) use ($storeId) {
                $q->whereNull('store_id')->orWhere('store_id', $storeId);
            })
            ->orderBy('max_amount', 'desc')
            ->first();

        if (!$rule) {
            // Default built-in threshold fallback: Store Manager <= 5,000; Accountant <= 30,000
            if ($user->isStoreManager() && $amount <= 5000.00) {
                return true;
            }
            if ($user->isAccountant() && $amount <= 30000.00) {
                return true;
            }
            return false;
        }

        return $amount <= (float) $rule->max_amount;
    }

    /**
     * Create an expense entry.
     * Auto-approves if creator has authority and requests immediate approval, else marks pending.
     */
    public function createExpense(
        Store $store,
        ExpenseCategory $category,
        float $amount,
        string $expenseDate,
        string $title,
        User $user,
        string $paymentMethod = 'cash',
        ?FinancialAccount $account = null,
        ?string $description = null,
        ?string $receiptAttachmentPath = null,
        bool $autoApproveIfAuthorized = false
    ): Expense {
        if ($amount <= 0) {
            throw new Exception("Expense amount must be greater than zero.");
        }

        return DB::transaction(function () use ($store, $category, $amount, $expenseDate, $title, $user, $paymentMethod, $account, $description, $receiptAttachmentPath, $autoApproveIfAuthorized) {
            $expenseNumber = SequenceService::generate('EXP', $store->code);

            $status = 'pending_approval';
            $approvedBy = null;
            $approvedAt = null;
            $paidAt = null;

            if ($autoApproveIfAuthorized && $this->canApprove($user, $amount, $store->id)) {
                $status = 'approved';
                $approvedBy = $user->id;
                $approvedAt = now();

                if ($paymentMethod === 'cash') {
                    $this->cashRegisterService->recordCashExpense($store, $amount);
                    $paidAt = now();
                }
            }

            $expense = Expense::create([
                'expense_number' => $expenseNumber,
                'store_id' => $store->id,
                'expense_category_id' => $category->id,
                'financial_account_id' => $account?->id,
                'amount' => $amount,
                'expense_date' => $expenseDate,
                'payment_method' => $paymentMethod,
                'title' => $title,
                'description' => $description,
                'receipt_attachment_path' => $receiptAttachmentPath,
                'status' => $status,
                'created_by' => $user->id,
                'approved_by' => $approvedBy,
                'approved_at' => $approvedAt,
                'paid_at' => $paidAt,
            ]);

            AuditService::log(
                action: 'expense.created',
                auditable: $expense,
                oldValues: null,
                newValues: ['expense_number' => $expenseNumber, 'amount' => $amount, 'status' => $status],
                reason: "Created expense {$expenseNumber}: {$title} (৳{$amount})"
            );

            return $expense;
        });
    }

    /**
     * Approve a pending expense.
     */
    public function approveExpense(Expense $expense, User $user): Expense
    {
        if ($expense->status !== 'pending_approval') {
            throw new Exception("Expense {$expense->expense_number} is already {$expense->status}.");
        }

        // Check if user is authorized to approve this amount
        if (!$this->canApprove($user, (float) $expense->amount, $expense->store_id)) {
            throw new Exception("Unauthorized: Your role cannot approve expenses exceeding the authorized threshold of ৳{$expense->amount}.");
        }

        // Disallow self-approval for restricted high expenses unless Super Admin
        if ($expense->created_by === $user->id && !$user->isSuperAdmin()) {
            throw new Exception("Self-approval of restricted expenses is not permitted.");
        }

        return DB::transaction(function () use ($expense, $user) {
            $expense->update([
                'status' => 'approved',
                'approved_by' => $user->id,
                'approved_at' => now(),
            ]);

            AuditService::log(
                action: 'expense.approved',
                auditable: $expense,
                oldValues: ['status' => 'pending_approval'],
                newValues: ['status' => 'approved', 'approved_by' => $user->id],
                reason: "Approved expense {$expense->expense_number} by {$user->name}"
            );

            return $expense;
        });
    }

    /**
     * Reject an expense with mandatory reason.
     */
    public function rejectExpense(Expense $expense, User $user, string $reason): Expense
    {
        if ($expense->status !== 'pending_approval') {
            throw new Exception("Expense {$expense->expense_number} is already {$expense->status}.");
        }

        if (empty(trim($reason))) {
            throw new Exception("A rejection reason is mandatory.");
        }

        return DB::transaction(function () use ($expense, $user, $reason) {
            $expense->update([
                'status' => 'rejected',
                'rejection_reason' => $reason,
                'approved_by' => $user->id,
                'approved_at' => now(),
            ]);

            AuditService::log(
                action: 'expense.rejected',
                auditable: $expense,
                oldValues: ['status' => 'pending_approval'],
                newValues: ['status' => 'rejected', 'reason' => $reason],
                reason: "Rejected expense {$expense->expense_number}"
            );

            return $expense;
        });
    }

    /**
     * Execute payout of an approved expense.
     * Decrements the appropriate financial account or cash register atomically.
     */
    public function payExpense(Expense $expense, User $user, ?FinancialAccount $account = null): Expense
    {
        if ($expense->status !== 'approved') {
            throw new Exception("Expense {$expense->expense_number} must be approved before payment can be executed.");
        }

        return DB::transaction(function () use ($expense, $user, $account) {
            $amount = (float) $expense->amount;
            $financialAccount = $account ?? $expense->financialAccount;

            if ($expense->payment_method === 'cash') {
                // Cash payment: deduct from daily cash register if open
                $this->cashRegisterService->recordCashExpense($expense->store, $amount);
            } elseif ($financialAccount) {
                // Bank / Mobile account withdrawal
                $this->accountService->withdraw(
                    $financialAccount,
                    $amount,
                    'expense_payment',
                    $user,
                    Expense::class,
                    $expense->id,
                    $expense->expense_number,
                    "Paid expense: {$expense->title}"
                );
            }

            $expense->update([
                'status' => 'paid',
                'financial_account_id' => $financialAccount?->id,
                'paid_at' => now(),
            ]);

            AuditService::log(
                action: 'expense.paid',
                auditable: $expense,
                oldValues: ['status' => 'approved'],
                newValues: ['status' => 'paid', 'paid_at' => now()->toIso8601String()],
                reason: "Paid expense {$expense->expense_number} (৳{$amount})"
            );

            return $expense;
        });
    }
}
