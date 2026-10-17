@extends('layouts.app')

@section('title', __('messages.purchase_orders') . ' - Management')
@section('header', __('messages.purchase_orders'))

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-black text-slate-900 dark:text-white flex items-center gap-2">
                <span>{{ __('messages.purchase_orders') }}</span>
                <span class="text-xs px-2.5 py-0.5 rounded-full bg-indigo-100 text-indigo-700 dark:bg-indigo-900/60 dark:text-indigo-300 font-bold">
                    WAC Costing
                </span>
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                Centralized garment procurement lifecycle: Draft → Submitted → Super Admin Approval → Receiving → Landed Cost Allocation.
            </p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('purchase-orders.create') }}" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs shadow-md shadow-indigo-600/20 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span>New Purchase Order</span>
            </a>
        </div>
    </div>

    <!-- Filters & Search -->
    <div class="bg-white dark:bg-slate-900 rounded-xl p-4 border border-slate-200 dark:border-slate-800 shadow-sm">
        <form method="GET" action="{{ route('purchase-orders.index') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-3">
            <div>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search PO # or supplier bill #..."
                       class="w-full text-xs rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white px-3 py-2">
            </div>
            <div>
                <select name="status" class="w-full text-xs rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white px-3 py-2">
                    <option value="">All Statuses</option>
                    <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                    <option value="submitted" {{ request('status') === 'submitted' ? 'selected' : '' }}>Submitted (Pending Approval)</option>
                    <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved (Ready to Receive)</option>
                    <option value="partially_received" {{ request('status') === 'partially_received' ? 'selected' : '' }}>Partially Received</option>
                    <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                    <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                </select>
            </div>
            <div>
                <select name="supplier_id" class="w-full text-xs rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white px-3 py-2">
                    <option value="">All Suppliers</option>
                    @foreach($suppliers as $s)
                        <option value="{{ $s->id }}" {{ request('supplier_id') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-center gap-2">
                <button type="submit" class="px-4 py-2 rounded-lg bg-slate-800 dark:bg-slate-700 text-white text-xs font-semibold hover:bg-slate-900 transition">
                    Filter
                </button>
                <a href="{{ route('purchase-orders.index') }}" class="px-3 py-2 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 text-xs hover:bg-slate-200 transition">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <!-- PO List Table -->
    <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-800/60 border-b border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-300 font-bold">
                        <th class="p-3">PO Number</th>
                        <th class="p-3">Supplier</th>
                        <th class="p-3">Destination</th>
                        <th class="p-3">Date</th>
                        <th class="p-3 text-center">Lifecycle Status</th>
                        <th class="p-3 text-right">Qty (Rec / Ord)</th>
                        <th class="p-3 text-right">Total Amount</th>
                        <th class="p-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($purchaseOrders as $po)
                        <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition">
                            <td class="p-3 font-mono font-bold text-indigo-600 dark:text-indigo-400">
                                <a href="{{ route('purchase-orders.show', $po) }}" class="hover:underline">
                                    {{ $po->po_number }}
                                </a>
                            </td>
                            <td class="p-3 font-semibold text-slate-900 dark:text-white">
                                {{ $po->supplier->name }}
                            </td>
                            <td class="p-3 text-slate-600 dark:text-slate-300">
                                {{ $po->store->name ?? 'Central Warehouse' }}
                            </td>
                            <td class="p-3 text-slate-500">
                                {{ optional($po->po_date ?? $po->created_at)->format('d M Y') ?? '—' }}
                            </td>
                            <td class="p-3 text-center">
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold
                                    @if($po->status === 'completed') bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300
                                    @elseif($po->status === 'approved') bg-blue-100 text-blue-800 dark:bg-blue-950/60 dark:text-blue-300
                                    @elseif($po->status === 'submitted') bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300
                                    @elseif($po->status === 'partially_received') bg-purple-100 text-purple-800 dark:bg-purple-950/60 dark:text-purple-300
                                    @elseif($po->status === 'cancelled') bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-300
                                    @else bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 @endif">
                                    {{ strtoupper(str_replace('_', ' ', $po->status)) }}
                                </span>
                            </td>
                            <td class="p-3 text-right font-mono text-slate-700 dark:text-slate-200">
                                <span class="font-bold {{ $po->total_received_qty < $po->total_ordered_qty ? 'text-amber-600' : 'text-emerald-600' }}">
                                    {{ $po->total_received_qty }}
                                </span> / {{ $po->total_ordered_qty }}
                            </td>
                            <td class="p-3 text-right font-mono font-bold text-slate-900 dark:text-white">
                                ৳{{ number_format($po->total_amount, 2) }}
                            </td>
                            <td class="p-3 text-right space-x-1">
                                <a href="{{ route('purchase-orders.show', $po) }}" class="px-2.5 py-1 rounded bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-[11px] font-semibold">
                                    View
                                </a>
                                @if(in_array($po->status, ['approved', 'partially_received']))
                                    <a href="{{ route('purchase-receivings.create', $po) }}" class="px-2.5 py-1 rounded bg-emerald-600 hover:bg-emerald-700 text-white text-[11px] font-bold shadow-sm">
                                        Receive Stock
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="p-6 text-center text-slate-400">
                                No purchase orders found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($purchaseOrders->hasPages())
            <div class="p-3 border-t border-slate-200 dark:border-slate-800">
                {{ $purchaseOrders->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
