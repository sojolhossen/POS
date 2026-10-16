@extends('layouts.app')

@section('title', __('messages.products'))
@section('header', __('messages.products'))

@section('content')
<div class="space-y-6">

    <!-- Header Actions -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold text-slate-900 dark:text-white">{{ __('messages.products') }}</h2>
            <p class="text-xs text-slate-500 mt-0.5">Centralized Product Catalog with Auto-generated SKUs & Code 128 Barcodes</p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('barcodes.index') }}" class="py-2.5 px-4 rounded-xl font-bold text-xs bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 transition">
                Barcode Workstation
            </a>
            @if(auth()->user()->isSuperAdmin() || auth()->user()->isStoreManager())
                <a href="{{ route('products.create') }}" 
                   class="inline-flex items-center gap-2 py-2.5 px-4 rounded-xl font-bold text-xs text-white bg-indigo-600 hover:bg-indigo-700 transition shadow-lg shadow-indigo-600/30">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    <span>+ Add Product & Variants</span>
                </a>
            @endif
        </div>
    </div>

    <!-- Filter Card & Table -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm overflow-hidden">
        
        <div class="p-4 border-b border-slate-100 dark:border-slate-800 flex flex-wrap items-center justify-between gap-3">
            <form method="GET" action="{{ route('products.index') }}" class="flex flex-wrap items-center gap-2 max-w-xl w-full">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search style name, SKU, brand, or barcode..."
                       class="flex-1 min-w-[200px] text-xs rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 py-2 px-3 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                
                <select name="category_id" class="text-xs rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 py-2 px-3">
                    <option value="">All Categories</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>
                            {{ $cat->name }}
                        </option>
                    @endforeach
                </select>

                <button type="submit" class="py-2 px-3.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-xs font-semibold hover:bg-slate-200">
                    Filter
                </button>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-slate-200 dark:border-slate-800 text-slate-400 uppercase text-[10px] tracking-wider font-bold">
                        <th class="py-3.5 px-4">SKU Code</th>
                        <th class="py-3.5 px-4">Product Name & Category</th>
                        <th class="py-3.5 px-4">Brand / Season</th>
                        <th class="py-3.5 px-4">Fabric</th>
                        <th class="py-3.5 px-4 text-right">Base Cost</th>
                        <th class="py-3.5 px-4 text-right">Selling Price (MRP)</th>
                        <th class="py-3.5 px-4 text-center">Variants</th>
                        <th class="py-3.5 px-4">Status</th>
                        <th class="py-3.5 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800 font-medium">
                    @forelse($products as $product)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition">
                            <td class="py-3.5 px-4 font-mono font-black text-indigo-600 dark:text-indigo-400 text-sm">
                                {{ $product->sku }}
                            </td>
                            <td class="py-3.5 px-4 font-bold text-slate-900 dark:text-white">
                                <a href="{{ route('products.show', $product) }}" class="hover:underline">
                                    {{ $product->name }}
                                </a>
                                <div class="text-[11px] font-normal text-slate-400">
                                    {{ $product->category->name }}
                                    @if($product->subcategory)
                                        › {{ $product->subcategory->name }}
                                    @endif
                                </div>
                            </td>
                            <td class="py-3.5 px-4 text-slate-600 dark:text-slate-300">
                                <div>{{ $product->brand ?? '—' }}</div>
                                <div class="text-[10px] text-slate-400">{{ $product->season ?? 'All Season' }}</div>
                            </td>
                            <td class="py-3.5 px-4 text-slate-500">
                                {{ $product->fabric ?? '—' }}
                            </td>
                            <td class="py-3.5 px-4 text-right text-slate-500 font-mono">
                                @bdt($product->base_purchase_price)
                            </td>
                            <td class="py-3.5 px-4 text-right font-black text-indigo-600 dark:text-indigo-400 font-mono text-sm">
                                @bdt($product->base_selling_price)
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                                    {{ $product->variants_count }} Variants
                                </span>
                            </td>
                            <td class="py-3.5 px-4">
                                @if($product->is_active)
                                    <span class="text-emerald-600 font-bold text-[11px] flex items-center gap-1">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Active
                                    </span>
                                @else
                                    <span class="text-slate-400 font-bold text-[11px] flex items-center gap-1">
                                        <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span> Inactive
                                    </span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 text-right">
                                <a href="{{ route('products.show', $product) }}" 
                                   class="py-1 px-3 rounded-lg bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 text-xs font-bold hover:bg-indigo-100">
                                    Manage
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="py-8 text-center text-slate-400">No products found matching criteria.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t border-slate-100 dark:border-slate-800">
            {{ $products->links() }}
        </div>
    </div>

</div>
@endsection
