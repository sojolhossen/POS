@extends('layouts.app')

@section('title', 'Transfer Challan ' . $transfer->reference_number)
@section('header', 'Stock Transfer Challan')

@section('content')
<div class="max-w-5xl mx-auto space-y-6">
    <!-- Top Action Bar -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl p-6 border border-slate-200 dark:border-slate-800 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="font-mono text-xs font-bold px-2.5 py-0.5 rounded bg-indigo-50 dark:bg-indigo-950/70 text-indigo-700 dark:text-indigo-300">
                    {{ $transfer->reference_number }}
                </span>
                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold
                    @if($transfer->status === 'completed') bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300
                    @elseif($transfer->status === 'in_transit') bg-purple-100 text-purple-800 dark:bg-purple-950/60 dark:text-purple-300
                    @elseif($transfer->status === 'approved') bg-blue-100 text-blue-800 dark:bg-blue-950/60 dark:text-blue-300
                    @elseif($transfer->status === 'requested') bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300
                    @else bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-300 @endif">
                    {{ strtoupper(str_replace('_', ' ', $transfer->status)) }}
                </span>
            </div>
            <h1 class="text-xl font-black text-slate-900 dark:text-white">
                {{ $transfer->sourceStore->name }} → {{ $transfer->destinationStore->name }}
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                Requested by {{ $transfer->creator->name ?? 'Staff' }} on {{ $transfer->created_at->format('d M Y, h:i A') }}
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('transfers.index') }}" class="px-3 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 text-slate-600 dark:text-slate-300 text-xs font-semibold">
                ← Back
            </a>

            <button onclick="window.print()" class="px-3 py-2 rounded-xl bg-slate-800 dark:bg-slate-700 hover:bg-slate-900 text-white text-xs font-bold transition flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                <span>Print Challan</span>
            </button>

            @if($transfer->status === 'requested' && (auth()->user()->isSuperAdmin() || auth()->user()->isStoreManager()))
                <form method="POST" action="{{ route('transfers.approve', $transfer) }}" onsubmit="return confirm('Authorize this transfer for warehouse dispatch?');">
                    @csrf
                    <button type="submit" class="px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs shadow-md shadow-blue-600/20 transition flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span>Approve Transfer</span>
                    </button>
                </form>
            @endif

            @if($transfer->status === 'approved')
                <a href="{{ route('transfers.dispatch.view', $transfer) }}" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs shadow-md shadow-indigo-600/20 transition flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"/></svg>
                    <span>Dispatch Stock (Reduce Source)</span>
                </a>
            @endif

            @if($transfer->status === 'in_transit')
                <a href="{{ route('transfers.receive.view', $transfer) }}" class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-md shadow-emerald-600/20 transition flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span>Confirm Receiving at Destination</span>
                </a>
            @endif
        </div>
    </div>

    <!-- Official Gate Pass / Challan Document Layout -->
    <div class="bg-white dark:bg-slate-900 rounded-xl p-8 border border-slate-200 dark:border-slate-800 shadow-sm space-y-6" id="printable-challan">
        <div class="flex items-start justify-between border-b border-slate-200 dark:border-slate-800 pb-6">
            <div>
                <div class="text-xs uppercase tracking-widest text-slate-400 font-bold">Inter-Store Stock Movement Challan</div>
                <h2 class="text-2xl font-black text-slate-900 dark:text-white mt-1">CLOTHING RETAIL NETWORK BD</h2>
                <p class="text-xs text-slate-500 mt-1">Official Gate Pass & Goods Dispatched Note</p>
            </div>

            <!-- Transaction QR Code SVG Mock -->
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
                <div class="text-[9px] font-mono text-slate-500 mt-1">{{ $transfer->reference_number }}</div>
            </div>
        </div>

        <!-- Logistics Meta -->
        <div class="grid grid-cols-2 gap-6 text-xs">
            <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-800 space-y-1">
                <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Source (Origin)</div>
                <div class="text-sm font-black text-slate-900 dark:text-white">{{ $transfer->sourceStore->name }}</div>
                <div class="text-slate-500">Address: {{ $transfer->sourceStore->address ?? 'Warehouse Complex, Tejgaon, Dhaka' }}</div>
                <div class="text-slate-500 font-mono">Store Code: {{ $transfer->sourceStore->code }}</div>
            </div>

            <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-800 space-y-1">
                <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Destination (Target Outlet)</div>
                <div class="text-sm font-black text-slate-900 dark:text-white">{{ $transfer->destinationStore->name }}</div>
                <div class="text-slate-500">Address: {{ $transfer->destinationStore->address ?? 'Retail Outlet Branch' }}</div>
                <div class="text-slate-500 font-mono">Store Code: {{ $transfer->destinationStore->code }}</div>
            </div>
        </div>

        <!-- Challan Items Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="border-b-2 border-slate-800 dark:border-slate-600 text-slate-700 dark:text-slate-200 font-bold">
                        <th class="py-2.5">#</th>
                        <th class="py-2.5">Garment Description</th>
                        <th class="py-2.5 font-mono">SKU / Barcode</th>
                        <th class="py-2.5 text-right">Requested</th>
                        <th class="py-2.5 text-right">Dispatched</th>
                        <th class="py-2.5 text-right text-emerald-600">Confirmed Rec.</th>
                        <th class="py-2.5 text-right text-rose-600">Damaged</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                    @foreach($transfer->items as $idx => $it)
                        <tr>
                            <td class="py-2.5 font-mono text-slate-400">{{ $idx + 1 }}</td>
                            <td class="py-2.5 font-semibold text-slate-900 dark:text-white">
                                {{ $it->productVariant->product->name }}
                                <span class="text-slate-400 font-normal">({{ $it->productVariant->color ?? 'Standard' }} / {{ $it->productVariant->size ?? 'Free' }})</span>
                            </td>
                            <td class="py-2.5 font-mono text-[11px] text-slate-500">{{ $it->productVariant->sku }}</td>
                            <td class="py-2.5 text-right font-mono">{{ $it->quantity_requested }} pcs</td>
                            <td class="py-2.5 text-right font-mono font-bold text-slate-800 dark:text-slate-200">{{ $it->quantity_dispatched }} pcs</td>
                            <td class="py-2.5 text-right font-mono font-bold text-emerald-600">{{ $it->quantity_received }} pcs</td>
                            <td class="py-2.5 text-right font-mono text-rose-600">{{ $it->quantity_damaged }} pcs</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Signatures & Audit Footprint -->
        <div class="pt-8 border-t border-slate-200 dark:border-slate-800 grid grid-cols-4 gap-4 text-center text-xs">
            <div class="space-y-8">
                <div class="border-b border-slate-300 dark:border-slate-700 pb-1 font-semibold text-slate-700 dark:text-slate-300">
                    {{ $transfer->creator->name ?? 'Requester' }}
                </div>
                <div class="text-[10px] text-slate-400 uppercase tracking-wider">Requisition Prepared</div>
            </div>

            <div class="space-y-8">
                <div class="border-b border-slate-300 dark:border-slate-700 pb-1 font-semibold text-slate-700 dark:text-slate-300">
                    {{ $transfer->approver->name ?? 'Pending' }}
                </div>
                <div class="text-[10px] text-slate-400 uppercase tracking-wider">Manager Approval</div>
            </div>

            <div class="space-y-8">
                <div class="border-b border-slate-300 dark:border-slate-700 pb-1 font-semibold text-slate-700 dark:text-slate-300">
                    {{ $transfer->dispatcher->name ?? 'Pending' }}
                </div>
                <div class="text-[10px] text-slate-400 uppercase tracking-wider">Warehouse Dispatcher</div>
            </div>

            <div class="space-y-8">
                <div class="border-b border-slate-300 dark:border-slate-700 pb-1 font-semibold text-slate-700 dark:text-slate-300">
                    {{ $transfer->receiver->name ?? 'Pending' }}
                </div>
                <div class="text-[10px] text-slate-400 uppercase tracking-wider">Destination Receiver</div>
            </div>
        </div>
    </div>
</div>
@endsection
