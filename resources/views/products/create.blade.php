@extends('layouts.app')

@section('title', 'Add New Product Style & Variants')
@section('header', 'Add New Product')

@section('content')
<div class="max-w-4xl mx-auto space-y-6" x-data="productBuilder()">

    <!-- Top Breadcrumb & Header -->
    <div class="flex items-center justify-between">
        <a href="{{ route('products.index') }}" class="text-xs font-bold text-slate-500 hover:text-indigo-600 flex items-center gap-1.5 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            <span>Back to Products Catalog</span>
        </a>
    </div>

    <form action="{{ route('products.store') }}" method="POST" class="space-y-6">
        @csrf

        <!-- CARD 1: PRODUCT GENERAL INFO & PRICING -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 sm:p-8 border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-6">
            <div class="pb-4 border-b border-slate-100 dark:border-slate-800 flex items-center gap-3">
                <div class="w-8 h-8 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-bold">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                </div>
                <div>
                    <h2 class="text-base font-bold text-slate-900 dark:text-white">Product Specification & Categorization</h2>
                    <p class="text-xs text-slate-500">Provide product name, category to determine SKU sequence prefix, and baseline cost.</p>
                </div>
            </div>

            <div class="space-y-4">
                <!-- Product Style Name -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">Product Style Name *</label>
                    <input type="text" name="name" value="{{ old('name') }}" required placeholder="e.g. Slim-Fit Cotton Oxford Shirt"
                           class="w-full text-xs font-medium rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 py-2.5 px-3.5 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                </div>

                <!-- Category & Subcategory -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">Category (SKU Prefix) *</label>
                        <select name="category_id" required class="w-full text-xs rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 py-2.5 px-3.5 font-medium">
                            <option value="">-- Select Category --</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }} (Prefix: {{ $category->code }})
                                </option>
                            @endforeach
                        </select>
                        <p class="text-[10px] text-slate-400 mt-1">Unique SKU (e.g. SH-000123) is automatically generated sequentially.</p>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">Subcategory (Optional)</label>
                        <select name="subcategory_id" class="w-full text-xs rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 py-2.5 px-3.5 font-medium">
                            <option value="">-- None --</option>
                            @foreach($categories as $category)
                                @foreach($category->subcategories as $sub)
                                    <option value="{{ $sub->id }}" {{ old('subcategory_id') == $sub->id ? 'selected' : '' }}>
                                        {{ $category->name }} › {{ $sub->name }}
                                    </option>
                                @endforeach
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Brand, Season, Fabric -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">Brand</label>
                        <input type="text" name="brand" value="{{ old('brand') }}" placeholder="e.g. Heritage Bengal"
                               class="w-full text-xs rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 py-2.5 px-3.5">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">Season / Collection</label>
                        <input type="text" name="season" value="{{ old('season', 'Summer 2026') }}" placeholder="e.g. Eid 2026"
                               class="w-full text-xs rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 py-2.5 px-3.5">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">Fabric / Material</label>
                        <input type="text" name="fabric" value="{{ old('fabric') }}" placeholder="e.g. 100% Combed Cotton"
                               class="w-full text-xs rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 py-2.5 px-3.5">
                    </div>
                </div>

                <!-- Base Pricing Matrix -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 p-4 rounded-2xl bg-indigo-50/40 dark:bg-indigo-950/20 border border-indigo-200/50 dark:border-indigo-800/40">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">Base Purchase Cost (BDT ৳) *</label>
                        <div class="relative">
                            <span class="absolute left-3 top-2.5 text-xs text-slate-400 font-bold">৳</span>
                            <input type="number" step="0.01" name="base_purchase_price" value="{{ old('base_purchase_price', '0.00') }}" required
                                   class="w-full text-xs font-mono font-bold rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 py-2.5 pl-7 pr-3.5 focus:ring-2 focus:ring-indigo-500">
                        </div>
                        <p class="text-[10px] text-slate-400 mt-1">Default factory unit cost. Can be overridden per variant.</p>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">Base Selling Price MRP (BDT ৳) *</label>
                        <div class="relative">
                            <span class="absolute left-3 top-2.5 text-xs text-indigo-500 font-bold">৳</span>
                            <input type="number" step="0.01" name="base_selling_price" value="{{ old('base_selling_price', '0.00') }}" required
                                   class="w-full text-xs font-mono font-bold text-indigo-600 dark:text-indigo-400 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 py-2.5 pl-7 pr-3.5 focus:ring-2 focus:ring-indigo-500">
                        </div>
                        <p class="text-[10px] text-slate-400 mt-1">Selling retail price printed on barcode labels.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- CARD 2: VARIANTS & INITIAL STOCK INTAKE (Upgraded Clean Design) -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 sm:p-8 border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-5">
            
            <!-- Section Header & Total Stock Counter -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-slate-100 dark:border-slate-800">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center font-bold">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                    </div>
                    <div>
                        <h3 class="font-bold text-base text-slate-900 dark:text-white">Product Variants & Stock Intake</h3>
                        <p class="text-xs text-slate-500">Define color & size variants and specify initial piece quantities.</p>
                    </div>
                </div>

                <div class="flex items-center gap-2 self-start sm:self-auto">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl font-bold text-xs bg-emerald-50 dark:bg-emerald-950/50 text-emerald-700 dark:text-emerald-300 border border-emerald-200/60 dark:border-emerald-800/60">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        Total Stock: <span class="font-mono text-sm font-black" x-text="totalQuantity()"></span> pcs
                    </span>
                </div>
            </div>

            <!-- Integrated Store Selection -->
            <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200/70 dark:border-slate-700/60 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 flex items-center justify-center text-slate-600 dark:text-slate-300 shrink-0 shadow-xs">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-900 dark:text-white">Initial Stock Intake Location *</label>
                        <p class="text-[11px] text-slate-400">Recorded directly to this warehouse or branch's stock ledger upon saving.</p>
                    </div>
                </div>

                <div class="sm:w-80">
                    <select name="store_id" class="w-full text-xs font-bold rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 py-2.5 px-3.5 text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500">
                        @foreach($stores as $st)
                            <option value="{{ $st->id }}" {{ (auth()->user()->store_id == $st->id || ($loop->first && !auth()->user()->store_id)) ? 'selected' : '' }}>
                                {{ $st->name }} ({{ $st->is_warehouse ? 'Central Warehouse' : 'Branch Store' }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Clean Variants Matrix Table -->
            <div class="border border-slate-200/80 dark:border-slate-800 rounded-2xl overflow-hidden shadow-xs">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="bg-slate-50 dark:bg-slate-800/80 border-b border-slate-200/80 dark:border-slate-800 text-slate-500 dark:text-slate-400 font-bold uppercase text-[10px] tracking-wider">
                            <th class="py-3 px-3 w-10 text-center">#</th>
                            <th class="py-3 px-4">Color *</th>
                            <th class="py-3 px-3 w-28 text-center">Size *</th>
                            <th class="py-3 px-4 w-40 text-center">Pieces / Qty (pcs)</th>
                            <th class="py-3 px-4 w-40 text-right">MRP Override (Opt)</th>
                            <th class="py-3 px-3 w-12 text-center"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800 font-medium">
                        <template x-for="(v, idx) in variants" :key="idx">
                            <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition">
                                <!-- Row Number -->
                                <td class="py-3 px-3 text-center font-mono text-slate-400 text-[11px]" x-text="idx + 1"></td>
                                
                                <!-- Color Input -->
                                <td class="py-3 px-4">
                                    <input type="text" :name="'variants[' + idx + '][color]'" x-model="v.color" required placeholder="e.g. Black, Navy, Maroon"
                                           class="w-full text-xs font-semibold rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 py-2 px-3 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                                </td>

                                <!-- Size Input -->
                                <td class="py-3 px-3">
                                    <input type="text" :name="'variants[' + idx + '][size]'" x-model="v.size" required placeholder="M, L, XL, 32"
                                           class="w-full text-xs font-bold rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 py-2 px-3 text-center focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                                </td>

                                <!-- Quantity / Pieces (Defaults to 0) -->
                                <td class="py-3 px-4 text-center">
                                    <div class="flex items-center justify-center">
                                        <input type="number" min="0" step="1" :name="'variants[' + idx + '][quantity]'" x-model.number="v.quantity"
                                               class="w-28 text-center text-xs font-mono font-bold rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 py-2 px-2 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                                               :class="v.quantity > 0 ? 'border-emerald-500 dark:border-emerald-600 bg-emerald-50/30 text-emerald-700 dark:text-emerald-300 font-black' : 'text-slate-500 dark:text-slate-400'">
                                    </div>
                                </td>

                                <!-- Selling Price Override -->
                                <td class="py-3 px-4 text-right">
                                    <div class="relative">
                                        <span class="absolute left-2.5 top-2 text-[11px] text-slate-400">৳</span>
                                        <input type="number" step="0.01" :name="'variants[' + idx + '][selling_price]'" x-model="v.selling_price" placeholder="Default MRP"
                                               class="w-full text-xs font-mono rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 py-2 pl-6 pr-2.5 text-right focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                                    </div>
                                </td>

                                <!-- Remove Action -->
                                <td class="py-3 px-3 text-center">
                                    <button type="button" @click="removeVariantRow(idx)" :disabled="variants.length === 1"
                                            class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 disabled:opacity-20 disabled:hover:text-slate-400 transition" title="Remove Variant">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>

                <!-- Table Bottom Toolbar with Quick Actions -->
                <div class="p-3 bg-slate-50/70 dark:bg-slate-800/50 border-t border-slate-100 dark:border-slate-800 flex flex-wrap items-center justify-between gap-3">
                    <div class="flex flex-wrap items-center gap-2">
                        <button type="button" @click="addVariantRow()" 
                                class="inline-flex items-center gap-1.5 py-1.5 px-3 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs transition shadow-sm">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            <span>Add Variant Row</span>
                        </button>

                        <button type="button" @click="addCommonSizes()"
                                class="py-1.5 px-3 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-semibold text-xs hover:bg-slate-100 dark:hover:bg-slate-700 transition">
                            + Add Common Sizes (S, M, L, XL, XXL)
                        </button>
                    </div>

                    <div class="text-[11px] text-slate-400 font-medium">
                        Each variant automatically receives a unique Code 128 barcode upon saving.
                    </div>
                </div>
            </div>

        </div>

        <!-- Form Submit & Cancel Actions -->
        <div class="flex items-center justify-between pt-2">
            <div class="text-xs font-semibold text-slate-500">
                Total Initial Stock: <strong class="text-slate-900 dark:text-white font-mono" x-text="totalQuantity() + ' pieces'"></strong>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('products.index') }}" class="py-2.5 px-4 rounded-xl text-xs font-bold text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                    Cancel
                </a>
                <button type="submit" class="py-2.5 px-6 rounded-xl font-bold text-xs text-white bg-indigo-600 hover:bg-indigo-700 transition shadow-lg shadow-indigo-600/30">
                    Save Product, Barcodes & Initial Stock
                </button>
            </div>
        </div>

    </form>

</div>

@push('scripts')
<script>
function productBuilder() {
    return {
        variants: [
            { color: 'Black', size: 'M', quantity: 0, selling_price: '' },
            { color: 'Black', size: 'L', quantity: 0, selling_price: '' },
            { color: 'Navy Blue', size: 'M', quantity: 0, selling_price: '' },
            { color: 'Navy Blue', size: 'L', quantity: 0, selling_price: '' }
        ],

        totalQuantity() {
            return this.variants.reduce((sum, v) => sum + (parseInt(v.quantity, 10) || 0), 0);
        },

        addVariantRow() {
            const lastColor = this.variants.length > 0 ? this.variants[this.variants.length - 1].color : '';
            this.variants.push({ color: lastColor, size: '', quantity: 0, selling_price: '' });
        },

        addCommonSizes() {
            const baseColor = this.variants.length > 0 && this.variants[0].color ? this.variants[0].color : 'Black';
            const sizes = ['S', 'M', 'L', 'XL', 'XXL'];
            sizes.forEach(sz => {
                const exists = this.variants.some(v => v.color.toLowerCase() === baseColor.toLowerCase() && v.size.toUpperCase() === sz);
                if (!exists) {
                    this.variants.push({ color: baseColor, size: sz, quantity: 0, selling_price: '' });
                }
            });
        },

        removeVariantRow(idx) {
            if (this.variants.length > 1) {
                this.variants.splice(idx, 1);
            }
        }
    };
}
</script>
@endpush
@endsection
