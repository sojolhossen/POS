@extends('layouts.app')

@section('title', 'Payroll & Salary Management')

@section('content')
<div class="space-y-6" x-data="{ activeTab: 'process', advanceModal: false, payModal: false, selectedEmp: null }">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">Payroll & Salary Tracking</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">Monthly salary calculation, advance disbursements, duplicate payment prevention, and account integration.</p>
        </div>
        <div class="flex items-center gap-3">
            <button @click="advanceModal = true" class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-medium text-sm rounded-lg shadow-sm">
                + Disburse Salary Advance
            </button>
        </div>
    </div>

    @if (session('success'))
        <div class="p-4 rounded-lg bg-emerald-50 text-emerald-800 border border-emerald-200 dark:bg-emerald-950/40 dark:border-emerald-900/50 dark:text-emerald-200">
            {{ session('success') }}
        </div>
    @endif
    @if ($errors->any())
        <div class="p-4 rounded-lg bg-rose-50 text-rose-800 border border-rose-200 dark:bg-rose-950/40 dark:border-rose-900/50 dark:text-rose-200">
            <ul class="list-disc pl-5 space-y-1 text-sm">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Tabs -->
    <div class="border-b border-slate-200 dark:border-slate-800 flex gap-6 text-sm font-semibold">
        <button @click="activeTab = 'process'" :class="activeTab === 'process' ? 'text-emerald-600 border-b-2 border-emerald-600 pb-3' : 'text-slate-500 hover:text-slate-700 pb-3'">
            Monthly Payroll ({{ date('F Y', mktime(0, 0, 0, $month, 1, $year)) }})
        </button>
        <button @click="activeTab = 'history'" :class="activeTab === 'history' ? 'text-emerald-600 border-b-2 border-emerald-600 pb-3' : 'text-slate-500 hover:text-slate-700 pb-3'">
            Payment History
        </button>
        <button @click="activeTab = 'advances'" :class="activeTab === 'advances' ? 'text-emerald-600 border-b-2 border-emerald-600 pb-3' : 'text-slate-500 hover:text-slate-700 pb-3'">
            Active Advances
        </button>
    </div>

    <!-- Monthly Payroll Calculation Table -->
    <div x-show="activeTab === 'process'" class="space-y-4">
        <div class="flex items-center justify-between bg-white dark:bg-slate-900 p-4 rounded-xl border border-slate-200 dark:border-slate-800">
            <form method="GET" action="{{ route('payroll.index') }}" class="flex items-center gap-3">
                <span class="text-sm font-semibold text-slate-700 dark:text-slate-300">Target Month:</span>
                <select name="month" onchange="this.form.submit()" class="rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 text-xs text-slate-700 dark:text-slate-200">
                    @for($m = 1; $m <= 12; $m++)
                        <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>{{ date('F', mktime(0, 0, 0, $m, 1)) }}</option>
                    @endfor
                </select>
                <select name="year" onchange="this.form.submit()" class="rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 text-xs text-slate-700 dark:text-slate-200">
                    @for($y = 2024; $y <= 2027; $y++)
                        <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>
            </form>
            <span class="text-xs text-slate-400">Total Active Employees: {{ $employees->count() }}</span>
        </div>

        <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-600 dark:text-slate-300">
                    <thead class="bg-slate-50 dark:bg-slate-800/60 text-xs uppercase font-semibold text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
                        <tr>
                            <th class="px-6 py-3">Employee</th>
                            <th class="px-6 py-3">Store</th>
                            <th class="px-6 py-3 text-right">Base Salary</th>
                            <th class="px-6 py-3 text-right">Advance Due</th>
                            <th class="px-6 py-3 text-center">Status</th>
                            <th class="px-6 py-3 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                        @forelse($employees as $emp)
                            @php
                                $monthYear = sprintf('%04d-%02d', $year, $month);
                                $isPaid = $emp->salaryPayments()->where('month_year', $monthYear)->exists();
                                $unreconciledAdvance = $emp->salaryAdvances()->where('is_reconciled', false)->sum('amount');
                            @endphp
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/50 transition">
                                <td class="px-6 py-4">
                                    <div class="font-semibold text-slate-900 dark:text-white">{{ $emp->name }}</div>
                                    <div class="text-xs text-slate-400 font-mono">{{ $emp->employee_code }} • {{ $emp->designation ?? $emp->role }}</div>
                                </td>
                                <td class="px-6 py-4 text-xs">
                                    {{ $emp->store->name ?? 'General' }}
                                </td>
                                <td class="px-6 py-4 text-right font-mono font-bold text-slate-900 dark:text-white">
                                    ৳{{ number_format($emp->base_salary, 2) }}
                                </td>
                                <td class="px-6 py-4 text-right font-mono font-medium text-amber-600 dark:text-amber-400">
                                    ৳{{ number_format($unreconciledAdvance, 2) }}
                                </td>
                                <td class="px-6 py-4 text-center">
                                    @if($isPaid)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-300">
                                            ✓ Paid
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400">
                                            Pending
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right">
                                    @if($isPaid)
                                        <span class="text-xs text-slate-400 italic">Disbursed</span>
                                    @else
                                        <button @click="selectedEmp = {{ json_encode($emp) }}; payModal = true" class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold rounded-lg shadow-sm">
                                            Pay Salary
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-slate-500">
                                    No active employees found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- History Tab -->
    <div x-show="activeTab === 'history'" class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600 dark:text-slate-300">
                <thead class="bg-slate-50 dark:bg-slate-800/60 text-xs uppercase font-semibold text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
                    <tr>
                        <th class="px-6 py-3">Month</th>
                        <th class="px-6 py-3">Employee</th>
                        <th class="px-6 py-3 text-right">Base</th>
                        <th class="px-6 py-3 text-right">Bonus</th>
                        <th class="px-6 py-3 text-right">Deduction / Adv</th>
                        <th class="px-6 py-3 text-right">Net Paid</th>
                        <th class="px-6 py-3">Method</th>
                        <th class="px-6 py-3">Disbursed At</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                    @forelse($payments as $pay)
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/50 transition">
                            <td class="px-6 py-4 font-semibold text-slate-900 dark:text-white">{{ $pay->month_year }}</td>
                            <td class="px-6 py-4">
                                <div class="font-semibold text-slate-900 dark:text-white">{{ $pay->employee->name ?? 'Deleted' }}</div>
                                <div class="text-xs text-slate-400 font-mono">{{ $pay->employee->employee_code ?? '' }}</div>
                            </td>
                            <td class="px-6 py-4 text-right font-mono">৳{{ number_format($pay->base_salary, 2) }}</td>
                            <td class="px-6 py-4 text-right font-mono text-emerald-600">+৳{{ number_format($pay->bonus, 2) }}</td>
                            <td class="px-6 py-4 text-right font-mono text-rose-600">-৳{{ number_format($pay->deduction + $pay->advance_deduction, 2) }}</td>
                            <td class="px-6 py-4 text-right font-mono font-bold text-slate-900 dark:text-white">৳{{ number_format($pay->net_paid_amount, 2) }}</td>
                            <td class="px-6 py-4 text-xs uppercase">{{ $pay->payment_method }}</td>
                            <td class="px-6 py-4 text-xs text-slate-400">{{ \Carbon\Carbon::parse($pay->paid_at)->format('d M, Y h:i A') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center text-slate-500">
                                No historical payroll records yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($payments->hasPages())
            <div class="p-4 border-t border-slate-200 dark:border-slate-800">
                {{ $payments->links() }}
            </div>
        @endif
    </div>

    <!-- Advances Tab -->
    <div x-show="activeTab === 'advances'" class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600 dark:text-slate-300">
                <thead class="bg-slate-50 dark:bg-slate-800/60 text-xs uppercase font-semibold text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
                    <tr>
                        <th class="px-6 py-3">Disbursed Date</th>
                        <th class="px-6 py-3">Employee</th>
                        <th class="px-6 py-3 text-right">Advance Amount</th>
                        <th class="px-6 py-3">Method</th>
                        <th class="px-6 py-3">Reason</th>
                        <th class="px-6 py-3">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                    @forelse($advances as $adv)
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/50 transition">
                            <td class="px-6 py-4 text-xs font-mono">{{ $adv->advance_date }}</td>
                            <td class="px-6 py-4">
                                <div class="font-semibold text-slate-900 dark:text-white">{{ $adv->employee->name ?? 'Deleted' }}</div>
                                <div class="text-xs text-slate-400 font-mono">{{ $adv->employee->employee_code ?? '' }}</div>
                            </td>
                            <td class="px-6 py-4 text-right font-mono font-bold text-amber-600 dark:text-amber-400">
                                ৳{{ number_format($adv->amount, 2) }}
                            </td>
                            <td class="px-6 py-4 text-xs uppercase">{{ $adv->payment_method }}</td>
                            <td class="px-6 py-4 text-xs text-slate-500">{{ $adv->reason ?? '--' }}</td>
                            <td class="px-6 py-4">
                                @if($adv->is_reconciled)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                        Reconciled
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-800 dark:bg-amber-950/50 dark:text-amber-300">
                                        Outstanding
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-slate-500">
                                No salary advances logged.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($advances->hasPages())
            <div class="p-4 border-t border-slate-200 dark:border-slate-800">
                {{ $advances->links() }}
            </div>
        @endif
    </div>

    <!-- Issue Advance Modal -->
    <div x-show="advanceModal" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4" style="display: none;">
        <div class="bg-white dark:bg-slate-900 rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-slate-200 dark:border-slate-800 space-y-4">
            <h3 class="text-lg font-bold text-slate-900 dark:text-white">Disburse Salary Advance</h3>
            <form action="{{ route('payroll.advance') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Employee *</label>
                    <select name="employee_id" required class="w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 text-xs text-slate-900 dark:text-white">
                        <option value="">Select Employee</option>
                        @foreach($employees as $emp)
                            <option value="{{ $emp->id }}">{{ $emp->name }} ({{ $emp->employee_code }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Amount (BDT ৳) *</label>
                    <input type="number" step="0.01" min="1" name="amount" required placeholder="0.00" class="w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 text-xs font-mono font-medium">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Payment Method *</label>
                    <select name="payment_method" required class="w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 text-xs text-slate-900 dark:text-white">
                        <option value="cash">Cash Register Drawer</option>
                        <option value="bank">Bank Account</option>
                        <option value="bkash">bKash</option>
                        <option value="nagad">Nagad</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Financial Account (Optional for Cash)</label>
                    <select name="financial_account_id" class="w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 text-xs text-slate-900 dark:text-white">
                        <option value="">None / Cash Drawer</option>
                        @foreach($financialAccounts as $acc)
                            <option value="{{ $acc->id }}">{{ $acc->account_name }} (৳{{ number_format($acc->current_balance, 2) }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Advance Reason / Notes</label>
                    <textarea name="reason" rows="2" placeholder="e.g. Medical emergency advance" class="w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 text-xs text-slate-900 dark:text-white"></textarea>
                </div>
                <div class="flex items-center justify-end gap-3 pt-3">
                    <button type="button" @click="advanceModal = false" class="px-4 py-2 text-xs font-medium text-slate-600 hover:text-slate-800">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-lg shadow-sm">Confirm Disbursement</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Pay Salary Modal -->
    <div x-show="payModal" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4" style="display: none;">
        <div class="bg-white dark:bg-slate-900 rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-slate-200 dark:border-slate-800 space-y-4">
            <h3 class="text-lg font-bold text-slate-900 dark:text-white">Disburse Monthly Salary</h3>
            <p class="text-xs text-slate-500">Paying salary for <span class="font-bold text-slate-800 dark:text-white" x-text="selectedEmp ? selectedEmp.name : ''"></span> ({{ date('F Y', mktime(0, 0, 0, $month, 1, $year)) }})</p>
            <form action="{{ route('payroll.pay') }}" method="POST" class="space-y-4">
                @csrf
                <input type="hidden" name="employee_id" :value="selectedEmp ? selectedEmp.id : ''">
                <input type="hidden" name="month" value="{{ $month }}">
                <input type="hidden" name="year" value="{{ $year }}">

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Bonus (৳)</label>
                        <input type="number" step="0.01" min="0" name="bonus" value="0.00" class="w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 text-xs font-mono font-medium">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Other Deduction (৳)</label>
                        <input type="number" step="0.01" min="0" name="deduction" value="0.00" class="w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 text-xs font-mono font-medium">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Payment Method *</label>
                    <select name="payment_method" required class="w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 text-xs text-slate-900 dark:text-white">
                        <option value="bank">Bank Transfer</option>
                        <option value="cash">Cash Drawer</option>
                        <option value="bkash">bKash</option>
                        <option value="nagad">Nagad</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Financial Account</label>
                    <select name="financial_account_id" class="w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 text-xs text-slate-900 dark:text-white">
                        <option value="">None / Cash Register</option>
                        @foreach($financialAccounts as $acc)
                            <option value="{{ $acc->id }}">{{ $acc->account_name }} (৳{{ number_format($acc->current_balance, 2) }})</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Remarks</label>
                    <input type="text" name="notes" placeholder="e.g. Standard monthly disbursement" class="w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 text-xs text-slate-900 dark:text-white">
                </div>

                <div class="flex items-center justify-end gap-3 pt-3">
                    <button type="button" @click="payModal = false" class="px-4 py-2 text-xs font-medium text-slate-600 hover:text-slate-800">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold rounded-lg shadow-sm">Disburse Salary</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
