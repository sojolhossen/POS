@extends('layouts.app')

@section('title', 'Dispatch Transfer - ' . $transfer->reference_number)
@section('header', 'Warehouse Dispatch Workstation')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="font-mono text-xs font-bold px-2 py-0.5 rounded bg-blue-100 text-blue-800 dark:bg-blue-950/70 dark:text-blue-300">
                    APPROVED FOR DISPATCH
                </span>
                <span class="font-mono text-xs text-slate-500">
                    {{ $transfer->reference_number }}
                </span>
            </div>
            <h1 class="text-xl font-black text-slate-900 dark:text-white">
                Dispatching from: {{ $transfer->sourceStore->name }}
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                Target Outlet: <strong>{{ $transfer->destinationStore->name }}</strong>. Submitting dispatch will decrement source available stock and mark inventory as In-Transit.
            </p>
        </div>
        <a href="{{ route('transfers.show', $transfer) }}" class="px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 text-slate-600 dark:text-slate-300 text-xs font-semibold">
            ← Cancel
        </a>
    </div>

    @if ($errors->any())
        <div class="p-4 rounded-xl bg-rose-50 dark:bg-rose-950/50 border border-rose-200 dark:border-rose-800 text-rose-700 dark:text-rose-300 text-xs">
            <div class="font-bold mb-1">Validation Errors:</div>
            <ul class="list-disc list-inside space-y-0.5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('transfers.dispatch.store', $transfer) }}" class="space-y-6">
        @csrf

        <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
            <div class="p-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                <div>
                    <h2 class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-200">
                        Dispatch Verification
                    </h2>
                    <p class="text-[11px] text-slate-400">Verify items and physical units packed onto the vehicle.</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="bg-slate-50 dark:bg-slate-800/60 text-slate-600 dark:text-slate-300 font-bold border-b border-slate-100 dark:border-slate-800">
                            <th class="p-3">Garment Variant</th>
                            <th class="p-3">SKU / Barcode</th>
                            <th class="p-3 text-right">Requested Qty</th>
                            <th class="p-3 text-right w-36">Dispatched Qty (Packed)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach($transfer->items as $item)
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30">
                                <td class="p-3">
                                    <div class="font-bold text-slate-900 dark:text-white">
                                        {{ $item->productVariant->product->name }}
                                    </div>
                                    <div class="text-[11px] text-slate-400">
                                        Color: {{ $item->productVariant->color ?? 'Standard' }} • Size: {{ $item->productVariant->size ?? 'Free' }}
                                    </div>
                                </td>
                                <td class="p-3 font-mono text-[11px] text-slate-500">
                                    {{ $item->productVariant->sku }}
                                </td>
                                <td class="p-3 text-right font-mono font-bold text-slate-700 dark:text-slate-300">
                                    {{ $item->quantity_requested }} pcs
                                </td>
                                <td class="p-3 text-right">
                                    <input type="number" min="0" max="{{ $item->quantity_requested }}"
                                           name="quantities[{{ $item->id }}]"
                                           value="{{ old('quantities.'.$item->id, $item->quantity_requested) }}"
                                           required
                                           class="w-full text-right text-xs rounded-lg border-indigo-300 dark:border-indigo-700 bg-indigo-50/20 dark:bg-indigo-950/20 font-mono font-bold text-indigo-700 dark:text-indigo-300 px-3 py-2">
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('transfers.show', $transfer) }}" class="px-4 py-2 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-semibold">
                Cancel
            </a>
            <button type="submit" class="px-6 py-2.5 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold shadow-md shadow-indigo-600/30 transition">
                Confirm Dispatch & Mark In-Transit
            </button>
        </div>
    </form>
</div>
@endsection
