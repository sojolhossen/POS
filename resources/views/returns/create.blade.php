@extends('layouts.app')

@section('title', 'Process Return / Exchange - ' . __('messages.returns_exchanges'))
@section('header', 'Customer Return & Exchange Workstation')

@section('content')
<div class="max-w-5xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-black text-slate-900 dark:text-white">Customer Return / Exchange Workstation</h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                7-Day policy enforcement: Locate original invoice, classify garment condition, and settle refunds atomically.
            </p>
        </div>
        <a href="{{ route('returns.index') }}" class="px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 text-slate-600 dark:text-slate-300 text-xs font-semibold">
            ← Back to History
        </a>
    </div>

    <!-- INVOICE LOOKUP FORM -->
    <div class="bg-white dark:bg-slate-900 rounded-xl p-5 border border-slate-200 dark:border-slate-800 shadow-sm">
        <form method="GET" action="{{ route('returns.create') }}" class="flex flex-col sm:flex-row items-center gap-3">
            <div class="relative flex-1 w-full">
                <input type="text" name="invoice_number" value="{{ $invoiceNumber ?? ($sale->invoice_number ?? '') }}" required
                       placeholder="Enter original invoice number (e.g. INV-DHK-20261016-000001)..."
                       class="w-full text-xs rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white px-4 py-2.5 font-mono">
            </div>
            <button type="submit" class="w-full sm:w-auto px-6 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs shadow-md transition whitespace-nowrap">
                Locate Sale
            </button>
        </form>
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

    @if($sale)
        @php
            $daysSinceSale = (int) $sale->created_at->diffInDays(now());
            $isWithin7Days = $daysSinceSale <= 7;
        @endphp

        <!-- SALE SUMMARY CARD -->
        <div class="bg-white dark:bg-slate-900 rounded-xl p-5 border border-slate-200 dark:border-slate-800 shadow-sm space-y-3">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-100 dark:border-slate-800 pb-3">
                <div>
                    <span class="font-mono text-xs font-bold text-indigo-600 dark:text-indigo-400">
                        {{ $sale->invoice_number }}
                    </span>
                    <span class="text-xs text-slate-500 ml-2">
                        Sold on {{ $sale->created_at->format('d M Y, h:i A') }} ({{ $daysSinceSale === 0 ? 'Today' : $daysSinceSale . ' days ago' }})
                    </span>
                </div>
                <div>
                    @if($isWithin7Days)
                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300">
                            ✓ Within 7-Day Return Window
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-300">
                            ⚠️ Expired Return Window (> 7 Days) — Requires Manager Approval
                        </span>
                    @endif
                </div>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-xs">
                <div>
                    <span class="text-slate-400 text-[10px] uppercase font-bold">Store Outlet:</span>
                    <div class="font-semibold text-slate-800 dark:text-slate-200">{{ $sale->store->name }}</div>
                </div>
                <div>
                    <span class="text-slate-400 text-[10px] uppercase font-bold">Customer:</span>
                    <div class="font-semibold text-slate-800 dark:text-slate-200">{{ $sale->customer->name ?? 'Walk-in Customer' }}</div>
                </div>
                <div>
                    <span class="text-slate-400 text-[10px] uppercase font-bold">Original Invoice Total:</span>
                    <div class="font-mono font-bold text-slate-900 dark:text-white">৳{{ number_format($sale->total_amount, 2) }}</div>
                    @if($sale->discount_amount > 0)
                        <div class="text-[10px] text-emerald-600 dark:text-emerald-400 font-semibold">(৳{{ number_format($sale->discount_amount, 2) }} discount applied)</div>
                    @endif
                </div>
                <div>
                    <span class="text-slate-400 text-[10px] uppercase font-bold">Settlement Rails:</span>
                    <div class="font-mono text-[11px] text-slate-600 dark:text-slate-300">
                        {{ $sale->payments->pluck('payment_method')->implode(', ') }}
                    </div>
                </div>
            </div>
        </div>

        <!-- RETURN FORM -->
        <form method="POST" action="{{ route('returns.store') }}" class="space-y-6">
            @csrf
            <input type="hidden" name="pos_sale_id" value="{{ $sale->id }}">

            <!-- Return Items Table -->
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
                <div class="p-4 border-b border-slate-100 dark:border-slate-800">
                    <h2 class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-200">
                        Select Return Items & Condition Classification
                    </h2>
                    <p class="text-[11px] text-slate-400">Sellable goods return to sellable inventory. Damaged/defective goods are segregated outside sellable stock.</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="bg-slate-50 dark:bg-slate-800/60 text-slate-600 dark:text-slate-300 font-bold border-b border-slate-100 dark:border-slate-800">
                                <th class="p-3">Garment Item</th>
                                <th class="p-3 text-right">Sold Qty</th>
                                <th class="p-3 text-right">Catalog Price</th>
                                <th class="p-3 text-right">Net Refund Rate</th>
                                <th class="p-3 text-right w-28">Return Qty</th>
                                <th class="p-3 w-40">Condition</th>
                                <th class="p-3 min-w-[200px]">Item Return Reason</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @foreach($sale->items as $idx => $item)
                                @php
                                    $returnedSoFar = (int) ($item->quantity_returned ?? \App\Models\PosReturnItem::where('original_pos_sale_item_id', $item->id)->sum('quantity'));
                                    $returnable = $item->returnableQuantity();
                                    $unitCatalogPrice = (float) $item->unit_price;
                                    $itemQty = max(1, (int) $item->quantity);
                                    $netUnitPaid = round(((float) $item->line_total) / $itemQty, 2);
                                    $unitDiscount = round(((float) $item->discount_amount) / $itemQty, 2);
                                @endphp
                                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30">
                                    <td class="p-3">
                                        <div class="font-bold text-slate-900 dark:text-white">
                                            {{ $item->productVariant->product->name }}
                                        </div>
                                        <div class="text-[11px] text-slate-400 font-mono">
                                            {{ $item->productVariant->color ?? 'Standard' }} / {{ $item->productVariant->size ?? 'Free' }} • {{ $item->productVariant->sku }}
                                        </div>
                                        <input type="hidden" name="items[{{ $idx }}][pos_sale_item_id]" value="{{ $item->id }}">
                                    </td>
                                    <td class="p-3 text-right font-mono text-slate-600 dark:text-slate-300">
                                        {{ $item->quantity }} pcs
                                        @if($returnedSoFar > 0)
                                            <div class="text-[10px] text-amber-500">({{ $returnedSoFar }} prev. ret.)</div>
                                        @endif
                                    </td>
                                    <td class="p-3 text-right font-mono text-slate-400">
                                        ৳{{ number_format($unitCatalogPrice, 2) }}
                                    </td>
                                    <td class="p-3 text-right font-mono">
                                        <div class="font-bold text-emerald-600 dark:text-emerald-400">
                                            ৳{{ number_format($netUnitPaid, 2) }}
                                        </div>
                                        @if($unitDiscount > 0)
                                            <div class="text-[10px] text-rose-500 font-sans">(-৳{{ number_format($unitDiscount, 2) }} disc.)</div>
                                        @endif
                                    </td>
                                    <td class="p-3 text-right">
                                        @if($returnable > 0)
                                            <input type="number" min="0" max="{{ $returnable }}"
                                                   name="items[{{ $idx }}][quantity]"
                                                   value="{{ old('items.'.$idx.'.quantity', 0) }}"
                                                   data-unit-refund="{{ $netUnitPaid }}"
                                                   class="return-qty-input w-full text-right text-xs rounded-lg border-rose-300 dark:border-rose-700 bg-rose-50/20 dark:bg-rose-950/20 text-rose-800 dark:text-rose-300 font-bold px-2 py-1.5 font-mono">
                                            <div class="text-[10px] text-slate-400 mt-0.5">Max: {{ $returnable }}</div>
                                        @else
                                            <span class="text-[10px] text-slate-400 font-bold">Fully Returned</span>
                                            <input type="hidden" name="items[{{ $idx }}][quantity]" value="0">
                                        @endif
                                    </td>
                                    <td class="p-3">
                                        <select name="items[{{ $idx }}][condition]" class="w-full text-xs rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white px-2 py-1.5">
                                            <option value="sellable">Sellable (Restock to Shelf)</option>
                                            <option value="damaged">Damaged (Faulty Stitch/Fabric)</option>
                                            <option value="defective">Defective (Factory Defect)</option>
                                        </select>
                                    </td>
                                    <td class="p-3">
                                        <input type="text" name="items[{{ $idx }}][reason]"
                                               placeholder="e.g. Size didn't fit, stitch opened..."
                                               class="w-full text-xs rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white px-2.5 py-1.5">
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Live Refund Calculation Summary -->
                <div class="p-4 bg-slate-50/70 dark:bg-slate-800/50 border-t border-slate-100 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div>
                        <div class="text-xs font-bold text-slate-700 dark:text-slate-300">
                            Cash Register Refund Total:
                        </div>
                        <div class="text-[11px] text-slate-400">
                            Calculated strictly on actual net price paid (after discount deduction).
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="text-xs text-slate-500 font-medium">Estimated Refund:</span>
                        <span id="liveRefundTotal" class="font-mono text-base font-black text-rose-600 dark:text-rose-400">
                            ৳0.00
                        </span>
                    </div>
                </div>
            </div>

            <!-- Settlement & Policy Authorizations -->
            <div class="bg-white dark:bg-slate-900 rounded-xl p-5 border border-slate-200 dark:border-slate-800 shadow-sm space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            Return Type <span class="text-rose-500">*</span>
                        </label>
                        <select name="return_type" required class="w-full text-xs rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white px-3 py-2">
                            <option value="refund">Cash / Digital Refund to Customer</option>
                            <option value="exchange">Garment Exchange / Replacement</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            Settlement Method <span class="text-rose-500">*</span>
                        </label>
                        <select name="settlement_method" required class="w-full text-xs rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white px-3 py-2">
                            <option value="cash">Cash Settlement (From Register)</option>
                            <option value="bkash">bKash Refund</option>
                            <option value="nagad">Nagad Refund</option>
                            <option value="card">Card Reversal / Adjustment</option>
                            <option value="exchange">Exchange Credit Note</option>
                        </select>
                    </div>
                </div>

                @if(!$isWithin7Days || request('receiptless'))
                    <div class="p-4 rounded-xl bg-amber-50 dark:bg-amber-950/50 border border-amber-200 dark:border-amber-800 space-y-2 text-xs">
                        <div class="font-bold text-amber-800 dark:text-amber-300 flex items-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                            <span>Special Authorization Required</span>
                        </div>
                        <p class="text-amber-700 dark:text-amber-400 text-[11px]">
                            This transaction is past the 7-day policy or is a receiptless return. Store Manager authorization will be recorded automatically.
                        </p>
                        <label class="inline-flex items-center gap-2 font-bold text-slate-800 dark:text-slate-200">
                            <input type="checkbox" name="is_receiptless" value="1" {{ request('receiptless') ? 'checked' : '' }} class="rounded border-slate-300 text-indigo-600">
                            <span>Receiptless / Policy Override Return Authorized by Manager</span>
                        </label>
                    </div>
                @endif

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                        Return Transaction Notes
                    </label>
                    <textarea name="reason" rows="2" placeholder="Overall reason for return/exchange"
                              class="w-full text-xs rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white px-3 py-2">{{ old('reason') }}</textarea>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3">
                <a href="{{ route('returns.create') }}" class="px-4 py-2 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-semibold">
                    Reset
                </a>
                <button type="submit" class="px-6 py-2.5 rounded-lg bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold shadow-md shadow-rose-600/30 transition">
                    Commit Return & Settle Refund
                </button>
            </div>
        </form>

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const qtyInputs = document.querySelectorAll('.return-qty-input');
                const liveTotalEl = document.getElementById('liveRefundTotal');

                function updateRefundCalculation() {
                    let total = 0.0;
                    qtyInputs.forEach(function (input) {
                        const qty = parseInt(input.value, 10) || 0;
                        const unitRate = parseFloat(input.getAttribute('data-unit-refund')) || 0.0;
                        if (qty > 0) {
                            total += (qty * unitRate);
                        }
                    });
                    if (liveTotalEl) {
                        liveTotalEl.textContent = '৳' + total.toFixed(2);
                    }
                }

                qtyInputs.forEach(function (input) {
                    input.addEventListener('input', updateRefundCalculation);
                    input.addEventListener('change', updateRefundCalculation);
                });

                updateRefundCalculation();
            });
        </script>
    @elseif(!empty($invoiceNumber))
        <div class="p-8 text-center bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 text-slate-500 text-xs">
            No sale found matching invoice number <strong class="text-slate-800 dark:text-slate-200 font-mono">{{ $invoiceNumber }}</strong>. Please check the spelling or scan the barcode from customer's cash memo.
        </div>
    @endif
</div>
@endsection
