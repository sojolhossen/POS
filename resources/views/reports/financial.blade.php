@extends('layouts.app')

@section('title', 'Financial Profit & Loss Statement')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">Profit & Loss / Financial Performance</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">Auditable Net Revenue, Weighted Average Cost (WAC) COGS, Gross Profit, and Operating Results.</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('reports.export', ['type' => 'financial', 'start_date' => $startDate, 'end_date' => $endDate, 'store_id' => $storeId]) }}" class="inline-flex items-center px-4 py-2 bg-slate-800 hover:bg-slate-700 text-white font-medium text-xs rounded-lg shadow-sm">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Export CSV
            </a>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="bg-white dark:bg-slate-900 p-4 rounded-xl border border-slate-200 dark:border-slate-800">
        <form method="GET" action="{{ route('reports.financial') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
            @if(in_array(auth()->user()->role, ['super_admin', 'accountant']))
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Store Scope</label>
                    <select name="store_id" class="w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 text-xs text-slate-900 dark:text-white">
                        <option value="">All Stores (Consolidated)</option>
                        @foreach($stores as $st)
                            <option value="{{ $st->id }}" {{ $storeId == $st->id ? 'selected' : '' }}>{{ $st->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Start Date</label>
                <input type="date" name="start_date" value="{{ $startDate }}" class="w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 text-xs text-slate-900 dark:text-white">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">End Date</label>
                <input type="date" name="end_date" value="{{ $endDate }}" class="w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 text-xs text-slate-900 dark:text-white">
            </div>
            <div>
                <button type="submit" class="w-full px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-medium text-xs rounded-lg shadow-sm">
                    Filter Report
                </button>
            </div>
        </form>
    </div>

    <!-- Stat Highlights -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-slate-900 p-5 rounded-xl border border-slate-200 dark:border-slate-800">
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Net Sales Revenue</span>
            <div class="text-2xl font-bold font-mono text-slate-900 dark:text-white mt-1">৳{{ number_format($report['net_revenue'], 2) }}</div>
            <span class="text-xs text-slate-400">Excludes statutory 5% VAT</span>
        </div>
        <div class="bg-white dark:bg-slate-900 p-5 rounded-xl border border-slate-200 dark:border-slate-800">
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Cost of Goods Sold (COGS)</span>
            <div class="text-2xl font-bold font-mono text-slate-700 dark:text-slate-300 mt-1">৳{{ number_format($report['cogs'], 2) }}</div>
            <span class="text-xs text-slate-400">Weighted Average Cost (WAC)</span>
        </div>
        <div class="bg-white dark:bg-slate-900 p-5 rounded-xl border border-slate-200 dark:border-slate-800">
            <span class="text-xs font-semibold text-emerald-600 uppercase tracking-wider">Gross Profit</span>
            <div class="text-2xl font-bold font-mono text-emerald-600 dark:text-emerald-400 mt-1">৳{{ number_format($report['gross_profit'], 2) }}</div>
            <span class="text-xs text-emerald-500 font-semibold">{{ $report['gross_margin_percent'] }}% Margin</span>
        </div>
        <div class="bg-white dark:bg-slate-900 p-5 rounded-xl border border-slate-200 dark:border-slate-800">
            <span class="text-xs font-semibold text-indigo-600 uppercase tracking-wider">Operating Profit</span>
            <div class="text-2xl font-bold font-mono {{ $report['operating_profit'] >= 0 ? 'text-indigo-600 dark:text-indigo-400' : 'text-rose-600' }} mt-1">
                ৳{{ number_format($report['operating_profit'], 2) }}
            </div>
            <span class="text-xs text-slate-400">After approved operating expenses</span>
        </div>
    </div>

    <!-- Structured Accounting Breakdown Table -->
    <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden p-6 space-y-6">
        <h2 class="text-lg font-bold text-slate-900 dark:text-white border-b border-slate-200 dark:border-slate-800 pb-3">Financial Statement Breakdown</h2>
        
        <div class="space-y-3 font-mono text-sm max-w-2xl">
            <div class="flex justify-between items-center py-2 border-b border-slate-100 dark:border-slate-800">
                <span class="text-slate-700 dark:text-slate-300 font-sans font-medium">Gross Sales (Posted Orders)</span>
                <span class="font-bold text-slate-900 dark:text-white">৳{{ number_format($report['gross_sales'], 2) }}</span>
            </div>
            <div class="flex justify-between items-center py-2 border-b border-slate-100 dark:border-slate-800 text-rose-600">
                <span class="font-sans font-medium">Less: Customer Sales Returns / Refunds</span>
                <span>- ৳{{ number_format($report['returns'], 2) }}</span>
            </div>
            <div class="flex justify-between items-center py-2 border-b-2 border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/40 px-3 rounded">
                <span class="text-slate-900 dark:text-white font-sans font-bold">Net Sales Revenue</span>
                <span class="font-bold text-slate-900 dark:text-white">৳{{ number_format($report['net_revenue'], 2) }}</span>
            </div>
            <div class="flex justify-between items-center py-2 border-b border-slate-100 dark:border-slate-800 text-rose-600">
                <span class="font-sans font-medium">Less: Cost of Goods Sold (Inventory Outflow at WAC)</span>
                <span>- ৳{{ number_format($report['cogs'], 2) }}</span>
            </div>
            <div class="flex justify-between items-center py-2 border-b-2 border-emerald-300 dark:border-emerald-800 bg-emerald-50 dark:bg-emerald-950/30 px-3 rounded">
                <span class="text-emerald-900 dark:text-emerald-300 font-sans font-bold">Gross Profit</span>
                <span class="font-bold text-emerald-600 dark:text-emerald-400">৳{{ number_format($report['gross_profit'], 2) }}</span>
            </div>
            <div class="flex justify-between items-center py-2 border-b border-slate-100 dark:border-slate-800 text-rose-600">
                <span class="font-sans font-medium">Less: Approved Operating Expenses (Rent, Salary, Utilities, etc.)</span>
                <span>- ৳{{ number_format($report['expenses'], 2) }}</span>
            </div>
            <div class="flex justify-between items-center py-3 border-b-2 border-indigo-400 dark:border-indigo-700 bg-indigo-50 dark:bg-indigo-950/40 px-3 rounded text-base">
                <span class="text-indigo-950 dark:text-indigo-200 font-sans font-extrabold">Operating Profit / Net Result</span>
                <span class="font-extrabold {{ $report['operating_profit'] >= 0 ? 'text-indigo-700 dark:text-indigo-300' : 'text-rose-600' }}">৳{{ number_format($report['operating_profit'], 2) }}</span>
            </div>
        </div>

        <!-- Statutory VAT Section (Explicitly separated from business revenue) -->
        <div class="mt-8 p-4 rounded-xl bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-900/50 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h4 class="text-sm font-bold text-amber-900 dark:text-amber-200">Statutory Tax & VAT Collected (Liability)</h4>
                <p class="text-xs text-amber-700 dark:text-amber-400">Collected on behalf of the National Board of Revenue (NBR). Excluded from sales revenue and operating profit.</p>
            </div>
            <div class="text-xl font-bold font-mono text-amber-900 dark:text-amber-200">
                ৳{{ number_format($report['vat_collected'], 2) }}
            </div>
        </div>
    </div>
</div>
@endsection
