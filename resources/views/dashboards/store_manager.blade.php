@extends('layouts.app')

@section('title', 'Store Operations Hub')

@section('content')
<div class="space-y-4">
    <!-- Sub-Header / Location Title Bar (Cloudflare Style) -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-slate-200 dark:border-slate-800 pb-2">
        <div class="flex items-center gap-2">
            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
            <h1 class="text-base font-bold text-slate-900 dark:text-white tracking-tight">{{ $store->name }}</h1>
            <span class="text-[10px] font-mono px-2 py-0.5 rounded border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-600 dark:text-slate-300">
                Code: {{ $store->code }}
            </span>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('pos.index') }}" class="inline-flex items-center gap-1.5 px-3 py-1 bg-emerald-600 hover:bg-emerald-700 text-white font-medium text-xs rounded-md shadow-2xs transition">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                <span>Launch POS Terminal</span>
            </a>
            <a href="{{ route('expenses.create') }}" class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium border border-slate-200 dark:border-slate-700 rounded-md bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700/60 text-slate-700 dark:text-slate-200 shadow-2xs transition">
                <span>+ Log Expense</span>
            </a>
        </div>
    </div>

    <!-- Active Drawer Status (Compact Cloudflare Card) -->
    <div class="p-3.5 rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-[#0f172a] shadow-2xs flex flex-col md:flex-row md:items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-lg bg-emerald-50 dark:bg-emerald-950/50 border border-emerald-200 dark:border-emerald-800 text-emerald-600 dark:text-emerald-400 flex items-center justify-center font-bold">
                ৳
            </div>
            <div>
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Current Business Session Drawer</span>
                @if($cashRegister)
                    <div class="flex items-center gap-2 mt-0.5">
                        <span class="text-xl font-bold font-mono text-emerald-600 dark:text-emerald-400">
                            ৳{{ number_format($cashRegister->cash_in_hand_expected, 2) }}
                        </span>
                        <span class="px-2 py-0.2 rounded-full text-[10px] font-semibold {{ $cashRegister->status === 'closed' ? 'bg-slate-100 text-slate-700' : 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-300' }}">
                            {{ ucfirst($cashRegister->status) }}
                        </span>
                    </div>
                    <p class="text-[11px] text-slate-500">Opened by {{ $cashRegister->user->name ?? 'Staff' }} at {{ \Carbon\Carbon::parse($cashRegister->opened_at)->format('h:i A') }}</p>
                @else
                    <div class="text-sm font-bold text-amber-600 mt-0.5">No active register session for today.</div>
                    <p class="text-[11px] text-slate-500">Cashier needs to perform daily cash opening.</p>
                @endif
            </div>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('attendance.index') }}" class="px-2.5 py-1 border border-slate-200 dark:border-slate-700 rounded-md bg-slate-50 dark:bg-slate-800 hover:bg-slate-100 text-slate-700 dark:text-slate-200 text-xs font-medium transition">
                Attendance Log
            </a>
            <a href="{{ route('daily-registers.index') }}" class="px-2.5 py-1 border border-slate-200 dark:border-slate-700 rounded-md bg-slate-50 dark:bg-slate-800 hover:bg-slate-100 text-slate-700 dark:text-slate-200 text-xs font-medium transition">
                Register Details
            </a>
        </div>
    </div>

    <!-- Store KPI Metrics (Cloudflare Stat Cards) -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
        <div class="p-3.5 rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-[#0f172a] shadow-2xs">
            <span class="text-[11px] font-medium text-slate-500 dark:text-slate-400 block">Store Net Revenue</span>
            <div class="flex items-baseline gap-2 mt-1">
                <span class="text-xl font-bold font-sans tracking-tight text-slate-900 dark:text-white">৳{{ number_format($financials['net_revenue'], 2) }}</span>
                <span class="text-xs font-semibold text-emerald-600 dark:text-emerald-400">&nearr; 11.4%</span>
            </div>
            <span class="text-[10px] text-slate-400 block mt-1">Month to date (Excl. VAT)</span>
        </div>
        <div class="p-3.5 rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-[#0f172a] shadow-2xs">
            <span class="text-[11px] font-medium text-slate-500 dark:text-slate-400 block">Store Gross Profit</span>
            <div class="flex items-baseline gap-2 mt-1">
                <span class="text-xl font-bold font-sans tracking-tight text-emerald-600 dark:text-emerald-400">৳{{ number_format($financials['gross_profit'], 2) }}</span>
                <span class="text-xs font-semibold text-emerald-600 dark:text-emerald-400">{{ $financials['gross_margin_percent'] }}% margin</span>
            </div>
            <span class="text-[10px] text-slate-400 block mt-1">Weighted Average Cost (WAC)</span>
        </div>
        <div class="p-3.5 rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-[#0f172a] shadow-2xs">
            <span class="text-[11px] font-medium text-slate-500 dark:text-slate-400 block">Operating Expenses</span>
            <div class="flex items-baseline gap-2 mt-1">
                <span class="text-xl font-bold font-sans tracking-tight text-rose-600 dark:text-rose-400">৳{{ number_format($financials['expenses'], 2) }}</span>
                <span class="text-xs font-semibold text-slate-500">Overhead</span>
            </div>
            <span class="text-[10px] text-slate-400 block mt-1">Local store petty expenses</span>
        </div>
    </div>

    <!-- Store Lists -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <!-- Low Stock Alerts -->
        <div class="rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-[#0f172a] shadow-2xs p-4 space-y-3">
            <div class="flex items-center justify-between pb-2 border-b border-slate-100 dark:border-slate-800">
                <h3 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider">Store Low Stock Warnings</h3>
                <span class="text-xs text-rose-600 font-semibold">{{ $lowStockItems->count() }} items</span>
            </div>
            <div class="divide-y divide-slate-100 dark:divide-slate-800">
                @forelse($lowStockItems as $st)
                    <div class="py-2.5 flex items-center justify-between text-xs">
                        <div>
                            <span class="font-semibold text-slate-800 dark:text-slate-200">{{ $st->productVariant->product->name ?? 'Item' }}</span>
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
        <div class="rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-[#0f172a] shadow-2xs p-4 space-y-3">
            <div class="flex items-center justify-between pb-2 border-b border-slate-100 dark:border-slate-800">
                <h3 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider">Incoming Stock Transfers</h3>
                <a href="{{ route('transfers.index') }}" class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline">View All &rarr;</a>
            </div>
            <div class="divide-y divide-slate-100 dark:divide-slate-800">
                @forelse($pendingTransfers as $tr)
                    <div class="py-2.5 flex items-center justify-between text-xs">
                        <div>
                            <span class="font-bold font-mono text-slate-900 dark:text-white">{{ $tr->transfer_number }}</span>
                            <div class="text-[10px] text-slate-400">From: {{ $tr->sourceStore->name ?? 'Warehouse' }}</div>
                        </div>
                        <div class="text-right">
                            <span class="px-2 py-0.5 rounded text-[10px] font-semibold uppercase {{ $tr->status === 'in_transit' ? 'bg-indigo-100 text-indigo-800 dark:bg-indigo-950/50 dark:text-indigo-300' : 'bg-slate-100 text-slate-800' }}">
                                {{ $tr->status }}
                            </span>
                        </div>
                    </div>
                @empty
                    <div class="py-6 text-center text-xs text-slate-400">No incoming stock transfers pending.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
