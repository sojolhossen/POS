@extends('layouts.app')

@section('title', 'Count Workstation — ' . $stockCount->reference_number)
@section('header', 'Stock Count Session: ' . $stockCount->reference_number)

@section('content')
<div class="space-y-6" x-data="stockCountSession()">

    <div class="flex items-center justify-between">
        <a href="{{ route('stock-counts.index') }}" class="text-xs font-bold text-slate-500 hover:text-indigo-600 flex items-center gap-1">
            ← Back to Stock Counts
        </a>

        @if($stockCount->status === 'in_progress')
            <form action="{{ route('stock-counts.complete', $stockCount) }}" method="POST" onsubmit="return confirm('Complete and close this stock count session?')">
                @csrf
                <button type="submit" class="py-2 px-5 rounded-xl font-bold text-xs text-white bg-emerald-600 hover:bg-emerald-700 shadow-md shadow-emerald-600/30">
                    Complete & Close Session
                </button>
            </form>
        @else
            <span class="px-3 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-600">
                Session Completed (@posDate($stockCount->completed_at))
            </span>
        @endif
    </div>

    <!-- Session Details Card -->
    <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 border border-slate-200/80 dark:border-slate-800 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <h2 class="text-xl font-black text-slate-900 dark:text-white">{{ $stockCount->reference_number }}</h2>
                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase {{ $stockCount->status === 'in_progress' ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800' }}">
                    {{ $stockCount->status }}
                </span>
            </div>
            <div class="text-xs text-slate-500 mt-1">
                Store: <strong>{{ $stockCount->store->name }}</strong> • Auditor: {{ $stockCount->creator->name }} • Date: @posDate($stockCount->created_at)
            </div>
        </div>
    </div>

    <!-- Active Scanner & Counter Workstation -->
    @if($stockCount->status === 'in_progress')
        <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 border border-indigo-200 dark:border-indigo-800/60 shadow-sm">
            <h3 class="text-sm font-bold text-indigo-900 dark:text-indigo-300 mb-3">Barcode Scanning Entry Point</h3>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="sm:col-span-2">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Scan or Enter Barcode</label>
                    <input type="text" x-model="inputBarcode" @keydown.enter.prevent="lookupAndFocusQty()"
                           placeholder="Scan Code 128 barcode or press Enter..."
                           class="w-full text-xs font-mono font-bold rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 py-2.5 px-3">
                </div>

                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Counted Physical Quantity</label>
                    <div class="flex gap-2">
                        <input type="number" x-model="inputQty" min="0" placeholder="Qty" id="qtyInput"
                               class="w-full text-xs font-mono font-bold text-center rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 py-2.5 px-3">
                        <button type="button" @click="submitCountItem()"
                                class="py-2.5 px-4 rounded-xl font-bold text-xs text-white bg-indigo-600 hover:bg-indigo-700">
                            Record
                        </button>
                    </div>
                </div>
            </div>

            <div x-show="feedbackMsg" class="mt-3 text-xs font-semibold" :class="feedbackIsError ? 'text-rose-500' : 'text-emerald-500'" x-text="feedbackMsg"></div>
        </div>
    @endif

    <!-- Reconciliation Items Grid -->
    <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 sm:p-8 border border-slate-200/80 dark:border-slate-800 shadow-sm">
        <h3 class="text-base font-bold text-slate-900 dark:text-white mb-4">Physical vs System Count Reconciliation</h3>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-slate-200 dark:border-slate-800 text-slate-400 uppercase text-[10px] tracking-wider font-bold">
                        <th class="py-3 px-3">Product Name</th>
                        <th class="py-3 px-3">Variant (Color / Size)</th>
                        <th class="py-3 px-3">Barcode</th>
                        <th class="py-3 px-3 text-right">System Qty</th>
                        <th class="py-3 px-3 text-right">Counted Qty</th>
                        <th class="py-3 px-3 text-right">Discrepancy (Diff)</th>
                        <th class="py-3 px-3">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800 font-medium">
                    @forelse($stockCount->items as $item)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                            <td class="py-3 px-3 font-bold text-slate-900 dark:text-white">
                                {{ $item->variant->product->name }}
                            </td>
                            <td class="py-3 px-3">
                                {{ $item->variant->color }} - <strong>{{ $item->variant->size }}</strong>
                            </td>
                            <td class="py-3 px-3 font-mono font-bold">
                                {{ $item->variant->barcode }}
                            </td>
                            <td class="py-3 px-3 text-right font-mono font-bold text-slate-600 dark:text-slate-400">
                                {{ $item->system_quantity }}
                            </td>
                            <td class="py-3 px-3 text-right font-mono font-black text-slate-900 dark:text-white">
                                {{ $item->counted_quantity }}
                            </td>
                            <td class="py-3 px-3 text-right font-mono font-black text-sm {{ $item->difference == 0 ? 'text-slate-400' : ($item->difference > 0 ? 'text-emerald-600' : 'text-rose-600') }}">
                                {{ $item->difference > 0 ? '+' . $item->difference : $item->difference }}
                            </td>
                            <td class="py-3 px-3">
                                @if($item->difference == 0)
                                    <span class="text-emerald-600 font-bold">Matched ✓</span>
                                @elseif($item->difference > 0)
                                    <span class="text-emerald-600 font-bold">Surplus (+{{ $item->difference }})</span>
                                @else
                                    <span class="text-rose-600 font-bold">Shortage ({{ $item->difference }})</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-slate-400">No items scanned or counted yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

@push('scripts')
<script>
function stockCountSession() {
    return {
        inputBarcode: '',
        inputQty: 1,
        feedbackMsg: '',
        feedbackIsError: false,

        lookupAndFocusQty() {
            document.getElementById('qtyInput').focus();
        },

        submitCountItem() {
            if (!this.inputBarcode) {
                this.feedbackMsg = 'Please scan or type a barcode.';
                this.feedbackIsError = true;
                return;
            }

            fetch('{{ route('stock-counts.items.record', $stockCount) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    barcode: this.inputBarcode,
                    counted_quantity: this.inputQty
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    this.feedbackMsg = `Recorded: ${data.item.variant_name} — Counted: ${data.item.counted_quantity} (Diff: ${data.item.difference})`;
                    this.feedbackIsError = false;
                    this.inputBarcode = '';
                    this.inputQty = 1;
                    setTimeout(() => location.reload(), 800);
                } else {
                    this.feedbackMsg = data.message || 'Failed to record item.';
                    this.feedbackIsError = true;
                }
            })
            .catch(() => {
                this.feedbackMsg = 'Server connection error.';
                this.feedbackIsError = true;
            });
        }
    };
}
</script>
@endpush
@endsection
