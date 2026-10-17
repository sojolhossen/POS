@extends('layouts.app')

@section('title', 'Purchase Order ' . $purchaseOrder->po_number)
@section('header', 'Purchase Order Details')

@section('content')
<div class="space-y-6">
    <!-- Top Action Header -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl p-6 border border-slate-200 dark:border-slate-800 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="font-mono text-xs font-bold px-2.5 py-0.5 rounded bg-indigo-50 dark:bg-indigo-950/70 text-indigo-700 dark:text-indigo-300">
                    {{ $purchaseOrder->po_number }}
                </span>
                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold
                    @if($purchaseOrder->status === 'completed') bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300
                    @elseif($purchaseOrder->status === 'approved') bg-blue-100 text-blue-800 dark:bg-blue-950/60 dark:text-blue-300
                    @elseif($purchaseOrder->status === 'submitted') bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300
                    @elseif($purchaseOrder->status === 'partially_received') bg-purple-100 text-purple-800 dark:bg-purple-950/60 dark:text-purple-300
                    @elseif($purchaseOrder->status === 'cancelled') bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-300
                    @else bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 @endif">
                    {{ strtoupper(str_replace('_', ' ', $purchaseOrder->status)) }}
                </span>
            </div>
            <h1 class="text-2xl font-black text-slate-900 dark:text-white">
                Supplier: {{ $purchaseOrder->supplier->name }}
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                Created by {{ $purchaseOrder->creator->name ?? 'System' }} on {{ optional($purchaseOrder->po_date ?? $purchaseOrder->created_at)->format('d M Y, h:i A') ?? '—' }} • Destination: {{ $purchaseOrder->destinationStore->name ?? 'Central Warehouse' }}
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('purchase-orders.index') }}" class="px-3 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 text-slate-600 dark:text-slate-300 text-xs font-semibold">
                ← Back
            </a>

            @if($purchaseOrder->status === 'submitted' && auth()->user()->isSuperAdmin())
                <form method="POST" action="{{ route('purchase-orders.approve', $purchaseOrder) }}" onsubmit="return confirm('Approve this purchase order? Only approved orders can receive physical inventory.');">
                    @csrf
                    <button type="submit" class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-md shadow-emerald-600/20 transition flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span>Super Admin Approve</span>
                    </button>
                </form>
            @endif

            @if(in_array($purchaseOrder->status, ['approved', 'partially_received']))
                <a href="{{ route('purchase-receivings.create', $purchaseOrder) }}" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs shadow-md shadow-indigo-600/20 transition flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                    <span>Receive Warehouse Stock</span>
                </a>
            @endif

            @if(in_array($purchaseOrder->status, ['draft', 'submitted', 'approved']))
                <form method="POST" action="{{ route('purchase-orders.cancel', $purchaseOrder) }}" onsubmit="return confirm('Cancel this purchase order? This action cannot be reversed.');">
                    @csrf
                    <button type="submit" class="px-3 py-2 rounded-xl bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/60 text-rose-700 dark:text-rose-300 font-semibold text-xs transition">
                        Cancel Order
                    </button>
                </form>
            @endif
        </div>
    </div>

    <!-- Status Audit Info -->
    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-slate-900 rounded-xl p-4 border border-slate-200 dark:border-slate-800 shadow-sm">
            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Total Ordered</span>
            <div class="text-xl font-black text-slate-900 dark:text-white mt-0.5">
                {{ $purchaseOrder->total_ordered_qty }} pcs
            </div>
        </div>

        <div class="bg-white dark:bg-slate-900 rounded-xl p-4 border border-slate-200 dark:border-slate-800 shadow-sm">
            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Total Received</span>
            <div class="text-xl font-black text-emerald-600 dark:text-emerald-400 mt-0.5">
                {{ $purchaseOrder->total_received_qty }} pcs
            </div>
        </div>

        <div class="bg-white dark:bg-slate-900 rounded-xl p-4 border border-slate-200 dark:border-slate-800 shadow-sm">
            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Bill Item Subtotal</span>
            <div class="text-xl font-black text-slate-900 dark:text-white mt-0.5 font-mono">
                ৳{{ number_format($purchaseOrder->subtotal, 2) }}
            </div>
        </div>

        <div class="bg-white dark:bg-slate-900 rounded-xl p-4 border border-slate-200 dark:border-slate-800 shadow-sm">
            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Grand Total (Inc. Freight)</span>
            <div class="text-xl font-black text-indigo-600 dark:text-indigo-400 mt-0.5 font-mono">
                ৳{{ number_format($purchaseOrder->total_amount, 2) }}
            </div>
        </div>
    </div>

    <!-- Line Items Table -->
    <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
        <div class="p-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-200">
                Ordered Garment Items
            </h2>
            <span class="text-xs text-slate-400 font-mono">{{ $purchaseOrder->items->count() }} Variants</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-800/60 text-slate-600 dark:text-slate-300 font-bold border-b border-slate-100 dark:border-slate-800">
                        <th class="p-3">Variant / Product</th>
                        <th class="p-3">SKU / Barcode</th>
                        <th class="p-3 text-right">Ordered Qty</th>
                        <th class="p-3 text-right">Received Qty</th>
                        <th class="p-3 text-right">Remaining Qty</th>
                        <th class="p-3 text-right">Wholesale Unit Cost</th>
                        <th class="p-3 text-right">Line Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @foreach($purchaseOrder->items as $item)
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30">
                            <td class="p-3 font-semibold text-slate-900 dark:text-white">
                                {{ $item->productVariant->product->name }}
                                <span class="text-slate-400 font-normal">({{ $item->productVariant->color ?? 'Standard' }} / {{ $item->productVariant->size ?? 'Free' }})</span>
                            </td>
                            <td class="p-3 font-mono text-[11px] text-slate-500">
                                {{ $item->productVariant->sku }}
                            </td>
                            <td class="p-3 text-right font-mono font-bold text-slate-800 dark:text-slate-200">
                                {{ $item->quantity_ordered }}
                            </td>
                            <td class="p-3 text-right font-mono font-bold text-emerald-600 dark:text-emerald-400">
                                {{ $item->quantity_received }}
                            </td>
                            <td class="p-3 text-right font-mono font-bold {{ ($item->quantity_ordered - $item->quantity_received) > 0 ? 'text-amber-600' : 'text-slate-400' }}">
                                {{ max(0, $item->quantity_ordered - $item->quantity_received) }}
                            </td>
                            <td class="p-3 text-right font-mono text-slate-700 dark:text-slate-300">
                                ৳{{ number_format($item->unit_cost, 2) }}
                            </td>
                            <td class="p-3 text-right font-mono font-bold text-slate-900 dark:text-white">
                                ৳{{ number_format($item->subtotal, 2) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Receiving Sessions History -->
    @if($purchaseOrder->receivings->count() > 0)
        <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
            <div class="p-4 border-b border-slate-100 dark:border-slate-800">
                <h2 class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-200">
                    Confirmed Warehouse Receiving Sessions & WAC Updates
                </h2>
            </div>

            <div class="divide-y divide-slate-100 dark:divide-slate-800">
                @foreach($purchaseOrder->receivings as $receiving)
                    <div class="p-4 space-y-3">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                            <div>
                                <span class="font-mono text-xs font-bold text-indigo-600 dark:text-indigo-400">
                                    {{ $receiving->receiving_number }}
                                </span>
                                <span class="text-xs text-slate-500 ml-2">
                                    Received on {{ optional($receiving->receiving_date ?? $receiving->received_at ?? $receiving->created_at)->format('d M Y, h:i A') ?? '—' }} by {{ $receiving->receiver->name ?? 'Warehouse Staff' }}
                                </span>
                            </div>
                            <div class="text-xs font-mono text-slate-600 dark:text-slate-300">
                                Allocated Freight: <strong>৳{{ number_format($receiving->allocated_additional_costs, 2) }}</strong>
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-[11px] bg-slate-50/50 dark:bg-slate-800/30 rounded-lg">
                                <thead>
                                    <tr class="text-slate-500 border-b border-slate-200/60 dark:border-slate-700/60">
                                        <th class="p-2">Variant</th>
                                        <th class="p-2 text-right">Received</th>
                                        <th class="p-2 text-right">Accepted (Warehouse)</th>
                                        <th class="p-2 text-right text-rose-500">Damaged</th>
                                        <th class="p-2 text-right text-amber-500">Shortage</th>
                                        <th class="p-2 text-right text-blue-500">Excess</th>
                                        <th class="p-2 text-right">Landed Cost / Pc</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                    @foreach($receiving->items as $rItem)
                                        <tr>
                                            <td class="p-2 font-medium text-slate-800 dark:text-slate-200">
                                                {{ $rItem->productVariant->product->name }} ({{ $rItem->productVariant->sku }})
                                            </td>
                                            <td class="p-2 text-right font-mono">{{ $rItem->quantity_received }}</td>
                                            <td class="p-2 text-right font-mono font-bold text-emerald-600">{{ $rItem->quantity_accepted }}</td>
                                            <td class="p-2 text-right font-mono text-rose-600">{{ $rItem->quantity_damaged }}</td>
                                            <td class="p-2 text-right font-mono text-amber-600">{{ $rItem->quantity_shortage }}</td>
                                            <td class="p-2 text-right font-mono text-blue-600">{{ $rItem->quantity_excess }}</td>
                                            <td class="p-2 text-right font-mono font-bold text-indigo-600">৳{{ number_format($rItem->landed_cost, 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
@endsection
