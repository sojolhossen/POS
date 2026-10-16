@extends('layouts.app')

@section('title', 'Cross-Store Comparative Performance')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">Multi-Store Performance Comparison</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">Benchmarking store revenues, WAC cost of sales, operating margins, and order throughput.</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('reports.export', ['type' => 'comparison', 'start_date' => $startDate, 'end_date' => $endDate]) }}" class="inline-flex items-center px-4 py-2 bg-slate-800 hover:bg-slate-700 text-white font-medium text-xs rounded-lg shadow-sm">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Export CSV
            </a>
        </div>
    </div>

    <!-- Date Filter -->
    <div class="bg-white dark:bg-slate-900 p-4 rounded-xl border border-slate-200 dark:border-slate-800">
        <form method="GET" action="{{ route('reports.store_comparison') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
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
                    Compare Stores
                </button>
            </div>
        </form>
    </div>

    <!-- Store Comparison Table -->
    <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600 dark:text-slate-300">
                <thead class="bg-slate-50 dark:bg-slate-800/60 text-xs uppercase font-semibold text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
                    <tr>
                        <th class="px-6 py-3">Store</th>
                        <th class="px-6 py-3 text-center">Orders</th>
                        <th class="px-6 py-3 text-right">Net Revenue</th>
                        <th class="px-6 py-3 text-right">COGS (WAC)</th>
                        <th class="px-6 py-3 text-right">Gross Profit</th>
                        <th class="px-6 py-3 text-right">Expenses</th>
                        <th class="px-6 py-3 text-right">Operating Profit</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                    @forelse($comparisons as $st)
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/50 transition">
                            <td class="px-6 py-4">
                                <div class="font-semibold text-slate-900 dark:text-white">{{ $st['store_name'] }}</div>
                                <div class="text-xs text-slate-400 font-mono">{{ $st['store_code'] }}</div>
                            </td>
                            <td class="px-6 py-4 text-center font-mono font-medium text-slate-700 dark:text-slate-300">
                                {{ $st['total_orders'] }}
                            </td>
                            <td class="px-6 py-4 text-right font-mono font-bold text-slate-900 dark:text-white">
                                ৳{{ number_format($st['net_revenue'], 2) }}
                            </td>
                            <td class="px-6 py-4 text-right font-mono text-slate-500">
                                ৳{{ number_format($st['cogs'], 2) }}
                            </td>
                            <td class="px-6 py-4 text-right font-mono font-bold text-emerald-600 dark:text-emerald-400">
                                ৳{{ number_format($st['gross_profit'], 2) }}
                            </td>
                            <td class="px-6 py-4 text-right font-mono text-rose-500">
                                ৳{{ number_format($st['expenses'], 2) }}
                            </td>
                            <td class="px-6 py-4 text-right font-mono font-bold {{ $st['operating_profit'] >= 0 ? 'text-indigo-600 dark:text-indigo-400' : 'text-rose-600' }}">
                                ৳{{ number_format($st['operating_profit'], 2) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-slate-500">
                                No store comparative data available.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
