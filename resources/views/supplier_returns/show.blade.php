@extends('layouts.app')

@section('title', 'Supplier Debit Note ' . $supplierReturn->return_number)
@section('header', 'Supplier Return Debit Note')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="font-mono text-xs font-bold px-2 py-0.5 rounded bg-rose-100 text-rose-800 dark:bg-rose-950/70 dark:text-rose-300">
                    RETURN DEBIT NOTE
                </span>
                <span class="font-mono text-xs text-slate-500">
                    {{ $supplierReturn->return_number }}
                </span>
            </div>
            <h1 class="text-xl font-black text-slate-900 dark:text-white">
                Vendor: {{ $supplierReturn->supplier->name }}
            </h1>
            <p class="text-xs text-slate-500 mt-1">
                Created on {{ $supplierReturn->created_at->format('d M Y, h:i A') }} by {{ $supplierReturn->creator->name ?? 'Staff' }} • Dispatch Location: {{ $supplierReturn->store->name }}
            </p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('supplier-returns.index') }}" class="px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 text-slate-600 dark:text-slate-300 text-xs font-semibold">
                ← Back
            </a>
            <button onclick="window.print()" class="px-3 py-1.5 rounded-lg bg-slate-800 dark:bg-slate-700 hover:bg-slate-900 text-white font-bold text-xs shadow-sm flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                <span>Print Debit Note</span>
            </button>
        </div>
    </div>

    <!-- Official Document Layout -->
    <div class="bg-white dark:bg-slate-900 rounded-xl p-8 border border-slate-200 dark:border-slate-800 shadow-sm space-y-6">
        <div class="flex items-start justify-between border-b border-slate-200 dark:border-slate-800 pb-6">
            <div>
                <div class="text-xs uppercase tracking-widest text-slate-400 font-bold">Official Vendor Return & Debit Note</div>
                <h2 class="text-2xl font-black text-slate-900 dark:text-white mt-1">CLOTHING STORE BD</h2>
                <p class="text-xs text-slate-500 mt-1">Vendor Claims, Damaged Goods Reversal & Gate Pass</p>
            </div>

            <!-- Transaction QR Code Mock -->
            <div class="text-center p-2 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800">
                <svg class="w-16 h-16 text-slate-900 dark:text-white mx-auto" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <rect x="3" y="3" width="7" height="7" rx="1"/>
                    <rect x="14" y="3" width="7" height="7" rx="1"/>
                    <rect x="3" y="14" width="7" height="7" rx="1"/>
                    <rect x="6" y="6" width="1" height="1" fill="currentColor"/>
                    <rect x="17" y="6" width="1" height="1" fill="currentColor"/>
                    <rect x="6" y="17" width="1" height="1" fill="currentColor"/>
                    <path d="M14 14h3v3h-3zM17 17h4v4h-4z"/>
                </svg>
                <div class="text-[9px] font-mono text-slate-500 mt-1">{{ $supplierReturn->return_number }}</div>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-6 text-xs">
            <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-800 space-y-1">
                <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Supplier / Vendor:</div>
                <div class="text-sm font-black text-slate-900 dark:text-white">{{ $supplierReturn->supplier->name }}</div>
                <div class="text-slate-500 font-mono">Phone: {{ $supplierReturn->supplier->phone }}</div>
                <div class="text-slate-500">Address: {{ $supplierReturn->supplier->address ?? 'Factory Address' }}</div>
            </div>

            <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-800 space-y-1">
                <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Settlement Particulars:</div>
                <div>Method: <strong class="uppercase font-mono">{{ str_replace('_', ' ', $supplierReturn->settlement_type) }}</strong></div>
                @if($supplierReturn->settlement_reference)
                    <div class="font-mono text-indigo-600 font-bold">Ref #: {{ $supplierReturn->settlement_reference }}</div>
                @endif
                <div class="text-slate-500">Origin Warehouse: {{ $supplierReturn->store->name }}</div>
            </div>
        </div>

        <!-- Items Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="border-b-2 border-slate-800 dark:border-slate-600 text-slate-700 dark:text-slate-200 font-bold">
                        <th class="py-2.5">#</th>
                        <th class="py-2.5">Returned Garment</th>
                        <th class="py-2.5 font-mono">SKU</th>
                        <th class="py-2.5 text-right">Quantity</th>
                        <th class="py-2.5 text-right">Unit Rate (৳)</th>
                        <th class="py-2.5 text-right">Credit Total (৳)</th>
                        <th class="py-2.5">Condition & Fault</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-800 font-mono">
                    @foreach($supplierReturn->items as $idx => $it)
                        <tr>
                            <td class="py-2.5 text-slate-400">{{ $idx + 1 }}</td>
                            <td class="py-2.5 font-sans font-semibold text-slate-900 dark:text-white">
                                {{ $it->productVariant->product->name }} ({{ $it->productVariant->color ?? 'Standard' }}/{{ $it->productVariant->size ?? 'Free' }})
                            </td>
                            <td class="py-2.5 text-[11px] text-slate-500">{{ $it->productVariant->sku }}</td>
                            <td class="py-2.5 text-right font-bold text-slate-900 dark:text-white">{{ $it->quantity }} pcs</td>
                            <td class="py-2.5 text-right text-slate-700 dark:text-slate-300">৳{{ number_format($it->unit_cost, 2) }}</td>
                            <td class="py-2.5 text-right font-bold text-rose-600 dark:text-rose-400">৳{{ number_format($it->total_amount, 2) }}</td>
                            <td class="py-2.5 font-sans text-slate-500 text-[11px]">
                                [{{ strtoupper($it->condition) }}] {{ $it->reason ?? '—' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="pt-4 border-t border-slate-200 dark:border-slate-800 flex justify-between items-center text-xs">
            <span class="text-slate-500">Net Debit Adjustment against Supplier Balance</span>
            <div class="text-xl font-black font-mono text-slate-900 dark:text-white">
                Total Credit Value: ৳{{ number_format($supplierReturn->total_credit_amount, 2) }}
            </div>
        </div>

        <div class="pt-8 border-t border-slate-200 dark:border-slate-800 grid grid-cols-3 gap-4 text-center text-xs">
            <div class="space-y-6">
                <div class="border-b border-slate-300 dark:border-slate-700 pb-1 font-semibold text-slate-700 dark:text-slate-300">
                    {{ $supplierReturn->creator->name ?? 'Warehouse Staff' }}
                </div>
                <div class="text-[10px] text-slate-400 uppercase tracking-wider">Store / Warehouse Dispatcher</div>
            </div>

            <div class="space-y-6">
                <div class="border-b border-slate-300 dark:border-slate-700 pb-1 font-semibold text-slate-700 dark:text-slate-300">
                    Super Admin / Accounts
                </div>
                <div class="text-[10px] text-slate-400 uppercase tracking-wider">Financial Debit Authorization</div>
            </div>

            <div class="space-y-6">
                <div class="border-b border-slate-300 dark:border-slate-700 pb-1 font-semibold text-slate-700 dark:text-slate-300">
                    Supplier Representative
                </div>
                <div class="text-[10px] text-slate-400 uppercase tracking-wider">Goods Received & Acknowledged</div>
            </div>
        </div>
    </div>
</div>
@endsection
