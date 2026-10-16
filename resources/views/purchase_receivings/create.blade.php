@extends('layouts.app')

@section('title', 'Receive Inventory - ' . $purchaseOrder->po_number)
@section('header', 'Warehouse Intake Workstation')

@section('content')
<div class="max-w-6xl mx-auto space-y-6" x-data="receivingWorkstation()">
    <div class="flex items-center justify-between">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="font-mono text-xs font-bold px-2 py-0.5 rounded bg-emerald-100 text-emerald-800 dark:bg-emerald-950/70 dark:text-emerald-300">
                    APPROVED ORDER
                </span>
                <span class="font-mono text-xs text-slate-500">
                    {{ $purchaseOrder->po_number }}
                </span>
            </div>
            <h1 class="text-xl font-black text-slate-900 dark:text-white">
                Intake & Barcode Reconcile: {{ $purchaseOrder->supplier->name }}
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                Scan garment barcodes or enter quantities. Damaged stock is segregated; accepted items update central warehouse inventory and calculate WAC.
            </p>
        </div>
        <a href="{{ route('purchase-orders.show', $purchaseOrder) }}" class="px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 text-slate-600 dark:text-slate-300 text-xs font-semibold">
            ← Cancel & Return
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

    <!-- FAST BARCODE SCANNER HUD -->
    <div class="bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 rounded-2xl p-5 text-white shadow-lg border border-slate-800">
        <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="w-full sm:w-2/3">
                <label class="block text-xs font-bold text-indigo-300 mb-1 flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                    <span>High-Speed Barcode Scanner Intake</span>
                </label>
                <div class="flex items-center gap-2">
                    <input type="text" x-model="scannedCode" @keydown.enter.prevent="processBarcodeScan()"
                           placeholder="Scan barcode or SKU from physical garment tag..."
                           class="w-full text-sm rounded-xl border-white/20 bg-white/10 text-white placeholder-slate-400 px-4 py-2.5 font-mono focus:ring-2 focus:ring-emerald-400 focus:outline-none">
                    <button type="button" @click="processBarcodeScan()" class="px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-md transition whitespace-nowrap">
                        Increment
                    </button>
                </div>
            </div>

            <div class="w-full sm:w-1/3 text-right">
                <div class="text-[11px] text-slate-300">Intake Destination:</div>
                <div class="text-sm font-black text-emerald-400">{{ $purchaseOrder->destinationStore->name ?? 'Central Warehouse' }}</div>
                <div class="text-[11px] text-slate-400 font-mono">Code: {{ $purchaseOrder->destinationStore->code ?? 'CW-01' }}</div>
            </div>
        </div>

        <div x-show="scanFeedback" x-transition class="mt-2 text-xs font-semibold" :class="scanFeedbackSuccess ? 'text-emerald-400' : 'text-rose-400'" x-text="scanFeedback"></div>
    </div>

    <form method="POST" action="{{ route('purchase-receivings.store', $purchaseOrder) }}" class="space-y-6">
        @csrf

        <!-- Vendor Bill Details -->
        <div class="bg-white dark:bg-slate-900 rounded-xl p-5 border border-slate-200 dark:border-slate-800 shadow-sm">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                        Supplier Invoice / Challan #
                    </label>
                    <input type="text" name="supplier_bill_number" value="{{ old('supplier_bill_number') }}"
                           placeholder="e.g. CHN-2026-981"
                           class="w-full text-xs rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white px-3 py-2 font-mono">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                        Actual Landed Freight / Additional Cost (৳)
                    </label>
                    <input type="number" step="0.01" min="0" name="actual_additional_costs"
                           value="{{ old('actual_additional_costs', $purchaseOrder->additional_costs) }}"
                           placeholder="0.00"
                           class="w-full text-xs rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white px-3 py-2 font-mono">
                    <p class="text-[10px] text-slate-400 mt-0.5">Will be distributed over accepted units for WAC</p>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                        Receiving Inspection Notes
                    </label>
                    <input type="text" name="notes" value="{{ old('notes') }}"
                           placeholder="e.g. Carton 3 was slightly torn, contents inspected"
                           class="w-full text-xs rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white px-3 py-2">
                </div>
            </div>
        </div>

        <!-- Intake Line Reconciliation Table -->
        <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
            <div class="p-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                <div>
                    <h2 class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-200">
                        Item Reconciliation & Stock Disposition
                    </h2>
                    <p class="text-[11px] text-slate-400">Specify physical count received. Segregate damaged items from sellable warehouse stock.</p>
                </div>
                <div class="text-xs font-bold text-slate-600 dark:text-slate-300">
                    Remaining to Receive: <strong class="text-indigo-600">{{ $purchaseOrder->total_ordered_qty - $purchaseOrder->total_received_qty }}</strong> pcs
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="bg-slate-50 dark:bg-slate-800/60 text-slate-600 dark:text-slate-300 font-bold border-b border-slate-100 dark:border-slate-800">
                            <th class="p-3">Garment Variant</th>
                            <th class="p-3">SKU / Barcode</th>
                            <th class="p-3 text-right">Remaining Due</th>
                            <th class="p-3 text-right w-28">Received Qty</th>
                            <th class="p-3 text-right w-28 text-emerald-600 dark:text-emerald-400">Accepted Qty</th>
                            <th class="p-3 text-right w-28 text-rose-600 dark:text-rose-400">Damaged Qty</th>
                            <th class="p-3 text-right">Unit Cost (৳)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach($purchaseOrder->items as $index => $item)
                            @php
                                $remainingDue = max(0, $item->quantity_ordered - $item->quantity_received);
                            @endphp
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30"
                                :class="highlightedIndex === {{ $index }} ? 'bg-indigo-50/60 dark:bg-indigo-950/40 ring-2 ring-indigo-500' : ''">
                                <td class="p-3">
                                    <div class="font-bold text-slate-900 dark:text-white">
                                        {{ $item->productVariant->product->name }}
                                    </div>
                                    <div class="text-[11px] text-slate-400">
                                        Color: {{ $item->productVariant->color ?? 'Standard' }} • Size: {{ $item->productVariant->size ?? 'Free' }}
                                    </div>
                                    <input type="hidden" name="items[{{ $index }}][product_variant_id]" value="{{ $item->product_variant_id }}">
                                    <input type="hidden" name="items[{{ $index }}][unit_cost]" value="{{ $item->unit_cost }}">
                                </td>
                                <td class="p-3 font-mono text-[11px] text-slate-500">
                                    <div>SKU: {{ $item->productVariant->sku }}</div>
                                    <div class="text-indigo-600 font-bold">{{ $item->productVariant->barcode }}</div>
                                </td>
                                <td class="p-3 text-right font-mono font-bold text-slate-700 dark:text-slate-300">
                                    {{ $remainingDue }}
                                </td>
                                <td class="p-3 text-right">
                                    <input type="number" min="0" name="items[{{ $index }}][quantity_received]"
                                           x-model.number="rows[{{ $index }}].received"
                                           @input="syncAccepted({{ $index }})"
                                           required
                                           class="w-full text-right text-xs rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white px-2 py-1.5 font-mono">
                                </td>
                                <td class="p-3 text-right">
                                    <input type="number" min="0" name="items[{{ $index }}][quantity_accepted]"
                                           x-model.number="rows[{{ $index }}].accepted"
                                           required
                                           class="w-full text-right text-xs rounded-lg border-emerald-300 dark:border-emerald-700 bg-emerald-50/30 dark:bg-emerald-950/30 text-emerald-800 dark:text-emerald-300 font-bold px-2 py-1.5 font-mono">
                                </td>
                                <td class="p-3 text-right">
                                    <input type="number" min="0" name="items[{{ $index }}][quantity_damaged]"
                                           x-model.number="rows[{{ $index }}].damaged"
                                           @input="adjustForDamage({{ $index }})"
                                           class="w-full text-right text-xs rounded-lg border-rose-300 dark:border-rose-700 bg-rose-50/30 dark:bg-rose-950/30 text-rose-800 dark:text-rose-300 px-2 py-1.5 font-mono">
                                </td>
                                <td class="p-3 text-right font-mono text-slate-800 dark:text-slate-200">
                                    ৳{{ number_format($item->unit_cost, 2) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="p-4 bg-slate-50 dark:bg-slate-800/60 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-xs font-mono">
                <div>
                    Session Accepted Stock: <strong class="text-emerald-600 font-black" x-text="totalAccepted()"></strong> pcs
                    • Segregated Damaged: <strong class="text-rose-600 font-black" x-text="totalDamaged()"></strong> pcs
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('purchase-orders.show', $purchaseOrder) }}" class="px-4 py-2 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-semibold">
                Cancel
            </a>
            <button type="submit" class="px-6 py-2.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-md shadow-emerald-600/30 transition">
                Confirm Intake & Update Inventory
            </button>
        </div>
    </form>
</div>

<script>
function receivingWorkstation() {
    return {
        scannedCode: '',
        scanFeedback: '',
        scanFeedbackSuccess: true,
        highlightedIndex: null,
        variantsMap: [
            @foreach($purchaseOrder->items as $idx => $it)
                {
                    index: {{ $idx }},
                    sku: '{{ $it->productVariant->sku }}',
                    barcode: '{{ $it->productVariant->barcode }}',
                    name: '{{ addslashes($it->productVariant->product->name) }}',
                    remaining: {{ max(0, $it->quantity_ordered - $it->quantity_received) }}
                },
            @endforeach
        ],
        rows: [
            @foreach($purchaseOrder->items as $it)
                @php $rem = max(0, $it->quantity_ordered - $it->quantity_received); @endphp
                { received: {{ $rem }}, accepted: {{ $rem }}, damaged: 0 },
            @endforeach
        ],
        syncAccepted(index) {
            let rec = Number(this.rows[index].received || 0);
            let dam = Number(this.rows[index].damaged || 0);
            this.rows[index].accepted = Math.max(0, rec - dam);
        },
        adjustForDamage(index) {
            let rec = Number(this.rows[index].received || 0);
            let dam = Number(this.rows[index].damaged || 0);
            this.rows[index].accepted = Math.max(0, rec - dam);
        },
        processBarcodeScan() {
            let code = this.scannedCode.trim();
            if (!code) return;

            let match = this.variantsMap.find(v => v.barcode === code || v.sku === code);
            if (match) {
                this.rows[match.index].received = (Number(this.rows[match.index].received) || 0) + 1;
                this.syncAccepted(match.index);
                this.highlightedIndex = match.index;
                this.scanFeedback = 'Scanned: ' + match.name + ' (+1 pc count)';
                this.scanFeedbackSuccess = true;
                this.scannedCode = '';
                setTimeout(() => { this.highlightedIndex = null; }, 1500);
            } else {
                this.scanFeedback = 'Barcode [' + code + '] not found in this Purchase Order!';
                this.scanFeedbackSuccess = false;
            }
        },
        totalAccepted() {
            return this.rows.reduce((sum, r) => sum + Number(r.accepted || 0), 0);
        },
        totalDamaged() {
            return this.rows.reduce((sum, r) => sum + Number(r.damaged || 0), 0);
        }
    }
}
</script>
@endsection
