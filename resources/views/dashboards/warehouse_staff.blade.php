@extends('layouts.app')

@section('title', 'Warehouse & Logistics Hub')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">Warehouse & Dispatch Operations</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">Inter-store transfer dispatches, inbound shipments, and central distribution logistics.</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('transfers.create') }}" class="inline-flex items-center px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-medium text-xs rounded-lg shadow-sm">
                + Create Stock Transfer
            </a>
        </div>
    </div>

    <!-- Overview Counters -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white dark:bg-slate-900 p-5 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm">
            <span class="text-xs font-semibold text-amber-600 uppercase tracking-wider">Awaiting Dispatch</span>
            <div class="text-3xl font-bold font-mono text-amber-600 dark:text-amber-400 mt-1">{{ $pendingDispatches->count() }} Transfers</div>
            <span class="text-xs text-slate-400">Staged in warehouse</span>
        </div>
        <div class="bg-white dark:bg-slate-900 p-5 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm">
            <span class="text-xs font-semibold text-indigo-600 uppercase tracking-wider">In-Transit Freight</span>
            <div class="text-3xl font-bold font-mono text-indigo-600 dark:text-indigo-400 mt-1">{{ $inTransitTransfers->count() }} Shipments</div>
            <span class="text-xs text-slate-400">On the road to destination stores</span>
        </div>
        <div class="bg-white dark:bg-slate-900 p-5 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm">
            <span class="text-xs font-semibold text-rose-600 uppercase tracking-wider">Warehouse Low Stock</span>
            <div class="text-3xl font-bold font-mono text-rose-600 dark:text-rose-400 mt-1">{{ $lowStockItems->count() }} Variants</div>
            <span class="text-xs text-slate-400">Requires purchase requisition</span>
        </div>
    </div>

    <!-- Active Transits Table -->
    <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden p-6 space-y-4">
        <h3 class="text-base font-bold text-slate-900 dark:text-white">Active Inter-Store Stock Transfers</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600 dark:text-slate-300">
                <thead class="bg-slate-50 dark:bg-slate-800/60 text-xs uppercase font-semibold text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
                    <tr>
                        <th class="px-4 py-2.5">Transfer #</th>
                        <th class="px-4 py-2.5">Origin Warehouse</th>
                        <th class="px-4 py-2.5">Destination Store</th>
                        <th class="px-4 py-2.5 text-center">Status</th>
                        <th class="px-4 py-2.5 text-right">Date Initiated</th>
                        <th class="px-4 py-2.5 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($inTransitTransfers->merge($pendingDispatches) as $tr)
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/50 transition">
                            <td class="px-4 py-3 font-mono font-bold text-slate-900 dark:text-white">{{ $tr->transfer_number }}</td>
                            <td class="px-4 py-3 text-xs">{{ $tr->fromWarehouse->name ?? 'Central' }}</td>
                            <td class="px-4 py-3 text-xs font-medium text-slate-800 dark:text-white">{{ $tr->toStore->name ?? 'Store' }}</td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-flex px-2 py-0.5 rounded text-xs font-semibold
                                    {{ $tr->status === 'in_transit' ? 'bg-indigo-100 text-indigo-800' : 'bg-amber-100 text-amber-800' }}">
                                    {{ ucfirst(str_replace('_', ' ', $tr->status)) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right text-xs text-slate-400">{{ \Carbon\Carbon::parse($tr->created_at)->format('d M, Y') }}</td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('transfers.show', $tr) }}" class="text-xs text-emerald-600 font-semibold hover:text-emerald-700">View Details &rarr;</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-slate-400 text-xs">No active transfer consignments currently in transit.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
