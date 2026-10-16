@extends('layouts.app')

@section('title', __('messages.transfers') . ' - Stock Movement')
@section('header', __('messages.transfers'))

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-black text-slate-900 dark:text-white flex items-center gap-2">
                <span>{{ __('messages.transfers') }}</span>
                <span class="text-xs px-2.5 py-0.5 rounded-full bg-indigo-100 text-indigo-700 dark:bg-indigo-900/60 dark:text-indigo-300 font-bold">
                    Controlled In-Transit Stock
                </span>
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                Central warehouse to retail outlets & inter-store transfers: Request → Approve → Dispatch → In Transit → Inspection & Receipt.
            </p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('transfers.create') }}" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs shadow-md shadow-indigo-600/20 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span>Request Transfer</span>
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-slate-900 rounded-xl p-4 border border-slate-200 dark:border-slate-800 shadow-sm">
        <form method="GET" action="{{ route('transfers.index') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div>
                <select name="status" class="w-full text-xs rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white px-3 py-2">
                    <option value="">All Transfer Statuses</option>
                    <option value="requested" {{ request('status') === 'requested' ? 'selected' : '' }}>Requested (Pending Approval)</option>
                    <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved (Ready for Dispatch)</option>
                    <option value="in_transit" {{ request('status') === 'in_transit' ? 'selected' : '' }}>In Transit (On Road)</option>
                    <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed (Received)</option>
                    <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                </select>
            </div>
            <div class="flex items-center gap-2">
                <button type="submit" class="px-4 py-2 rounded-lg bg-slate-800 dark:bg-slate-700 text-white text-xs font-semibold hover:bg-slate-900 transition">
                    Filter
                </button>
                <a href="{{ route('transfers.index') }}" class="px-3 py-2 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 text-xs hover:bg-slate-200 transition">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Transfers Table -->
    <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-800/60 border-b border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-300 font-bold">
                        <th class="p-3">Challan / Ref #</th>
                        <th class="p-3">Source Store / Warehouse</th>
                        <th class="p-3">Destination Store</th>
                        <th class="p-3">Date</th>
                        <th class="p-3 text-center">Transfer State</th>
                        <th class="p-3 text-right">Items / Qty</th>
                        <th class="p-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($transfers as $tr)
                        <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition">
                            <td class="p-3 font-mono font-bold text-indigo-600 dark:text-indigo-400">
                                <a href="{{ route('transfers.show', $tr) }}" class="hover:underline">
                                    {{ $tr->reference_number }}
                                </a>
                            </td>
                            <td class="p-3 font-semibold text-slate-800 dark:text-slate-200">
                                {{ $tr->sourceStore->name }}
                            </td>
                            <td class="p-3 font-semibold text-slate-800 dark:text-slate-200">
                                {{ $tr->destinationStore->name }}
                            </td>
                            <td class="p-3 text-slate-500">
                                {{ $tr->created_at->format('d M Y, h:i A') }}
                            </td>
                            <td class="p-3 text-center">
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold
                                    @if($tr->status === 'completed') bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300
                                    @elseif($tr->status === 'in_transit') bg-purple-100 text-purple-800 dark:bg-purple-950/60 dark:text-purple-300
                                    @elseif($tr->status === 'approved') bg-blue-100 text-blue-800 dark:bg-blue-950/60 dark:text-blue-300
                                    @elseif($tr->status === 'requested') bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300
                                    @else bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-300 @endif">
                                    {{ strtoupper(str_replace('_', ' ', $tr->status)) }}
                                </span>
                            </td>
                            <td class="p-3 text-right font-mono text-slate-700 dark:text-slate-200">
                                {{ $tr->items->sum('quantity_requested') }} pcs
                            </td>
                            <td class="p-3 text-right space-x-1">
                                <a href="{{ route('transfers.show', $tr) }}" class="px-2.5 py-1 rounded bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-[11px] font-semibold">
                                    View
                                </a>
                                @if($tr->status === 'approved')
                                    <a href="{{ route('transfers.dispatch.view', $tr) }}" class="px-2.5 py-1 rounded bg-indigo-600 hover:bg-indigo-700 text-white text-[11px] font-bold">
                                        Dispatch
                                    </a>
                                @elseif($tr->status === 'in_transit')
                                    <a href="{{ route('transfers.receive.view', $tr) }}" class="px-2.5 py-1 rounded bg-emerald-600 hover:bg-emerald-700 text-white text-[11px] font-bold">
                                        Confirm Receipt
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-6 text-center text-slate-400">
                                No stock transfers recorded.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($transfers->hasPages())
            <div class="p-3 border-t border-slate-200 dark:border-slate-800">
                {{ $transfers->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
