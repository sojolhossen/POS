@extends('layouts.app')

@section('title', 'Executive Operations Dashboard')

@section('content')
<div class="space-y-6">
    <!-- Top Bar -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">Executive Enterprise Dashboard</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">Consolidated retail operations, net profit metrics, cash drawer health, and pending approvals.</p>
        </div>
        <div class="flex items-center gap-3">
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-300">
                <span class="w-2 h-2 rounded-full bg-emerald-500 mr-2 animate-pulse"></span>
                System Live • {{ count($stores) }} Active Stores
            </span>
        </div>
    </div>

    <!-- Alert Banner if items require attention -->
    @if($pendingExpensesCount > 0 || $openCashRegisters->count() > 0 || $pendingTransfersCount > 0)
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            @if($pendingExpensesCount > 0)
                <a href="{{ route('expenses.index') }}" class="p-4 rounded-xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-900/50 flex items-center justify-between hover:bg-amber-100/50 transition">
                    <div>
                        <span class="text-xs font-bold uppercase tracking-wider text-amber-800 dark:text-amber-300">Pending Expenses</span>
                        <div class="text-xl font-bold text-amber-900 dark:text-amber-100 mt-0.5">{{ $pendingExpensesCount }} awaiting approval</div>
                    </div>
                    <span class="text-amber-600 dark:text-amber-400 text-sm font-semibold">Review &rarr;</span>
                </a>
            @endif
            @if($pendingTransfersCount > 0)
                <a href="{{ route('transfers.index') }}" class="p-4 rounded-xl bg-indigo-50 dark:bg-indigo-950/40 border border-indigo-200 dark:border-indigo-900/50 flex items-center justify-between hover:bg-indigo-100/50 transition">
                    <div>
                        <span class="text-xs font-bold uppercase tracking-wider text-indigo-800 dark:text-indigo-300">Stock Transfers</span>
                        <div class="text-xl font-bold text-indigo-900 dark:text-indigo-100 mt-0.5">{{ $pendingTransfersCount }} in-transit</div>
                    </div>
                    <span class="text-indigo-600 dark:text-indigo-400 text-sm font-semibold">Track &rarr;</span>
                </a>
            @endif
            @if($lowStockCount > 0)
                <a href="{{ route('reports.slow_moving') }}" class="p-4 rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-900/50 flex items-center justify-between hover:bg-rose-100/50 transition">
                    <div>
                        <span class="text-xs font-bold uppercase tracking-wider text-rose-800 dark:text-rose-300">Stock Alerts</span>
                        <div class="text-xl font-bold text-rose-900 dark:text-rose-100 mt-0.5">{{ $lowStockCount }} items low stock</div>
                    </div>
                    <span class="text-rose-600 dark:text-rose-400 text-sm font-semibold">Audit &rarr;</span>
                </a>
            @endif
        </div>
    @endif

    <!-- High-Level Financial Performance Metrics (This Month) -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-slate-900 p-5 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm">
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Net Sales Revenue</span>
            <div class="text-2xl font-bold font-mono text-slate-900 dark:text-white mt-1">৳{{ number_format($financials['net_revenue'], 2) }}</div>
            <span class="text-xs text-slate-400">Current Month (Net of returns & 5% VAT)</span>
        </div>
        <div class="bg-white dark:bg-slate-900 p-5 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm">
            <span class="text-xs font-semibold text-emerald-600 uppercase tracking-wider">Gross Profit</span>
            <div class="text-2xl font-bold font-mono text-emerald-600 dark:text-emerald-400 mt-1">৳{{ number_format($financials['gross_profit'], 2) }}</div>
            <span class="text-xs text-emerald-500 font-semibold">{{ $financials['gross_margin_percent'] }}% Margin (WAC Basis)</span>
        </div>
        <div class="bg-white dark:bg-slate-900 p-5 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm">
            <span class="text-xs font-semibold text-indigo-600 uppercase tracking-wider">Operating Profit</span>
            <div class="text-2xl font-bold font-mono {{ $financials['operating_profit'] >= 0 ? 'text-indigo-600 dark:text-indigo-400' : 'text-rose-600' }} mt-1">
                ৳{{ number_format($financials['operating_profit'], 2) }}
            </div>
            <span class="text-xs text-slate-400">After Operating Expenses</span>
        </div>
        <div class="bg-white dark:bg-slate-900 p-5 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm">
            <span class="text-xs font-semibold text-rose-600 uppercase tracking-wider">Supplier Outstanding Dues</span>
            <div class="text-2xl font-bold font-mono text-rose-600 dark:text-rose-400 mt-1">৳{{ number_format($supplierDues, 2) }}</div>
            <span class="text-xs text-slate-400">Trade Accounts Payable</span>
        </div>
    </div>

    <!-- Account Balances & Cash Register Reconciliation -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Financial Accounts Overview -->
        <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm p-6 space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="text-base font-bold text-slate-900 dark:text-white">Chart of Accounts & Liquid Reserves</h3>
                <a href="{{ route('financial-accounts.index') }}" class="text-xs font-semibold text-emerald-600 hover:text-emerald-700">Manage Accounts &rarr;</a>
            </div>
            <div class="divide-y divide-slate-100 dark:divide-slate-800">
                @forelse($financialAccounts as $acc)
                    <div class="py-3 flex items-center justify-between">
                        <div>
                            <div class="font-semibold text-sm text-slate-900 dark:text-white">{{ $acc->account_name }}</div>
                            <div class="text-xs text-slate-400 uppercase">{{ $acc->account_type }} • {{ $acc->account_number ?? 'Primary' }}</div>
                        </div>
                        <div class="text-right">
                            <div class="font-mono font-bold text-sm text-slate-900 dark:text-white">৳{{ number_format($acc->current_balance, 2) }}</div>
                            <div class="text-[10px] text-slate-400">Reconciled</div>
                        </div>
                    </div>
                @empty
                    <div class="py-6 text-center text-xs text-slate-400">No financial accounts initialized.</div>
                @endforelse
            </div>
        </div>

        <!-- Store Daily Cash Closing Status -->
        <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm p-6 space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="text-base font-bold text-slate-900 dark:text-white">Active Daily Cash Drawers</h3>
                <a href="{{ route('reports.cash') }}" class="text-xs font-semibold text-emerald-600 hover:text-emerald-700">Reconciliation Report &rarr;</a>
            </div>
            <div class="divide-y divide-slate-100 dark:divide-slate-800">
                @forelse($openCashRegisters as $reg)
                    <div class="py-3 flex items-center justify-between">
                        <div>
                            <div class="font-semibold text-sm text-slate-900 dark:text-white">{{ $reg->store->name ?? 'Store' }}</div>
                            <div class="text-xs text-slate-400">Cashier: {{ $reg->user->name ?? 'Unknown' }} • Opened: {{ \Carbon\Carbon::parse($reg->opened_at)->format('h:i A') }}</div>
                        </div>
                        <div class="text-right">
                            <div class="font-mono font-bold text-sm text-emerald-600 dark:text-emerald-400">৳{{ number_format($reg->cash_in_hand_expected, 2) }}</div>
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-amber-100 text-amber-800 dark:bg-amber-950/50 dark:text-amber-300">
                                Open Register
                            </span>
                        </div>
                    </div>
                @empty
                    <div class="py-6 text-center text-xs text-slate-400">All store cash registers have successfully closed for today.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
