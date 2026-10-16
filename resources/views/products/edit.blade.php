@extends('layouts.app')

@section('title', 'Edit Product — ' . $product->name)
@section('header', 'Edit Product Metadata')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">

    <div class="flex items-center justify-between">
        <a href="{{ route('products.show', $product) }}" class="text-xs font-bold text-slate-500 hover:text-indigo-600 flex items-center gap-1">
            ← Back to Product
        </a>
    </div>

    <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 sm:p-8 border border-slate-200/80 dark:border-slate-800 shadow-sm">
        <h2 class="text-lg font-bold text-slate-900 dark:text-white mb-6">Edit Metadata: {{ $product->name }} ({{ $product->sku }})</h2>

        <form action="{{ route('products.update', $product) }}" method="POST" class="space-y-5">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">Product Style Name *</label>
                <input type="text" name="name" value="{{ old('name', $product->name) }}" required
                       class="w-full text-xs rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 py-2.5 px-3.5 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">Brand</label>
                    <input type="text" name="brand" value="{{ old('brand', $product->brand) }}"
                           class="w-full text-xs rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 py-2.5 px-3.5">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">Season</label>
                    <input type="text" name="season" value="{{ old('season', $product->season) }}"
                           class="w-full text-xs rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 py-2.5 px-3.5">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">Fabric</label>
                    <input type="text" name="fabric" value="{{ old('fabric', $product->fabric) }}"
                           class="w-full text-xs rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 py-2.5 px-3.5">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">Base Purchase Cost (BDT ৳) *</label>
                <input type="number" step="0.01" name="base_purchase_price" value="{{ old('base_purchase_price', $product->base_purchase_price) }}" required
                       class="w-full text-xs font-mono font-bold rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 py-2.5 px-3.5">
            </div>

            <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/60 dark:border-slate-700/60">
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $product->is_active) ? 'checked' : '' }}
                           class="rounded text-indigo-600 focus:ring-indigo-500 w-4 h-4">
                    <div>
                        <span class="text-xs font-bold text-slate-900 dark:text-white">Active Product</span>
                        <p class="text-[11px] text-slate-500">Active products appear in store inventory lookups and barcode printing.</p>
                    </div>
                </label>
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
                <a href="{{ route('products.show', $product) }}" class="py-2.5 px-4 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100">
                    Cancel
                </a>
                <button type="submit" class="py-2.5 px-6 rounded-xl font-bold text-xs text-white bg-indigo-600 hover:bg-indigo-700 transition shadow-lg shadow-indigo-600/30">
                    Update Product
                </button>
            </div>
        </form>
    </div>

</div>
@endsection
