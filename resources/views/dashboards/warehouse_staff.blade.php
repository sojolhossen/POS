@extends('layouts.app')

@section('title', 'Logistics Operations Hub')

@section('content')
<div class="space-y-4">
    <!-- Header (Cloudflare Style) -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-slate-200 dark:border-slate-800 pb-2">
        <div class="flex items-center gap-2">
            <span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span>
            <h1 class="text-base font-bold text-slate-900 dark:text-white tracking-tight">Warehouse &amp; Dispatch Operations</h1>
            <span class="text-[10px] font-mono px-2 py-0.5 rounded border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-600 dark:text-slate-300">
                Logistics Hub
            </span>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('transfers.create') }}" class="inline-flex items-center gap-1.5 px-3 py-1 bg-emerald-600 hover:bg-emerald-700 text-white font-medium text-xs rounded-md shadow-2xs transition">
                <span>+ Create Transfer Dispatch</span>
            </a>
        </div>
    </div>

    <!-- Overview Counters (Cloudflare Stat Cards) -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
        <div class="p-3.5 rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-[#0f172a] shadow-2xs">
            <span class="text-[11px] font-medium text-amber-600 dark:text-amber-400 block">Awaiting Dispatch</span>
            <div class="flex items-baseline gap-2 mt-1">
                <span class="text-xl font-bold font-sans tracking-tight text-amber-600 dark:text-amber-400">{{ $pendingDispatches->count() }} Transfers</span>
                <span class="text-xs font-semibold text-amber-600">Pending</span>
            </div>
            <span class="text-[10px] text-slate-400 block mt-1">Staged in distribution center</span>
        </div>

        <div class="p-3.5 rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-[#0f172a] shadow-2xs">
            <span class="text-[11px] font-medium text-indigo-600 dark:text-indigo-400 block">In-Transit Freight</span>
            <div class="flex items-baseline gap-2 mt-1">
                <span class="text-xl font-bold font-sans tracking-tight text-indigo-600 dark:text-indigo-400">{{ $inTransitTransfers->count() }} Shipments</span>
                <span class="text-xs font-semibold text-indigo-600">On Route</span>
            </div>
            <span class="text-[10px] text-slate-400 block mt-1">Destination store transfers</span>
        </div>

        <div class="p-3.5 rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-[#0f172a] shadow-2xs">
            <span class="text-[11px] font-medium text-rose-600 dark:text-rose-400 block">Warehouse Low Stock</span>
            <div class="flex items-baseline gap-2 mt-1">
                <span class="text-xl font-bold font-sans tracking-tight text-rose-600 dark:text-rose-400">{{ $lowStockItems->count() }} Variants</span>
                <span class="text-xs font-semibold text-rose-600">&le; 5 units</span>
            </div>
            <span class="text-[10px] text-slate-400 block mt-1">Requisition needed</span>
        </div>
    </div>

    <!-- Active Transits Table (Cloudflare Table) -->
    <div class="rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-[#0f172a] shadow-2xs overflow-hidden p-4 space-y-3">
        <div class="flex items-center justify-between pb-2 border-b border-slate-100 dark:border-slate-800">
            <h3 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider">Active Inter-Store Stock Transfers</h3>
            <a href="{{ route('transfers.index') }}" class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline">View All &rarr;</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600 dark:text-slate-300">
                <thead class="bg-slate-50 dark:bg-slate-800/60 text-[10px] uppercase font-bold text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
                    <tr>
                        <th class="px-3 py-2">Transfer #</th>
                        <th class="px-3 py-2">Origin Warehouse</th>
                        <th class="px-3 py-2">Destination Store</th>
                        <th class="px-3 py-2 text-center">Status</th>
                        <th class="px-3 py-2 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($inTransitTransfers->merge($pendingDispatches) as $tr)
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/50 transition">
                            <td class="px-3 py-2.5 font-mono font-bold text-slate-900 dark:text-white">{{ $tr->transfer_number }}</td>
                            <td class="px-3 py-2.5 text-xs">{{ $tr->sourceStore->name ?? $tr->fromWarehouse->name ?? 'Central' }}</td>
                            <td class="px-3 py-2.5 text-xs font-medium text-slate-800 dark:text-white">{{ $tr->destinationStore->name ?? $tr->toStore->name ?? 'Store' }}</td>
                            <td class="px-3 py-2.5 text-center">
                                <span class="px-1.5 py-0.2 rounded text-[9px] font-semibold uppercase {{ $tr->status === 'in_transit' ? 'bg-indigo-100 text-indigo-800 dark:bg-indigo-950/50 dark:text-indigo-300' : 'bg-amber-100 text-amber-800' }}">
                                    {{ $tr->status }}
                                </span>
                            </td>
                            <td class="px-3 py-2.5 text-right">
                                <a href="{{ route('transfers.show', $tr) }}" class="text-indigo-600 dark:text-indigo-400 hover:underline font-medium">Inspect &rarr;</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-slate-400 text-xs">No active transfer dispatches found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
