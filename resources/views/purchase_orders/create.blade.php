@extends('layouts.app')

@section('title', 'Create Purchase Order - ' . __('messages.purchase_orders'))
@section('header', 'Create Purchase Order')

@section('content')
<div class="max-w-5xl mx-auto space-y-6" x-data="poForm()">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-black text-slate-900 dark:text-white">Create Garment Purchase Order</h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                Draft order terms. Submitting requires Super Admin approval before receiving into warehouse.
            </p>
        </div>
        <a href="{{ route('purchase-orders.index') }}" class="px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 text-slate-600 dark:text-slate-300 text-xs font-semibold">
            ← Back to Orders
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

    <form method="POST" action="{{ route('purchase-orders.store') }}" class="space-y-6">
        @csrf

        <!-- General Details Card -->
        <div class="bg-white dark:bg-slate-900 rounded-xl p-6 border border-slate-200 dark:border-slate-800 shadow-sm space-y-4">
            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-200 border-b border-slate-100 dark:border-slate-800 pb-2">
                Order Particulars
            </h2>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                        Garment Supplier <span class="text-rose-500">*</span>
                    </label>
                    <select name="supplier_id" required class="w-full text-xs rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white px-3 py-2">
                        <option value="">Select Active Supplier</option>
                        @foreach($suppliers as $supplier)
                            <option value="{{ $supplier->id }}" {{ (old('supplier_id', request('supplier_id')) == $supplier->id) ? 'selected' : '' }}>
                                {{ $supplier->name }} ({{ $supplier->phone }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                        Destination Warehouse / Store <span class="text-rose-500">*</span>
                    </label>
                    <input type="hidden" name="destination_store_id" value="{{ $warehouse->id }}">
                    <input type="text" readonly value="{{ $warehouse->name }} ({{ $warehouse->code }})"
                           class="w-full text-xs rounded-lg border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50 dark:text-slate-300 px-3 py-2 cursor-not-allowed">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                        Additional Costs (Transport, Freight) ৳
                    </label>
                    <input type="number" step="0.01" min="0" name="additional_costs" x-model.number="additionalCosts"
                           placeholder="0.00"
                           class="w-full text-xs rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white px-3 py-2 font-mono">
                    <p class="text-[10px] text-slate-400 mt-0.5">Allocated proportionally via WAC upon receipt</p>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                    Order Terms & Internal Notes
                </label>
                <textarea name="notes" rows="2" placeholder="e.g. 50% advance upon cutting, balance within 30 days after receiving"
                          class="w-full text-xs rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white px-3 py-2">{{ old('notes') }}</textarea>
            </div>
        </div>

        <!-- Line Items Table -->
        <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
            <div class="p-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                <div>
                    <h2 class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-200">
                        Garment Variants & Order Quantities
                    </h2>
                    <p class="text-[11px] text-slate-400">Specify size/color variants, target order quantities, and unit wholesale cost.</p>
                </div>
                <button type="button" @click="addItem()" class="px-3 py-1.5 rounded-lg bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-950/70 dark:hover:bg-indigo-900 text-indigo-700 dark:text-indigo-300 text-xs font-bold transition flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    <span>Add Item</span>
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="bg-slate-50 dark:bg-slate-800/60 text-slate-600 dark:text-slate-300 font-bold border-b border-slate-100 dark:border-slate-800">
                            <th class="p-3 w-10 text-center">#</th>
                            <th class="p-3 min-w-[280px]">Product & Variant (Color, Size, SKU)</th>
                            <th class="p-3 w-32 text-right">Order Qty</th>
                            <th class="p-3 w-36 text-right">Unit Cost (৳)</th>
                            <th class="p-3 w-36 text-right">Subtotal (৳)</th>
                            <th class="p-3 w-12 text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        <template x-for="(item, index) in items" :key="index">
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30">
                                <td class="p-3 text-center text-slate-400 font-mono" x-text="index + 1"></td>
                                <td class="p-3">
                                    <select :name="'items[' + index + '][product_variant_id]'" x-model="item.product_variant_id" required
                                            class="w-full text-xs rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white px-2.5 py-1.5">
                                        <option value="">Select Variant</option>
                                        @foreach($variants as $v)
                                            <option value="{{ $v->id }}">
                                                {{ $v->product->name }} • {{ $v->color ?? 'Standard' }} / {{ $v->size ?? 'Free' }} [{{ $v->sku }}]
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="p-3 text-right">
                                    <input type="number" min="1" :name="'items[' + index + '][quantity]'" x-model.number="item.quantity" required
                                           class="w-full text-right text-xs rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white px-2.5 py-1.5 font-mono">
                                </td>
                                <td class="p-3 text-right">
                                    <input type="number" step="0.01" min="0" :name="'items[' + index + '][unit_cost]'" x-model.number="item.unit_cost" required
                                           class="w-full text-right text-xs rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white px-2.5 py-1.5 font-mono">
                                </td>
                                <td class="p-3 text-right font-mono font-bold text-slate-900 dark:text-white"
                                    x-text="'৳' + (Number(item.quantity || 0) * Number(item.unit_cost || 0)).toFixed(2)">
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

            <!-- Totals Footer -->
            <div class="p-4 bg-slate-50 dark:bg-slate-800/60 border-t border-slate-100 dark:border-slate-800 flex flex-col sm:flex-row items-center justify-between gap-4">
                <div class="text-xs text-slate-500">
                    Total Order Units: <strong class="font-mono text-slate-800 dark:text-slate-200" x-text="totalQty()"></strong> pcs
                </div>

                <div class="text-right space-y-1 font-mono">
                    <div class="text-xs text-slate-500">
                        Item Subtotal: ৳<span x-text="itemsSubtotal().toFixed(2)"></span>
                    </div>
                    <div class="text-xs text-slate-500" x-show="additionalCosts > 0">
                        Additional Freight/Cost: ৳<span x-text="Number(additionalCosts).toFixed(2)"></span>
                    </div>
                    <div class="text-sm font-black text-indigo-600 dark:text-indigo-400">
                        Estimated Bill Total: ৳<span x-text="grandTotal().toFixed(2)"></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('purchase-orders.index') }}" class="px-4 py-2 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-semibold">
                Cancel
            </a>
            <button type="submit" class="px-6 py-2.5 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold shadow-md shadow-indigo-600/30 transition">
                Submit Purchase Order
            </button>
        </div>
    </form>
</div>

<script>
function poForm() {
    return {
        additionalCosts: 0.0,
        items: [
            { product_variant_id: '', quantity: 50, unit_cost: 350.00 }
        ],
        addItem() {
            this.items.push({ product_variant_id: '', quantity: 20, unit_cost: 0.00 });
        },
        removeItem(index) {
            if (this.items.length > 1) {
                this.items.splice(index, 1);
            }
        },
        itemsSubtotal() {
            return this.items.reduce((sum, item) => sum + (Number(item.quantity || 0) * Number(item.unit_cost || 0)), 0);
        },
        totalQty() {
            return this.items.reduce((sum, item) => sum + Number(item.quantity || 0), 0);
        },
        grandTotal() {
            return this.itemsSubtotal() + Number(this.additionalCosts || 0);
        }
    }
}
</script>
@endsection
