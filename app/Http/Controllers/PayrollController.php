<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\FinancialAccount;
use App\Models\SalaryAdvance;
use App\Models\SalaryPayment;
use App\Models\Store;
use App\Services\PayrollService;
use Illuminate\Http\Request;

class PayrollController extends Controller
{
    public function __construct(
        protected PayrollService $payrollService
    ) {}

    public function index(Request $request)
    {
        $user = auth()->user();
        $month = $request->input('month', now()->format('Y-m'));

        $query = Employee::where('employment_status', 'active')->with(['store', 'salaryPayments' => fn ($q) => $q->where('salary_month', $month)]);

        if ($user->isStoreManager() && $user->store_id) {
            $query->where('store_id', $user->store_id);
        } elseif ($request->filled('store_id') && ($user->isSuperAdmin() || $user->isAccountant())) {
            $query->where('store_id', $request->store_id);
        }

        $employees = $query->paginate(15)->withQueryString();
        $stores = Store::where('is_active', true)->get();
        $accounts = FinancialAccount::where('is_active', true)->get();

        $recentAdvances = SalaryAdvance::with(['employee', 'store'])
            ->orderBy('id', 'desc')
            ->limit(10)
            ->get();

        return view('payroll.index', compact('employees', 'month', 'stores', 'accounts', 'recentAdvances'));
    }

    public function advance(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'amount' => 'required|numeric|min:1',
            'payment_method' => 'required|string',
            'financial_account_id' => 'nullable|exists:financial_accounts,id',
            'notes' => 'nullable|string|max:255',
            'reason' => 'nullable|string|max:255',
        ]);

        $employee = Employee::findOrFail($validated['employee_id']);
        $store = $employee->store ?? Store::first();
        $account = !empty($validated['financial_account_id']) ? FinancialAccount::find($validated['financial_account_id']) : null;
        $method = $validated['payment_method'] === 'bank' ? 'bank_transfer' : $validated['payment_method'];

        try {
            $adv = $this->payrollService->issueAdvance(
                $employee,
                $store,
                (float) $validated['amount'],
                auth()->user(),
                $method,
                $account,
                $validated['notes'] ?? $validated['reason'] ?? null
            );

            return redirect()->route('payroll.index')->with('success', "Salary advance {$adv->advance_number} of ৳{$adv->amount} issued to {$employee->name}.");
        } catch (\Exception $e) {
            return back()->withErrors(['amount' => $e->getMessage()]);
        }
    }

    public function pay(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'salary_month' => 'nullable|string|regex:/^\d{4}-\d{2}$/',
            'month' => 'nullable|integer|between:1,12',
            'year' => 'nullable|integer|between:2020,2030',
            'amount_to_pay' => 'nullable|numeric|min:0.01',
            'payment_method' => 'required|string',
            'financial_account_id' => 'nullable|exists:financial_accounts,id',
            'notes' => 'nullable|string|max:255',
        ]);

        $employee = Employee::findOrFail($validated['employee_id']);
        $store = $employee->store ?? Store::first();
        $account = !empty($validated['financial_account_id']) ? FinancialAccount::find($validated['financial_account_id']) : null;
        $method = $validated['payment_method'] === 'bank' ? 'bank_transfer' : $validated['payment_method'];

        $salaryMonth = $validated['salary_month'] ?? sprintf('%04d-%02d', $validated['year'] ?? date('Y'), $validated['month'] ?? date('n'));
        $amountToPay = !empty($validated['amount_to_pay'])
            ? (float) $validated['amount_to_pay']
            : max(0, (float) $employee->monthly_salary - (float) $employee->current_advance_balance);

        try {
            $payment = $this->payrollService->paySalary(
                $employee,
                $store,
                $salaryMonth,
                $amountToPay,
                auth()->user(),
                $method,
                $account,
                $validated['notes'] ?? null
            );

            return redirect()->route('payroll.index')
                ->with('success', "Salary payment {$payment->payment_number} of ৳{$payment->amount_paid} completed for {$employee->name}.");
        } catch (\Exception $e) {
            return back()->withErrors(['salary_month' => $e->getMessage()]);
        }
    }
}
