@extends('layouts.app')

@section('title', 'Receive Transfer - ' . $transfer->reference_number)
@section('header', 'Transfer Receiving & Inspection')

@section('content')
<div class="max-w-5xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="font-mono text-xs font-bold px-2 py-0.5 rounded bg-purple-100 text-purple-800 dark:bg-purple-950/70 dark:text-purple-300">
                    IN TRANSIT
                </span>
                <span class="font-mono text-xs text-slate-500">
                    {{ $transfer->reference_number }}
                </span>
            </div>
            <h1 class="text-xl font-black text-slate-900 dark:text-white">
                Receiving at: {{ $transfer->destinationStore->name }}
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                Source: <strong>{{ $transfer->sourceStore->name }}</strong>. Inspect incoming garments. Any shortage, excess, or damage must include an explanation.
            </p>
            <div class="inline-flex items-center gap-2 mt-2 px-3 py-1.5 rounded-lg bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 text-xs">
                <svg class="w-4 h-4 text-emerald-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/></svg>
                <span>Receiving Officer: <strong>{{ auth()->user()->name }}</strong> ({{ auth()->user()->role_name }}) &mdash; Your identity will be recorded in the official transfer history.</span>
            </div>
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

    <form method="POST" action="{{ route('transfers.receive.store', $transfer) }}" class="space-y-6">
        @csrf

        <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
            <div class="p-4 border-b border-slate-100 dark:border-slate-800">
                <h2 class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-200">
                    Challan Reconciliation & Stock Acceptance
                </h2>
                <p class="text-[11px] text-slate-400">Accepted units will be credited immediately to {{ $transfer->destinationStore->name }}.</p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="bg-slate-50 dark:bg-slate-800/60 text-slate-600 dark:text-slate-300 font-bold border-b border-slate-100 dark:border-slate-800">
                            <th class="p-3">Garment Variant</th>
                            <th class="p-3">SKU</th>
                            <th class="p-3 text-right">Dispatched</th>
                            <th class="p-3 text-right w-28 text-emerald-600 dark:text-emerald-400">Received (Good)</th>
                            <th class="p-3 text-right w-28 text-rose-600 dark:text-rose-400">Damaged</th>
                            <th class="p-3 min-w-[200px]">Discrepancy Note</th>
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
                                        {{ $item->productVariant->color ?? 'Standard' }} / {{ $item->productVariant->size ?? 'Free' }}
                                    </div>
                                </td>
                                <td class="p-3 font-mono text-[11px] text-slate-500">
                                    {{ $item->productVariant->sku }}
                                </td>
                                <td class="p-3 text-right font-mono font-bold text-slate-800 dark:text-slate-200">
                                    {{ $item->quantity_dispatched }} pcs
                                </td>
                                <td class="p-3 text-right">
                                    <input type="number" min="0"
                                           name="received_quantities[{{ $item->id }}]"
                                           value="{{ old('received_quantities.'.$item->id, $item->quantity_dispatched) }}"
                                           required
                                           class="w-full text-right text-xs rounded-lg border-emerald-300 dark:border-emerald-700 bg-emerald-50/20 dark:bg-emerald-950/20 text-emerald-800 dark:text-emerald-300 font-bold px-2 py-1.5 font-mono">
                                </td>
                                <td class="p-3 text-right">
                                    <input type="number" min="0"
                                           name="damaged_quantities[{{ $item->id }}]"
                                           value="{{ old('damaged_quantities.'.$item->id, 0) }}"
                                           class="w-full text-right text-xs rounded-lg border-rose-300 dark:border-rose-700 bg-rose-50/20 dark:bg-rose-950/20 text-rose-800 dark:text-rose-300 px-2 py-1.5 font-mono">
                                </td>
                                <td class="p-3">
                                    <input type="text"
                                           name="discrepancy_reasons[{{ $item->id }}]"
                                           value="{{ old('discrepancy_reasons.'.$item->id) }}"
                                           placeholder="Required if count differs from dispatched"
                                           class="w-full text-xs rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white px-2.5 py-1.5">
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
            <button type="submit" class="px-6 py-2.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-md shadow-emerald-600/30 transition">
                Confirm Intake & Update Available Inventory
            </button>
        </div>
    </form>
</div>
@endsection
