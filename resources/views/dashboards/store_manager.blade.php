@extends('layouts.app')

@section('title', 'Store Manager Dashboard')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">{{ $store->name }} — Operations Dashboard</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">Store-level sales performance, daily drawer status, staff attendance, and local stock alerts.</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('pos.index') }}" class="inline-flex items-center px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-medium text-xs rounded-lg shadow-sm">
                Open POS Terminal &rarr;
            </a>
        </div>
    </div>

    <!-- Store Daily Cash Status Banner -->
    <div class="bg-white dark:bg-slate-900 p-6 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="space-y-1">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Current Business Day Cash Drawer</span>
            @if($cashRegister)
                <div class="flex items-center gap-3">
                    <div class="text-2xl font-bold font-mono text-emerald-600 dark:text-emerald-400">
                        ৳{{ number_format($cashRegister->cash_in_hand_expected, 2) }}
                    </div>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $cashRegister->status === 'closed' ? 'bg-slate-100 text-slate-700' : 'bg-emerald-100 text-emerald-800' }}">
                        {{ ucfirst($cashRegister->status) }}
                    </span>
                </div>
                <p class="text-xs text-slate-400">Opened by {{ $cashRegister->user->name ?? 'Staff' }} at {{ \Carbon\Carbon::parse($cashRegister->opened_at)->format('h:i A') }}</p>
            @else
                <div class="text-lg font-bold text-amber-600">No active register session for today.</div>
                <p class="text-xs text-slate-400">Cashier needs to perform daily cash opening.</p>
            @endif
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('attendance.index') }}" class="px-3 py-2 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 text-slate-700 dark:text-slate-200 text-xs font-medium rounded-lg">
                Log Staff Attendance
            </a>
            <a href="{{ route('expenses.create') }}" class="px-3 py-2 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 text-slate-700 dark:text-slate-200 text-xs font-medium rounded-lg">
                Log Store Expense
            </a>
        </div>
    </div>

    <!-- Store KPI Metrics -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white dark:bg-slate-900 p-5 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm">
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Store Net Revenue</span>
            <div class="text-2xl font-bold font-mono text-slate-900 dark:text-white mt-1">৳{{ number_format($financials['net_revenue'], 2) }}</div>
            <span class="text-xs text-slate-400">Month to date (Excl. VAT)</span>
        </div>
        <div class="bg-white dark:bg-slate-900 p-5 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm">
            <span class="text-xs font-semibold text-emerald-600 uppercase tracking-wider">Store Gross Profit</span>
            <div class="text-2xl font-bold font-mono text-emerald-600 dark:text-emerald-400 mt-1">৳{{ number_format($financials['gross_profit'], 2) }}</div>
            <span class="text-xs text-emerald-500">{{ $financials['gross_margin_percent'] }}% Margin</span>
        </div>
        <div class="bg-white dark:bg-slate-900 p-5 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm">
            <span class="text-xs font-semibold text-rose-600 uppercase tracking-wider">Store Operating Expenses</span>
            <div class="text-2xl font-bold font-mono text-rose-600 dark:text-rose-400 mt-1">৳{{ number_format($financials['expenses'], 2) }}</div>
            <span class="text-xs text-slate-400">Approved operational overhead</span>
        </div>
    </div>

    <!-- Store Lists -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Low Stock Alerts -->
        <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm p-6 space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="text-base font-bold text-slate-900 dark:text-white">Store Low Stock Warnings</h3>
                <span class="text-xs text-rose-600 font-semibold">{{ $lowStockItems->count() }} items</span>
            </div>
            <div class="divide-y divide-slate-100 dark:divide-slate-800">
                @forelse($lowStockItems as $st)
                    <div class="py-2.5 flex items-center justify-between text-xs">
                        <div>
                            <span class="font-bold text-slate-900 dark:text-white">{{ $st->productVariant->product->name ?? 'Item' }}</span>
                            <span class="text-slate-400">({{ $st->productVariant->sku ?? '' }})</span>
                        </div>
                        <span class="font-mono font-bold text-rose-600">{{ $st->quantity }} remaining</span>
                    </div>
                @empty
                    <div class="py-6 text-center text-xs text-slate-400">Inventory levels are healthy across all variants.</div>
                @endforelse
            </div>
        </div>

        <!-- In-Transit / Pending Transfers -->
        <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm p-6 space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="text-base font-bold text-slate-900 dark:text-white">Incoming Stock Transfers</h3>
                <a href="{{ route('transfers.index') }}" class="text-xs text-emerald-600 hover:text-emerald-700">View All &rarr;</a>
            </div>
            <div class="divide-y divide-slate-100 dark:divide-slate-800">
                @forelse($pendingTransfers as $tr)
                    <div class="py-2.5 flex items-center justify-between text-xs">
                        <div>
                            <span class="font-bold font-mono text-slate-900 dark:text-white">{{ $tr->transfer_number }}</span>
                            <span class="text-slate-400">From: {{ $tr->fromWarehouse->name ?? 'Central' }}</span>
                        </div>
                        <span class="inline-flex px-2 py-0.5 rounded text-[10px] font-semibold bg-indigo-100 text-indigo-800 dark:bg-indigo-950/50 dark:text-indigo-300">
                            {{ ucfirst($tr->status) }}
                        </span>
                    </div>
                @empty
                    <div class="py-6 text-center text-xs text-slate-400">No incoming stock transfers pending.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
