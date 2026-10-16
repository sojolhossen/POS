<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\FinancialAccount;
use App\Models\SalaryAdvance;
use App\Models\SalaryPayment;
use App\Models\Store;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;

class PayrollService
{
    public function __construct(
        protected FinancialAccountService $accountService,
        protected CashRegisterService $cashRegisterService,
        protected AuditService $auditService
    ) {}

    /**
     * Record daily attendance for an employee.
     */
    public function recordAttendance(
        Employee $employee,
        Store $store,
        string $date,
        string $status,
        User $user,
        ?string $inTime = null,
        ?string $outTime = null,
        ?string $notes = null
    ): Attendance {
        return DB::transaction(function () use ($employee, $store, $date, $status, $user, $inTime, $outTime, $notes) {
            $attendance = Attendance::updateOrCreate(
                [
                    'employee_id' => $employee->id,
                    'date' => $date,
                ],
                [
                    'store_id' => $store->id,
                    'status' => $status,
                    'in_time' => $inTime,
                    'out_time' => $outTime,
                    'notes' => $notes,
                    'recorded_by' => $user->id,
                ]
            );

            AuditService::log(
                action: 'attendance.recorded',
                auditable: $attendance,
                oldValues: null,
                newValues: ['employee_id' => $employee->id, 'date' => $date, 'status' => $status],
                reason: "Attendance recorded for {$employee->name} on {$date} ({$status})"
            );

            return $attendance;
        });
    }

    /**
     * Correct an attendance record with mandatory explanation.
     */
    public function correctAttendance(
        Attendance $attendance,
        string $newStatus,
        User $user,
        string $correctionReason
    ): Attendance {
        if (empty(trim($correctionReason))) {
            throw new Exception("A correction reason is mandatory to alter attendance records.");
        }

        return DB::transaction(function () use ($attendance, $newStatus, $user, $correctionReason) {
            $oldStatus = $attendance->status;
            $attendance->update([
                'status' => $newStatus,
                'correction_reason' => $correctionReason,
                'recorded_by' => $user->id,
            ]);

            AuditService::log(
                action: 'attendance.corrected',
                auditable: $attendance,
                oldValues: ['status' => $oldStatus],
                newValues: ['status' => $newStatus, 'reason' => $correctionReason],
                reason: "Attendance corrected for {$attendance->employee->name} on {$attendance->date->format('Y-m-d')}"
            );

            return $attendance;
        });
    }

    /**
     * Issue salary advance to employee.
     */
    public function issueAdvance(
        Employee $employee,
        Store $store,
        float $amount,
        User $user,
        string $paymentMethod = 'cash',
        ?FinancialAccount $account = null,
        ?string $notes = null
    ): SalaryAdvance {
        if ($amount <= 0) {
            throw new Exception("Advance amount must be greater than zero.");
        }

        return DB::transaction(function () use ($employee, $store, $amount, $user, $paymentMethod, $account, $notes) {
            $advanceNumber = SequenceService::generate('ADV', $store->code);

            // Deduct funds from account / cash
            if ($paymentMethod === 'cash') {
                $this->cashRegisterService->recordCashExpense($store, $amount);
            } elseif ($account) {
                $this->accountService->withdraw(
                    $account,
                    $amount,
                    'withdrawal',
                    $user,
                    SalaryAdvance::class,
                    null,
                    $advanceNumber,
                    "Salary advance to {$employee->name}"
                );
            }

            $advance = SalaryAdvance::create([
                'advance_number' => $advanceNumber,
                'employee_id' => $employee->id,
                'store_id' => $store->id,
                'amount' => $amount,
                'request_date' => now()->format('Y-m-d'),
                'financial_account_id' => $account?->id,
                'payment_method' => $paymentMethod,
                'status' => 'paid',
                'approved_by' => $user->id,
                'paid_at' => now(),
                'notes' => $notes,
            ]);

            // Increment employee's advance balance
            $employee->increment('current_advance_balance', $amount);

            AuditService::log(
                action: 'salary_advance.issued',
                auditable: $advance,
                oldValues: null,
                newValues: ['amount' => $amount, 'advance_balance' => $employee->current_advance_balance],
                reason: "Issued salary advance {$advanceNumber} to {$employee->name} (৳{$amount})"
            );

            return $advance;
        });
    }

    /**
     * Process monthly salary payout.
     * Prevents duplicate payments, deducts advances, creates linked expense, updates accounts.
     */
    public function paySalary(
        Employee $employee,
        Store $store,
        string $salaryMonth, // YYYY-MM
        float $amountToPay,
        User $user,
        string $paymentMethod = 'bank_transfer',
        ?FinancialAccount $account = null,
        ?string $notes = null
    ): SalaryPayment {
        return DB::transaction(function () use ($employee, $store, $salaryMonth, $amountToPay, $user, $paymentMethod, $account, $notes) {
            // Row-lock employee
            $emp = Employee::where('id', $employee->id)->lockForUpdate()->first();

            // Check if payment record already exists for this employee and month
            $existing = SalaryPayment::where('employee_id', $emp->id)
                ->where('salary_month', $salaryMonth)
                ->first();

            if ($existing && $existing->status === 'paid') {
                throw new Exception("Payroll for {$emp->name} for month {$salaryMonth} is already fully paid.");
            }

            $basic = (float) $emp->monthly_salary;
            $advanceDeduction = min((float) $emp->current_advance_balance, $basic);
            $netPayable = max(0, $basic - $advanceDeduction);

            if ($amountToPay > $netPayable) {
                throw new Exception("Payment amount ৳{$amountToPay} exceeds net payable amount of ৳{$netPayable}.");
            }

            $outstanding = round($netPayable - $amountToPay, 2);
            $paymentStatus = $outstanding <= 0 ? 'paid' : 'partial';

            $paymentNumber = SequenceService::generate('SAL', $store->code);

            // 1. Create corresponding Salary Expense under 'Salaries & Wages' category
            $salaryCategory = ExpenseCategory::firstOrCreate(
                ['code' => 'SALARIES'],
                ['name' => 'Salaries & Wages', 'is_active' => true]
            );

            $expense = Expense::create([
                'expense_number' => SequenceService::generate('EXP', $store->code),
                'store_id' => $store->id,
                'expense_category_id' => $salaryCategory->id,
                'financial_account_id' => $account?->id,
                'amount' => $amountToPay,
                'expense_date' => now()->format('Y-m-d'),
                'payment_method' => $paymentMethod,
                'title' => "Salary for {$emp->name} ({$salaryMonth})",
                'description' => "Salary payment: Net ৳{$amountToPay} after ৳{$advanceDeduction} advance recovery",
                'status' => 'paid',
                'created_by' => $user->id,
                'approved_by' => $user->id,
                'approved_at' => now(),
                'paid_at' => now(),
            ]);

            // 2. Deduct funds from financial account or cash register
            if ($paymentMethod === 'cash') {
                $this->cashRegisterService->recordCashExpense($store, $amountToPay);
            } elseif ($account) {
                $this->accountService->withdraw(
                    $account,
                    $amountToPay,
                    'salary_payment',
                    $user,
                    SalaryPayment::class,
                    null,
                    $paymentNumber,
                    "Salary payment for {$emp->name} ({$salaryMonth})"
                );
            }

            // 3. Create or update Salary Payment Record
            $payment = SalaryPayment::create([
                'payment_number' => $paymentNumber,
                'employee_id' => $emp->id,
                'store_id' => $store->id,
                'salary_month' => $salaryMonth,
                'basic_salary' => $basic,
                'advance_deduction' => $advanceDeduction,
                'net_payable' => $netPayable,
                'amount_paid' => $amountToPay,
                'outstanding_balance' => $outstanding,
                'payment_method' => $paymentMethod,
                'financial_account_id' => $account?->id,
                'expense_id' => $expense->id,
                'status' => $paymentStatus,
                'payment_date' => now()->format('Y-m-d'),
                'created_by' => $user->id,
                'notes' => $notes,
            ]);

            // 4. Reduce employee advance balance
            if ($advanceDeduction > 0) {
                $emp->decrement('current_advance_balance', $advanceDeduction);
            }

            AuditService::log(
                action: 'salary.paid',
                auditable: $payment,
                oldValues: null,
                newValues: ['amount' => $amountToPay, 'advance_deducted' => $advanceDeduction, 'status' => $paymentStatus],
                reason: "Processed salary payment {$paymentNumber} for {$emp->name} ({$salaryMonth})"
            );

            return $payment;
        });
    }
}
