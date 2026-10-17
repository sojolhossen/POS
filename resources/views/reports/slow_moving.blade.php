@extends('layouts.app')

@section('title', 'Slow-Moving & Aging Inventory')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">Aging & Slow-Moving Stock</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">Identify stale merchandise, inventory age, capital tied up, and clearance opportunities.</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('reports.export', ['type' => 'slow_moving', 'days' => $daysThreshold, 'store_id' => $storeId]) }}" class="inline-flex items-center px-4 py-2 bg-slate-800 hover:bg-slate-700 text-white font-medium text-xs rounded-lg shadow-sm">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Export CSV
            </a>
        </div>
    </div>

    <!-- Filter -->
    <div class="bg-white dark:bg-slate-900 p-4 rounded-xl border border-slate-200 dark:border-slate-800">
        <form method="GET" action="{{ route('reports.slow_moving') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
            @if(in_array(auth()->user()->role, ['super_admin', 'accountant']))
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Store Scope</label>
                    <select name="store_id" class="w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 text-xs text-slate-900 dark:text-white">
                        <option value="">All Stores</option>
                        @foreach($stores as $st)
                            <option value="{{ $st->id }}" {{ $storeId == $st->id ? 'selected' : '' }}>{{ $st->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Days Without Sale</label>
                <select name="days" class="w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 text-xs text-slate-900 dark:text-white">
                    <option value="30" {{ $daysThreshold == 30 ? 'selected' : '' }}>30+ Days</option>
                    <option value="60" {{ $daysThreshold == 60 ? 'selected' : '' }}>60+ Days</option>
                    <option value="90" {{ $daysThreshold == 90 ? 'selected' : '' }}>90+ Days (Quarterly Stagnant)</option>
                    <option value="180" {{ $daysThreshold == 180 ? 'selected' : '' }}>180+ Days (Severe Deadstock)</option>
                </select>
            </div>
            <div>
                <button type="submit" class="w-full px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-medium text-xs rounded-lg shadow-sm">
                    Analyze Aging
                </button>
            </div>
        </form>
    </div>

    <!-- Items Table -->
    <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600 dark:text-slate-300">
                <thead class="bg-slate-50 dark:bg-slate-800/60 text-xs uppercase font-semibold text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
                    <tr>
                        <th class="px-6 py-3">Product / SKU</th>
                        <th class="px-6 py-3">Category</th>
                        <th class="px-6 py-3 text-right">In Stock Qty</th>
                        <th class="px-6 py-3 text-right">Unit Cost (WAC)</th>
                        <th class="px-6 py-3 text-right">Capital Tied Up</th>
                        <th class="px-6 py-3 text-right">Last Sale</th>
                        <th class="px-6 py-3 text-center">Days Stagnant</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                    @forelse($items as $item)
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/50 transition">
                            <td class="px-6 py-4">
                                <div class="font-semibold text-slate-900 dark:text-white">{{ $item['name'] ?? $item['product_name'] ?? 'Unknown Product' }}</div>
                                <div class="text-xs text-slate-400 font-mono">{{ $item['sku'] ?? 'N/A' }}</div>
                            </td>
                            <td class="px-6 py-4 text-xs">{{ $item['category'] ?? 'General' }}</td>
                            <td class="px-6 py-4 text-right font-mono font-bold text-slate-900 dark:text-white">{{ $item['current_stock'] ?? $item['quantity_on_hand'] ?? 0 }}</td>
                            <td class="px-6 py-4 text-right font-mono">৳{{ number_format($item['unit_cost'] ?? 0, 2) }}</td>
                            <td class="px-6 py-4 text-right font-mono font-bold text-amber-600 dark:text-amber-400">৳{{ number_format($item['capital_tied_up'] ?? $item['tied_up_capital'] ?? 0, 2) }}</td>
                            <td class="px-6 py-4 text-right text-xs text-slate-400">{{ $item['last_sale_date'] ?? 'Never' }}</td>
                            <td class="px-6 py-4 text-center">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold
                                    {{ ($item['days_without_sale'] ?? $daysThreshold) >= 90 ? 'bg-rose-100 text-rose-800 dark:bg-rose-950/50 dark:text-rose-300' : 'bg-amber-100 text-amber-800 dark:bg-amber-950/50 dark:text-amber-300' }}">
                                    {{ $item['days_without_sale'] ?? $daysThreshold }} Days
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-slate-500">
                                No inventory items exceeding {{ $daysThreshold }} days without sales.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
