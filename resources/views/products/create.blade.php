@extends('layouts.app')

@section('title', 'Add New Product Style & Variants')
@section('header', 'Add New Product')

@section('content')
<div class="max-w-3xl mx-auto space-y-6" x-data="productBuilder()">

    <div class="flex items-center justify-between">
        <a href="{{ route('products.index') }}" class="text-xs font-bold text-slate-500 hover:text-indigo-600 flex items-center gap-1">
            ← Back to Products Catalog
        </a>
    </div>

    <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 sm:p-8 border border-slate-200/80 dark:border-slate-800 shadow-sm">
        <h2 class="text-lg font-bold text-slate-900 dark:text-white mb-6">Create New Clothing Product & Size/Color Variants</h2>

        <form action="{{ route('products.store') }}" method="POST" class="space-y-6">
            @csrf

            <!-- Product Basic Info -->
            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">Product Style Name *</label>
                    <input type="text" name="name" value="{{ old('name') }}" required placeholder="e.g. Slim-Fit Cotton Oxford Shirt"
                           class="w-full text-xs rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 py-2.5 px-3.5 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">Category (Determines SKU Prefix) *</label>
                        <select name="category_id" required class="w-full text-xs rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 py-2.5 px-3.5">
                            <option value="">-- Select Category --</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }} (Prefix: {{ $category->code }})
                                </option>
                            @endforeach
                        </select>
                        <p class="text-[10px] text-slate-400 mt-1">Unique SKU (e.g. SH-000123) is automatically generated upon saving.</p>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">Subcategory (Optional)</label>
                        <select name="subcategory_id" class="w-full text-xs rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 py-2.5 px-3.5">
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

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 p-4 rounded-2xl bg-indigo-50/40 dark:bg-indigo-950/20 border border-indigo-200/50 dark:border-indigo-800/40">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">Base Purchase Price (BDT ৳) *</label>
                        <input type="number" step="0.01" name="base_purchase_price" value="{{ old('base_purchase_price', '0.00') }}" required
                               class="w-full text-xs font-mono font-bold rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 py-2.5 px-3.5">
                        <p class="text-[10px] text-slate-400 mt-1">Default design cost. Can be overridden per variant.</p>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">Base Selling Price MRP (BDT ৳) *</label>
                        <input type="number" step="0.01" name="base_selling_price" value="{{ old('base_selling_price', '0.00') }}" required
                               class="w-full text-xs font-mono font-bold text-indigo-600 dark:text-indigo-400 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 py-2.5 px-3.5">
                        <p class="text-[10px] text-slate-400 mt-1">Selling prices may only be edited later by the Super Admin.</p>
                    </div>
                </div>
            </div>

            <!-- VARIANTS GENERATOR (Size & Color) -->
            <div class="space-y-4 pt-4 border-t border-slate-100 dark:border-slate-800">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="font-bold text-sm text-slate-900 dark:text-white">Product Variants (Size & Color combinations)</h3>
                        <p class="text-[11px] text-slate-400">Each variant automatically receives a unique Code 128 barcode permanently bound to it.</p>
                    </div>
                    <button type="button" @click="addVariantRow()" class="py-1.5 px-3 rounded-lg bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400 font-bold text-xs hover:bg-indigo-100">
                        + Add Variant
                    </button>
                </div>

                <div class="space-y-2">
                    <template x-for="(v, idx) in variants" :key="idx">
                        <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/60 dark:border-slate-700/60 flex flex-wrap sm:flex-nowrap items-center gap-3">
                            <div class="w-full sm:w-1/3">
                                <label class="block text-[10px] font-bold text-slate-400 uppercase mb-0.5">Color</label>
                                <input type="text" :name="'variants[' + idx + '][color]'" x-model="v.color" required placeholder="e.g. Navy Blue"
                                       class="w-full text-xs rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 py-1.5 px-2.5">
                            </div>

                            <div class="w-full sm:w-1/4">
                                <label class="block text-[10px] font-bold text-slate-400 uppercase mb-0.5">Size</label>
                                <input type="text" :name="'variants[' + idx + '][size]'" x-model="v.size" required placeholder="e.g. M, L, 42, 32"
                                       class="w-full text-xs font-bold rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 py-1.5 px-2.5">
                            </div>

                            <div class="w-full sm:w-1/4">
                                <label class="block text-[10px] font-bold text-slate-400 uppercase mb-0.5">Override Price (Opt)</label>
                                <input type="number" step="0.01" :name="'variants[' + idx + '][selling_price]'" x-model="v.selling_price" placeholder="Default MRP"
                                       class="w-full text-xs rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 py-1.5 px-2.5">
                            </div>

                            <div class="pt-3 sm:pt-4">
                                <button type="button" @click="removeVariantRow(idx)" class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 transition" title="Remove Variant">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-6 border-t border-slate-100 dark:border-slate-800">
                <a href="{{ route('products.index') }}" class="py-2.5 px-4 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100">
                    Cancel
                </a>
                <button type="submit" class="py-2.5 px-6 rounded-xl font-bold text-xs text-white bg-indigo-600 hover:bg-indigo-700 transition shadow-lg shadow-indigo-600/30">
                    Save Product & Generate Code 128 Barcodes
                </button>
            </div>
        </form>
    </div>

</div>

@push('scripts')
<script>
function productBuilder() {
    return {
        variants: [
            { color: 'Black', size: 'M', selling_price: '' },
            { color: 'Black', size: 'L', selling_price: '' },
            { color: 'Navy Blue', size: 'M', selling_price: '' },
            { color: 'Navy Blue', size: 'L', selling_price: '' }
        ],
        addVariantRow() {
            this.variants.push({ color: '', size: '', selling_price: '' });
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
