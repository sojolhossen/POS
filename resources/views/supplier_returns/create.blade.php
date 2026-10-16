@extends('layouts.app')

@section('title', 'Return Garments to Vendor - ' . __('messages.supplier_returns'))
@section('header', 'Return Garments to Supplier')

@section('content')
<div class="max-w-4xl mx-auto space-y-6" x-data="supplierReturnForm()">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-black text-slate-900 dark:text-white">Create Supplier Return / Debit Note</h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                Return damaged factory goods or excess inventory back to vendor. Adjusts liability or issues credit note.
            </p>
        </div>
        <a href="{{ route('supplier-returns.index') }}" class="px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 text-slate-600 dark:text-slate-300 text-xs font-semibold">
            ← Back to Returns
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

    <form method="POST" action="{{ route('supplier-returns.store') }}" class="space-y-6">
        @csrf

        <div class="bg-white dark:bg-slate-900 rounded-xl p-6 border border-slate-200 dark:border-slate-800 shadow-sm space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                        Garment Vendor <span class="text-rose-500">*</span>
                    </label>
                    <select name="supplier_id" required class="w-full text-xs rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white px-3 py-2">
                        <option value="">Select Supplier</option>
                        @foreach($suppliers as $supplier)
                            <option value="{{ $supplier->id }}" {{ old('supplier_id') == $supplier->id ? 'selected' : '' }}>
                                {{ $supplier->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                        Dispatched From Location <span class="text-rose-500">*</span>
                    </label>
                    <select name="store_id" required class="w-full text-xs rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white px-3 py-2">
                        @foreach($stores as $store)
                            <option value="{{ $store->id }}" {{ old('store_id', 1) == $store->id ? 'selected' : '' }}>
                                {{ $store->name }} ({{ $store->code }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                        Settlement Type <span class="text-rose-500">*</span>
                    </label>
                    <select name="settlement_type" required class="w-full text-xs rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white px-3 py-2">
                        <option value="credit_note">Credit Note (Offset Future Bills)</option>
                        <option value="bank_refund">Bank Transfer Refund</option>
                        <option value="cash_refund">Cash Refund</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                        Credit Note / Debit Note Reference #
                    </label>
                    <input type="text" name="settlement_reference" placeholder="e.g. CN-APEX-982"
                           class="w-full text-xs rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white px-3 py-2 font-mono">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                        Return Reason / Notes
                    </label>
                    <input type="text" name="notes" placeholder="e.g. Color bleed detected on washed samples"
                           class="w-full text-xs rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white px-3 py-2">
                </div>
            </div>
        </div>

        <!-- Items Table -->
        <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
            <div class="p-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                <div>
                    <h2 class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-200">
                        Garments Dispatched Back to Supplier
                    </h2>
                    <p class="text-[11px] text-slate-400">Inventory will be deducted from selected warehouse/store.</p>
                </div>
                <button type="button" @click="addItem()" class="px-3 py-1.5 rounded-lg bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-950/70 text-indigo-700 dark:text-indigo-300 text-xs font-bold transition flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    <span>Add Item</span>
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="bg-slate-50 dark:bg-slate-800/60 text-slate-600 dark:text-slate-300 font-bold border-b border-slate-100 dark:border-slate-800">
                            <th class="p-3">Garment Variant</th>
                            <th class="p-3 w-28 text-right">Return Qty</th>
                            <th class="p-3 w-32 text-right">Unit Rate (৳)</th>
                            <th class="p-3 w-36">Condition</th>
                            <th class="p-3 min-w-[180px]">Discrepancy / Fault Reason</th>
                            <th class="p-3 w-10 text-center"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        <template x-for="(item, index) in items" :key="index">
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30">
                                <td class="p-3">
                                    <select :name="'items[' + index + '][product_variant_id]'" x-model="item.product_variant_id" required
                                            class="w-full text-xs rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white px-2.5 py-1.5">
                                        <option value="">Select Variant</option>
                                        @foreach($variants as $v)
                                            <option value="{{ $v->id }}">
                                                {{ $v->product->name }} ({{ $v->color ?? 'Standard' }}/{{ $v->size ?? 'Free' }} - {{ $v->sku }})
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="p-3 text-right">
                                    <input type="number" min="1" :name="'items[' + index + '][quantity]'" x-model.number="item.quantity" required
                                           class="w-full text-right text-xs rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white px-2 py-1.5 font-mono">
                                </td>
                                <td class="p-3 text-right">
                                    <input type="number" step="0.01" min="0" :name="'items[' + index + '][unit_cost]'" x-model.number="item.unit_cost" required
                                           class="w-full text-right text-xs rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white px-2 py-1.5 font-mono">
                                </td>
                                <td class="p-3">
                                    <select :name="'items[' + index + '][condition]'" x-model="item.condition" class="w-full text-xs rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white px-2 py-1.5">
                                        <option value="damaged">Damaged / Defective</option>
                                        <option value="excess">Excess / Cancelled</option>
                                    </select>
                                </td>
                                <td class="p-3">
                                    <input type="text" :name="'items[' + index + '][reason]'" x-model="item.reason" placeholder="Specific reason..."
                                           class="w-full text-xs rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white px-2 py-1.5">
                                </td>
                                <td class="p-3 text-center">
                                    <button type="button" @click="removeItem(index)" class="text-rose-500 hover:text-rose-700 p-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <div class="p-4 bg-slate-50 dark:bg-slate-800/60 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-xs">
                <span class="text-slate-500">Total Return Units: <strong class="font-mono text-slate-800 dark:text-slate-200" x-text="totalQty() + ' pcs'"></strong></span>
                <span class="font-mono font-bold text-slate-900 dark:text-white">Estimated Debit Value: ৳<span x-text="totalValue().toFixed(2)"></span></span>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('supplier-returns.index') }}" class="px-4 py-2 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-semibold">
                Cancel
            </a>
            <button type="submit" class="px-6 py-2.5 rounded-lg bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold shadow-md shadow-rose-600/30 transition">
                Confirm Return & Generate Debit Note
            </button>
        </div>
    </form>
</div>

<script>
function supplierReturnForm() {
    return {
        items: [
            { product_variant_id: '', quantity: 10, unit_cost: 300, condition: 'damaged', reason: 'Defective stitching' }
        ],
        addItem() {
            this.items.push({ product_variant_id: '', quantity: 5, unit_cost: 0, condition: 'damaged', reason: '' });
        },
        removeItem(index) {
            if (this.items.length > 1) {
                this.items.splice(index, 1);
            }
        },
        totalQty() {
            return this.items.reduce((sum, it) => sum + Number(it.quantity || 0), 0);
        },
        totalValue() {
            return this.items.reduce((sum, it) => sum + (Number(it.quantity || 0) * Number(it.unit_cost || 0)), 0);
        }
    }
}
</script>
@endsection
