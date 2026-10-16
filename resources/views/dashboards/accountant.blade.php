@extends('layouts.app')

@section('title', 'Financial Controller & Accounting Hub')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">Accountant & Financial Control Centre</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">Cash drawer reconciliations, liquid accounts, expense approvals, and supplier payables.</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('reports.financial') }}" class="inline-flex items-center px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-medium text-xs rounded-lg shadow-sm">
                Financial Statement (P&L) &rarr;
            </a>
        </div>
    </div>

    <!-- Overview Counters -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white dark:bg-slate-900 p-5 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm">
            <span class="text-xs font-semibold text-rose-600 uppercase tracking-wider">Trade Accounts Payable</span>
            <div class="text-3xl font-bold font-mono text-rose-600 dark:text-rose-400 mt-1">৳{{ number_format($supplierDues, 2) }}</div>
            <span class="text-xs text-slate-400">Total outstanding supplier bills</span>
        </div>
        <div class="bg-white dark:bg-slate-900 p-5 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm">
            <span class="text-xs font-semibold text-amber-600 uppercase tracking-wider">Pending Expense Approvals</span>
            <div class="text-3xl font-bold font-mono text-amber-600 dark:text-amber-400 mt-1">{{ $pendingExpenses->count() }} Items</div>
            <span class="text-xs text-slate-400">Requires audit confirmation</span>
        </div>
        <div class="bg-white dark:bg-slate-900 p-5 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm">
            <span class="text-xs font-semibold text-indigo-600 uppercase tracking-wider">Open Daily Cash Drawers</span>
            <div class="text-3xl font-bold font-mono text-indigo-600 dark:text-indigo-400 mt-1">{{ $todayRegisters->count() }} Stores</div>
            <span class="text-xs text-slate-400">Awaiting end-of-day closing</span>
        </div>
    </div>

    <!-- Liquid Accounts & Pending Expenses -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Accounts -->
        <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm p-6 space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="text-base font-bold text-slate-900 dark:text-white">Chart of Accounts Balances</h3>
                <a href="{{ route('financial-accounts.index') }}" class="text-xs text-emerald-600 hover:text-emerald-700">All Accounts &rarr;</a>
            </div>
            <div class="divide-y divide-slate-100 dark:divide-slate-800">
                @forelse($financialAccounts as $acc)
                    <div class="py-3 flex items-center justify-between">
                        <div>
                            <div class="font-semibold text-sm text-slate-900 dark:text-white">{{ $acc->account_name }}</div>
                            <div class="text-xs text-slate-400 uppercase font-mono">{{ $acc->account_type }}</div>
                        </div>
                        <div class="text-right">
                            <div class="font-mono font-bold text-sm text-slate-900 dark:text-white">৳{{ number_format($acc->current_balance, 2) }}</div>
                            <div class="text-[10px] text-emerald-500 font-semibold">Active</div>
                        </div>
                    </div>
                @empty
                    <div class="py-6 text-center text-xs text-slate-400">No financial accounts found.</div>
                @endforelse
            </div>
        </div>

        <!-- Pending Expenses -->
        <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm p-6 space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="text-base font-bold text-slate-900 dark:text-white">Expenses Awaiting Verification</h3>
                <a href="{{ route('expenses.index') }}" class="text-xs text-emerald-600 hover:text-emerald-700">Audit &rarr;</a>
            </div>
            <div class="divide-y divide-slate-100 dark:divide-slate-800">
                @forelse($pendingExpenses as $exp)
                    <div class="py-3 flex items-center justify-between text-xs">
                        <div>
                            <div class="font-bold text-slate-900 dark:text-white">{{ $exp->category->name ?? 'Expense' }}</div>
                            <div class="text-slate-400">{{ $exp->store->name ?? 'All Stores' }} • Logged by: {{ $exp->user->name ?? 'User' }}</div>
                        </div>
                        <div class="text-right">
                            <div class="font-mono font-bold text-amber-600">৳{{ number_format($exp->amount, 2) }}</div>
                            <span class="inline-flex px-2 py-0.5 rounded text-[10px] font-semibold bg-amber-100 text-amber-800">Pending</span>
                        </div>
                    </div>
                @empty
                    <div class="py-6 text-center text-xs text-slate-400">No pending expenses awaiting approval.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
