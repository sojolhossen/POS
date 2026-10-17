@extends('layouts.app')

@section('title', $product->name)
@section('header', 'Product Style: ' . $product->name)

@section('content')
<div class="space-y-6">

    <div class="flex items-center justify-between">
        <a href="{{ route('products.index') }}" class="text-xs font-bold text-slate-500 hover:text-indigo-600 flex items-center gap-1">
            ← Back to Products Catalog
        </a>

        @if(auth()->user()->isSuperAdmin() || auth()->user()->isStoreManager())
            <a href="{{ route('products.edit', $product) }}" class="py-2 px-4 rounded-xl bg-slate-100 dark:bg-slate-800 text-xs font-bold text-slate-700 dark:text-slate-300 hover:bg-slate-200">
                Edit Metadata
            </a>
        @endif
    </div>

    <!-- PRODUCT MASTER CARD -->
    <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 sm:p-8 border border-slate-200/80 dark:border-slate-800 shadow-sm">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-6 border-b border-slate-100 dark:border-slate-800">
            <div>
                <div class="flex items-center gap-3">
                    <span class="font-mono font-black text-sm px-2.5 py-1 rounded-lg bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400">
                        {{ $product->sku }}
                    </span>
                    <h2 class="text-2xl font-black text-slate-900 dark:text-white">{{ $product->name }}</h2>
                </div>
                <div class="text-xs text-slate-500 mt-1">
                    {{ $product->category->name }}
                    @if($product->subcategory)
                        › {{ $product->subcategory->name }}
                    @endif
                    • Brand: {{ $product->brand ?? 'Unbranded' }} • Season: {{ $product->season ?? 'All Season' }}
                </div>
            </div>

            <div class="flex items-center gap-4">
                <div class="text-right">
                    <div class="text-[10px] uppercase font-bold text-slate-400">Base Selling MRP</div>
                    <div class="text-2xl font-black text-indigo-600 dark:text-indigo-400">@bdt($product->base_selling_price)</div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 pt-6 text-xs">
            <div>
                <div class="text-slate-400 uppercase font-bold text-[10px]">Fabric / Material</div>
                <div class="font-bold text-slate-800 dark:text-slate-200 mt-0.5">{{ $product->fabric ?? '—' }}</div>
            </div>
            <div>
                <div class="text-slate-400 uppercase font-bold text-[10px]">Purchase Cost</div>
                <div class="font-bold text-slate-800 dark:text-slate-200 mt-0.5 font-mono">@bdt($product->base_purchase_price)</div>
            </div>
            <div>
                <div class="text-slate-400 uppercase font-bold text-[10px]">Status</div>
                <div class="mt-0.5">
                    @if($product->is_active)
                        <span class="text-emerald-600 font-bold">Active</span>
                    @else
                        <span class="text-slate-400 font-bold">Inactive</span>
                    @endif
                </div>
            </div>
            <div>
                <div class="text-slate-400 uppercase font-bold text-[10px]">Created Date</div>
                <div class="font-bold text-slate-800 dark:text-slate-200 mt-0.5">@posDate($product->created_at)</div>
            </div>
        </div>
    </div>

    <!-- VARIANTS & CODE 128 BARCODES TABLE -->
    <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 sm:p-8 border border-slate-200/80 dark:border-slate-800 shadow-sm">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
            <div>
                <h3 class="text-base font-bold text-slate-900 dark:text-white">Product Variants & Code 128 Barcodes</h3>
                <p class="text-xs text-slate-500">Barcodes are permanently bound to each size/color variant.</p>
            </div>
            <div>
                <a href="{{ route('barcodes.print', ['product_id' => $product->id]) }}" target="_blank"
                   class="inline-flex items-center gap-2 py-2 px-3.5 rounded-xl bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 font-bold text-xs hover:bg-indigo-100 dark:hover:bg-indigo-900/40 transition border border-indigo-200/50 dark:border-indigo-800/50 shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                    <span>Print All Barcodes for this Product</span>
                </a>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-slate-200 dark:border-slate-800 text-slate-400 uppercase text-[10px] tracking-wider font-bold">
                        <th class="py-3 px-3">Variant</th>
                        <th class="py-3 px-3">Barcode Number (Code 128)</th>
                        <th class="py-3 px-3">Barcode Render</th>
                        <th class="py-3 px-3 text-right">Selling Price</th>
                        <th class="py-3 px-3 text-right">Stock On Hand</th>
                        <th class="py-3 px-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800 font-medium">
                    @forelse($product->variants as $variant)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                            <td class="py-3.5 px-3">
                                <span class="px-2.5 py-1 rounded-lg bg-slate-100 dark:bg-slate-800 font-bold text-slate-800 dark:text-slate-200">
                                    {{ $variant->color }} - {{ $variant->size }}
                                </span>
                            </td>
                            <td class="py-3.5 px-3 font-mono font-black text-slate-900 dark:text-white tracking-wider">
                                {{ $variant->barcode }}
                            </td>
                            <td class="py-3.5 px-3">
                                <div class="w-32 bg-white p-1 rounded border border-slate-200 flex justify-center">
                                    {!! \App\Services\BarcodeService::renderSvg($variant->barcode, 1, 28) !!}
                                </div>
                            </td>
                            <td class="py-3.5 px-3 text-right font-black text-indigo-600 dark:text-indigo-400 font-mono text-sm">
                                @bdt($variant->effective_selling_price)
                            </td>
                            <td class="py-3.5 px-3 text-right font-bold text-slate-800 dark:text-slate-200 font-mono">
                                {{ $variant->stockBalances->sum('quantity_on_hand') }} Units
                            </td>
                            <td class="py-3.5 px-3 text-right">
                                <a href="{{ route('barcodes.print', ['single_variant_id' => $variant->id, 'single_quantity' => max(1, (int)$variant->stockBalances->sum('quantity_on_hand'))]) }}" target="_blank"
                                   class="inline-flex items-center gap-1 py-1 px-2.5 rounded-lg bg-slate-100 dark:bg-slate-800 hover:bg-indigo-50 dark:hover:bg-indigo-950/60 hover:text-indigo-600 text-slate-700 dark:text-slate-300 font-bold text-[11px] transition">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                    <span>Print</span>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-6 text-center text-slate-400">No variants created for this product.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Add Variant Form -->
        @if(auth()->user()->isSuperAdmin() || auth()->user()->isStoreManager())
            <div class="mt-6 pt-6 border-t border-slate-100 dark:border-slate-800">
                <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-3">+ Add New Size / Color Variant</h4>
                <form action="{{ route('products.variants.add', $product) }}" method="POST" class="flex flex-wrap items-center gap-3">
                    @csrf
                    <div>
                        <input type="text" name="color" required placeholder="Color (e.g. Maroon)" class="text-xs rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 py-2 px-3">
                    </div>
                    <div>
                        <input type="text" name="size" required placeholder="Size (e.g. XL, 34)" class="text-xs rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 py-2 px-3">
                    </div>
                    <div>
                        <input type="number" step="0.01" name="selling_price" placeholder="Override Price (Opt)" class="text-xs rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 py-2 px-3">
                    </div>
                    <button type="submit" class="py-2 px-4 rounded-xl font-bold text-xs text-white bg-slate-900 dark:bg-slate-700 hover:bg-slate-800">
                        Generate Variant & Barcode
                    </button>
                </form>
            </div>
        @endif
    </div>

    <!-- SELLING PRICE CHANGE (SUPER ADMIN ONLY) & AUDIT HISTORY -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Price Change Gate -->
        @if(auth()->user()->isSuperAdmin())
            <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 border border-indigo-200 dark:border-indigo-800/60 shadow-sm">
                <h3 class="text-sm font-bold text-indigo-900 dark:text-indigo-300 mb-2">Change Selling Price (Super Admin Gate)</h3>
                <p class="text-xs text-slate-500 mb-4">Every price change is strictly recorded with previous price, new price, mandatory reason, and timestamp.</p>

                <form action="{{ route('products.price.update', $product) }}" method="POST" class="space-y-4">
                    @csrf
                    
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Target</label>
                        <select name="product_variant_id" class="w-full text-xs rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 py-2 px-3">
                            <option value="">Base Product (All Variants Default)</option>
                            @foreach($product->variants as $var)
                                <option value="{{ $var->id }}">{{ $var->fullName }} (Current: ৳ {{ number_format($var->effective_selling_price, 2) }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">New Selling Price (BDT ৳) *</label>
                        <input type="number" step="0.01" name="new_price" required min="0" placeholder="e.g. 1850.00"
                               class="w-full text-xs font-mono font-bold text-indigo-600 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 py-2.5 px-3">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Mandatory Change Reason *</label>
                        <textarea name="reason" rows="2" required placeholder="e.g. Eid promotion price revision approved by executive management"
                                  class="w-full text-xs rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 py-2 px-3"></textarea>
                    </div>

                    <button type="submit" class="w-full py-2.5 px-4 rounded-xl font-bold text-xs text-white bg-indigo-600 hover:bg-indigo-700 shadow-md shadow-indigo-600/30">
                        Update Selling Price & Log History
                    </button>
                </form>
            </div>
        @else
            <div class="bg-slate-50 dark:bg-slate-900 rounded-3xl p-6 border border-slate-200 dark:border-slate-800 text-xs text-slate-500">
                <div class="font-bold text-slate-700 dark:text-slate-300 mb-1">Price Modification Restriction</div>
                <p>Only the Super Admin is authorized to change selling prices. Contact central administration for pricing updates.</p>
            </div>
        @endif

        <!-- Price Change Log -->
        <div class="lg:col-span-2 bg-white dark:bg-slate-900 rounded-3xl p-6 border border-slate-200/80 dark:border-slate-800 shadow-sm">
            <h3 class="text-sm font-bold text-slate-900 dark:text-white mb-4">Historical Price Change Log</h3>
            
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-slate-100 dark:border-slate-800 text-slate-400 uppercase text-[10px] font-bold">
                            <th class="py-2.5 px-3">Date</th>
                            <th class="py-2.5 px-3">Variant / Scope</th>
                            <th class="py-2.5 px-3 text-right">Old Price</th>
                            <th class="py-2.5 px-3 text-right">New Price</th>
                            <th class="py-2.5 px-3">Reason</th>
                            <th class="py-2.5 px-3">Authorized By</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse($product->priceHistories as $ph)
                            <tr>
                                <td class="py-2.5 px-3 font-mono text-[11px] text-slate-500">
                                    @posDate($ph->created_at)
                                </td>
                                <td class="py-2.5 px-3 font-semibold text-slate-700 dark:text-slate-300">
                                    {{ $ph->variant ? $ph->variant->fullName : 'Base Product' }}
                                </td>
                                <td class="py-2.5 px-3 text-right font-mono text-slate-400">
                                    @bdt($ph->old_price)
                                </td>
                                <td class="py-2.5 px-3 text-right font-mono font-bold text-indigo-600">
                                    @bdt($ph->new_price)
                                </td>
                                <td class="py-2.5 px-3 text-slate-600 dark:text-slate-400">
                                    {{ $ph->reason }}
                                </td>
                                <td class="py-2.5 px-3 text-slate-500">
                                    {{ $ph->user ? $ph->user->name : 'System' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-6 text-center text-slate-400">No price changes recorded.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>

</div>
@endsection
