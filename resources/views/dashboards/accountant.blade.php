@extends('layouts.app')

@section('title', 'Financial Controller Hub')

@section('content')
<div class="space-y-4">
    <!-- Header (Cloudflare Style) -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-slate-200 dark:border-slate-800 pb-2">
        <div class="flex items-center gap-2">
            <span class="w-2.5 h-2.5 rounded-full bg-indigo-500"></span>
            <h1 class="text-base font-bold text-slate-900 dark:text-white tracking-tight">Accountant &amp; Financial Control Centre</h1>
            <span class="text-[10px] font-mono px-2 py-0.5 rounded border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-600 dark:text-slate-300">
                Audit Status: Active
            </span>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('reports.financial') }}" class="inline-flex items-center gap-1.5 px-3 py-1 bg-emerald-600 hover:bg-emerald-700 text-white font-medium text-xs rounded-md shadow-2xs transition">
                <span>View P&amp;L Statement &rarr;</span>
            </a>
        </div>
    </div>

    <!-- Overview Counters (Cloudflare Stat Cards) -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
        <div class="p-3.5 rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-[#0f172a] shadow-2xs">
            <span class="text-[11px] font-medium text-rose-600 dark:text-rose-400 block">Trade Accounts Payable</span>
            <div class="flex items-baseline gap-2 mt-1">
                <span class="text-xl font-bold font-sans tracking-tight text-rose-600 dark:text-rose-400">৳{{ number_format($supplierDues, 2) }}</span>
                <span class="text-xs font-semibold text-slate-500">Payables</span>
            </div>
            <span class="text-[10px] text-slate-400 block mt-1">Total outstanding supplier invoices</span>
        </div>

        <div class="p-3.5 rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-[#0f172a] shadow-2xs">
            <span class="text-[11px] font-medium text-amber-600 dark:text-amber-400 block">Pending Expense Approvals</span>
            <div class="flex items-baseline gap-2 mt-1">
                <span class="text-xl font-bold font-sans tracking-tight text-amber-600 dark:text-amber-400">{{ $pendingExpenses->count() }} Items</span>
                <span class="text-xs font-semibold text-amber-600">Review</span>
            </div>
            <span class="text-[10px] text-slate-400 block mt-1">Requires financial authorization</span>
        </div>

        <div class="p-3.5 rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-[#0f172a] shadow-2xs">
            <span class="text-[11px] font-medium text-indigo-600 dark:text-indigo-400 block">Open Daily Cash Drawers</span>
            <div class="flex items-baseline gap-2 mt-1">
                <span class="text-xl font-bold font-sans tracking-tight text-indigo-600 dark:text-indigo-400">{{ $todayRegisters->count() }} Stores</span>
                <span class="text-xs font-semibold text-indigo-600">Active</span>
            </div>
            <span class="text-[10px] text-slate-400 block mt-1">Awaiting physical reconciliation</span>
        </div>
    </div>

    <!-- Liquid Accounts & Pending Expenses (Cloudflare Cards) -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <!-- Accounts -->
        <div class="rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-[#0f172a] shadow-2xs p-4 space-y-3">
            <div class="flex items-center justify-between pb-2 border-b border-slate-100 dark:border-slate-800">
                <h3 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider">Chart of Accounts Balances</h3>
                <a href="{{ route('financial-accounts.index') }}" class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline">All Accounts &rarr;</a>
            </div>
            <div class="divide-y divide-slate-100 dark:divide-slate-800 text-xs">
                @forelse($financialAccounts as $acc)
                    <div class="py-2.5 flex items-center justify-between">
                        <div>
                            <div class="font-semibold text-slate-800 dark:text-slate-100">{{ $acc->account_name }}</div>
                            <div class="text-[10px] text-slate-400 uppercase font-mono">{{ $acc->account_type }}</div>
                        </div>
                        <div class="text-right">
                            <div class="font-mono font-bold text-slate-900 dark:text-white">৳{{ number_format($acc->current_balance, 2) }}</div>
                            <span class="text-[9px] text-emerald-600 dark:text-emerald-400 font-semibold">Active &bull; Reconciled</span>
                        </div>
                    </div>
                @empty
                    <div class="py-6 text-center text-slate-400 text-xs">No financial accounts found.</div>
                @endforelse
            </div>
        </div>

        <!-- Pending Expenses -->
        <div class="rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-[#0f172a] shadow-2xs p-4 space-y-3">
            <div class="flex items-center justify-between pb-2 border-b border-slate-100 dark:border-slate-800">
                <h3 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider">Pending Store Expenses</h3>
                <a href="{{ route('expenses.index') }}" class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline">Review All &rarr;</a>
            </div>
            <div class="divide-y divide-slate-100 dark:divide-slate-800 text-xs">
                @forelse($pendingExpenses as $exp)
                    <div class="py-2.5 flex items-center justify-between">
                        <div>
                            <div class="font-semibold text-slate-800 dark:text-slate-100">{{ $exp->category->name ?? 'Expense' }}</div>
                            <div class="text-[10px] text-slate-400">Store: {{ $exp->store->name ?? 'Central' }} &bull; {{ $exp->expense_date }}</div>
                        </div>
                        <div class="text-right">
                            <div class="font-mono font-bold text-rose-600">৳{{ number_format($exp->amount, 2) }}</div>
                            <span class="px-1.5 py-0.2 rounded text-[9px] font-semibold bg-amber-100 text-amber-800 dark:bg-amber-950/50 dark:text-amber-300">Awaiting Approval</span>
                        </div>
                    </div>
                @empty
                    <div class="py-6 text-center text-slate-400 text-xs">No pending expense requests require audit.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
