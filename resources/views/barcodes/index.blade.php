@extends('layouts.app')

@section('title', __('messages.barcodes'))
@section('header', __('messages.barcodes'))

@section('content')
<div class="space-y-6" x-data="barcodeWorkstation({
    variants: [
        @foreach($variants as $variant)
        @php
            $storeBal = $variant->stockBalances->firstWhere('store_id', $selectedStore?->id);
            $stockQty = $storeBal ? $storeBal->quantity_on_hand : $variant->stockBalances->sum('quantity_on_hand');
            $searchMeta = strtolower($variant->product->name . ' ' . $variant->product->sku . ' ' . $variant->barcode . ' ' . $variant->color . ' ' . $variant->size . ' ' . ($variant->product->category?->name ?? '') . ' ' . ($variant->product->subcategory?->name ?? ''));
        @endphp
        {
            id: {{ $variant->id }},
            stock: {{ (int) $stockQty }},
            qty: {{ $stockQty > 0 ? (int) $stockQty : 1 }},
            meta: {{ json_encode($searchMeta) }}
        },
        @endforeach
    ],
    selectedStoreId: '{{ $selectedStore?->id ?? '' }}',
    initialSearch: ''
})">

    <!-- TOP CONTROL & SCANNER CARDS (Symmetric, Minimal & Balanced) -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        
        <!-- CARD 1: Hardware / Keyboard Scanner Input -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl p-4 border border-slate-200/80 dark:border-slate-800 shadow-sm flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <div class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-ping"></span>
                        <h3 class="font-bold text-xs uppercase tracking-wider text-slate-800 dark:text-slate-200">Hardware Scanner</h3>
                    </div>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400 border border-emerald-200/60">
                        Ready
                    </span>
                </div>
                <p class="text-[11px] text-slate-400 mb-2.5">
                    Scan with USB/Bluetooth scanner gun, or type:
                </p>
                <!-- Enclosed Input Container (Zero overflow) -->
                <div class="flex items-center rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 p-1 focus-within:ring-2 focus-within:ring-indigo-500 transition">
                    <input type="text" x-model="scannedBarcode" @keydown.enter.prevent="performLookup(scannedBarcode)"
                           placeholder="Type barcode or scan..." 
                           class="flex-1 min-w-0 bg-transparent border-0 py-1 px-2 text-xs font-mono text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-0">
                    <button @click="performLookup(scannedBarcode)" 
                            class="px-3 py-1.5 rounded-lg bg-indigo-600 text-white font-bold text-xs hover:bg-indigo-700 transition shrink-0 shadow-xs">
                        Scan
                    </button>
                </div>
            </div>
            <div class="mt-3 pt-2 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-[10px] text-slate-400">
                <span>Listening for keystrokes in real-time</span>
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
            </div>
        </div>

        <!-- CARD 2: Camera Scanner Section -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl p-4 border border-slate-200/80 dark:border-slate-800 shadow-sm flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <div class="flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <h3 class="font-bold text-xs uppercase tracking-wider text-slate-800 dark:text-slate-200">Camera Scanner</h3>
                    </div>
                </div>
                <p class="text-[11px] text-slate-400 mb-2.5">
                    Scan Code 128 using webcam or mobile camera:
                </p>
                <div id="reader" class="rounded-xl overflow-hidden mb-2 bg-slate-100 dark:bg-slate-800 text-xs text-center" :class="{ 'min-h-[140px]': cameraActive }"></div>
            </div>
            <div>
                <button type="button" @click="toggleCamera()" 
                        class="w-full py-1.5 px-3 rounded-xl font-bold text-xs transition border flex items-center justify-center gap-1.5 shadow-xs"
                        :class="cameraActive ? 'bg-rose-50 text-rose-600 border-rose-200 hover:bg-rose-100' : 'bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 text-slate-700 dark:text-slate-200 border-slate-200 dark:border-slate-700'"
                        x-text="cameraActive ? 'Stop Camera' : 'Start Camera Scanner'">
                    Start Camera Scanner
                </button>
            </div>
        </div>

        <!-- CARD 3: Scanned Product Details -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl p-4 border border-slate-200/80 dark:border-slate-800 shadow-sm flex flex-col justify-between">
            <div>
                <!-- Header -->
                <div class="flex items-center justify-between mb-2">
                    <div class="flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full" :class="scannedResult ? 'bg-emerald-500' : 'bg-slate-300 dark:bg-slate-700'"></span>
                        <h3 class="font-bold text-xs uppercase tracking-wider text-slate-800 dark:text-slate-200">Scanned Details</h3>
                    </div>
                    <template x-if="scannedResult">
                        <a :href="'/products/' + scannedResult?.product_id" target="_blank" 
                           class="text-[11px] font-bold text-indigo-600 dark:text-indigo-400 hover:underline flex items-center gap-1">
                            <span>Catalog</span>
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                        </a>
                    </template>
                </div>

                <!-- Idle State: Minimal Placeholder -->
                <div x-show="!scannedResult && !lookupError" class="py-4 text-center text-slate-400 text-xs flex flex-col items-center justify-center gap-1">
                    <svg class="w-5 h-5 text-slate-300 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                    <span>Scan any barcode label to view product specs & live stock.</span>
                </div>

                <!-- Error State -->
                <div x-show="lookupError" class="p-2.5 rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 text-rose-600 text-xs font-semibold" x-text="lookupError"></div>

                <!-- Scanned Active State (Clean, Cohesive, Modern Layout) -->
                <div x-show="scannedResult" class="space-y-2.5">
                    <!-- Title & SKU Row -->
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0 flex-1">
                            <h4 class="font-extrabold text-sm text-slate-900 dark:text-white truncate" x-text="scannedResult?.product_name"></h4>
                            <div class="text-[11px] text-slate-400 truncate" x-text="scannedResult?.category_name"></div>
                        </div>
                        <span class="px-2 py-0.5 rounded-md bg-slate-100 dark:bg-slate-800 font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 shrink-0" x-text="scannedResult?.sku"></span>
                    </div>

                    <!-- Metrics Badges (Price, Variant, Stock) in One Clean Strip -->
                    <div class="flex flex-wrap items-center gap-1.5 text-xs">
                        <!-- Variant Pill -->
                        <span class="px-2 py-0.5 rounded-md bg-slate-100 dark:bg-slate-800 font-semibold text-slate-700 dark:text-slate-300">
                            <span x-text="scannedResult?.color"></span> • <strong class="text-indigo-600 dark:text-indigo-400" x-text="scannedResult?.size"></strong>
                        </span>

                        <!-- Selling Price Pill -->
                        <span class="px-2 py-0.5 rounded-md bg-indigo-50 dark:bg-indigo-950/50 font-black text-indigo-600 dark:text-indigo-400 font-mono" x-text="scannedResult?.selling_price_formatted"></span>

                        <!-- Live Stock Pill -->
                        <span class="px-2 py-0.5 rounded-md font-bold font-mono inline-flex items-center gap-1"
                              :class="scannedResult?.stock_on_hand > 0 ? 'bg-emerald-50 dark:bg-emerald-950/50 text-emerald-700 dark:text-emerald-300 border border-emerald-200/60' : 'bg-slate-100 text-slate-500'">
                            <span class="w-1.5 h-1.5 rounded-full" :class="scannedResult?.stock_on_hand > 0 ? 'bg-emerald-500' : 'bg-slate-400'"></span>
                            <span x-text="scannedResult?.stock_on_hand + ' pcs in stock'"></span>
                        </span>
                    </div>

                    <!-- Location Breakdown (compact text if multiple stores) -->
                    <template x-if="scannedResult?.stores_breakdown && scannedResult.stores_breakdown.length > 1">
                        <div class="text-[10px] text-slate-400 flex flex-wrap items-center gap-x-2 gap-y-0.5">
                            <span class="font-bold">Locations:</span>
                            <template x-for="st in scannedResult.stores_breakdown" :key="st.store_id">
                                <span class="text-slate-600 dark:text-slate-300">
                                    <span x-text="st.store_name"></span>: <strong class="font-mono text-emerald-600 dark:text-emerald-400" x-text="st.stock_on_hand + ' pcs'"></strong>
                                </span>
                            </template>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Instant Print Action Bar -->
            <div x-show="scannedResult" class="mt-3 pt-2 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between gap-2">
                <div class="flex items-center gap-1">
                    <span class="text-[11px] text-slate-400 font-semibold">Qty:</span>
                    <input type="number" min="1" max="200" x-model.number="quickScanPrintQty"
                           class="w-12 text-center text-xs font-bold rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 py-1 focus:ring-1 focus:ring-indigo-500">
                </div>

                <button type="button" @click="printSingleVariant(scannedResult.id, quickScanPrintQty)"
                        class="py-1 px-3.5 rounded-xl font-bold text-xs text-white bg-indigo-600 hover:bg-indigo-700 transition flex items-center gap-1.5 shadow-sm">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                    <span>Print (<span x-text="quickScanPrintQty"></span>)</span>
                </button>
            </div>
        </div>
    </div>

    <!-- BATCH LABEL PRINTING WORKSTATION -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm overflow-hidden">
        
        <!-- Filter Toolbar -->
        <div class="p-6 border-b border-slate-100 dark:border-slate-800">
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 mb-5">
                <div>
                    <h3 class="font-bold text-base text-slate-900 dark:text-white">Batch Barcode Label Generator</h3>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Filter by category, search products in real-time, and print barcode stickers matching in-stock pieces.
                    </p>
                </div>

                <!-- Print Configuration Settings -->
                <div class="flex flex-wrap items-center gap-3">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Store on Label</label>
                        <select x-model="selectedStoreId" @change="onStoreChange($event.target.value)"
                                class="text-xs rounded-xl border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 py-1.5 px-3 font-semibold">
                            @foreach($stores as $st)
                                <option value="{{ $st->id }}" {{ $selectedStore?->id == $st->id ? 'selected' : '' }}>{{ $st->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Sticker Size (W×H mm)</label>
                        <div class="flex items-center gap-1">
                            <input type="number" x-model.number="labelWidth" min="30" max="100" class="w-14 text-xs text-center font-bold rounded-lg border-slate-300 dark:border-slate-700 py-1.5">
                            <span class="text-xs text-slate-400">×</span>
                            <input type="number" x-model.number="labelHeight" min="20" max="80" class="w-14 text-xs text-center font-bold rounded-lg border-slate-300 dark:border-slate-700 py-1.5">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Real-time Filter Form with Live Search -->
            <form method="GET" action="{{ route('barcodes.index') }}" x-ref="filterForm"
                  class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
                
                <!-- Category Filter (Auto updates on select) -->
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Filter by Category</label>
                    <select name="category_id" @change="submitFilterForm()"
                            class="w-full text-xs rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 py-2 px-3 font-medium">
                        <option value="">All Categories</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                            @foreach($category->subcategories as $sub)
                                <option value="{{ $sub->id }}" {{ request('category_id') == $sub->id ? 'selected' : '' }}>
                                    &nbsp;&nbsp;↳ {{ $sub->name }}
                                </option>
                            @endforeach
                        @endforeach
                    </select>
                </div>

                <!-- Real-time Keyword Search Input with instant 0ms row filter (no page reload while typing) -->
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-[10px] font-bold text-slate-400 uppercase">Search Products</label>
                        <span x-show="liveSearch" class="text-[10px] text-indigo-500 font-bold" x-text="visibleVariantList.length + ' matches'"></span>
                    </div>
                    <div class="relative">
                        <input type="text" name="search" x-model="liveSearch"
                               x-ref="searchInput"
                               autocomplete="off"
                               autocorrect="off"
                               autocapitalize="off"
                               spellcheck="false"
                               @keydown.enter.prevent="submitFilterForm()"
                               placeholder="Type style, SKU, barcode, color, size..."
                               class="w-full text-xs rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 py-2 pl-3 pr-8 focus:ring-2 focus:ring-indigo-500 focus:outline-none font-medium">
                        
                        <!-- Clear Search Icon (X) -->
                        <button type="button" x-show="liveSearch" @click="clearLiveSearch()"
                                class="absolute right-2.5 top-2.5 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition"
                                title="Clear Search">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </div>

                <!-- Stock Filter (Auto updates on select) -->
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Stock Status</label>
                    <select name="stock_status" @change="submitFilterForm()"
                            class="w-full text-xs rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 py-2 px-3 font-medium">
                        <option value="all" {{ request('stock_status', 'all') === 'all' ? 'selected' : '' }}>All Products</option>
                        <option value="in_stock" {{ request('stock_status') === 'in_stock' ? 'selected' : '' }}>In Stock Only (>0 pcs)</option>
                        <option value="out_of_stock" {{ request('stock_status') === 'out_of_stock' ? 'selected' : '' }}>Out of Stock (0 pcs)</option>
                    </select>
                </div>

                <!-- Store Context -->
                <input type="hidden" name="store_id" :value="selectedStoreId">

                <!-- Per Page (Auto updates on select) -->
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Show Per Page</label>
                    <select name="per_page" @change="submitFilterForm()"
                            class="w-full text-xs rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 py-2 px-3 font-medium">
                        <option value="25" {{ request('per_page', 25) == 25 ? 'selected' : '' }}>25 Items</option>
                        <option value="50" {{ request('per_page', 50) == 50 ? 'selected' : '' }}>50 Items</option>
                        <option value="100" {{ request('per_page', 100) == 100 ? 'selected' : '' }}>100 Items</option>
                    </select>
                </div>

                <!-- Submit / Reset Buttons -->
                <div class="flex items-end gap-2">
                    <button type="submit" class="flex-1 py-2 px-4 rounded-xl bg-slate-900 dark:bg-white dark:text-slate-900 text-white font-bold text-xs hover:bg-slate-800 transition">
                        Apply Filter
                    </button>
                    @if(request()->anyFilled(['category_id', 'search', 'stock_status']))
                        <a href="{{ route('barcodes.index', ['store_id' => $selectedStore?->id]) }}" class="py-2 px-3 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 font-bold text-xs hover:bg-slate-200 transition">
                            Reset
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <!-- BATCH FORM WRAPPER -->
        <form action="{{ route('barcodes.print') }}" method="POST" target="_blank" id="batchPrintForm" @submit="onBatchSubmit($event)">
            @csrf
            <input type="hidden" name="store_id" :value="selectedStoreId">
            <input type="hidden" name="label_width" :value="labelWidth">
            <input type="hidden" name="label_height" :value="labelHeight">

            <!-- Selection Controls & Quick Action Bar -->
            <div class="px-6 py-3.5 bg-slate-50/70 dark:bg-slate-850 border-b border-slate-100 dark:border-slate-800 flex flex-wrap items-center justify-between gap-4">
                <div class="flex flex-wrap items-center gap-2">
                    <!-- Toggle All Visible -->
                    <button type="button" @click="toggleSelectAll()" 
                            class="py-1.5 px-3 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-bold text-slate-700 dark:text-slate-200 hover:bg-slate-50 transition"
                            x-text="allSelected ? 'Deselect All' : 'Select All'">
                    </button>

                    <!-- Select In-Stock Only -->
                    <button type="button" @click="selectInStockOnly()"
                            class="py-1.5 px-3 rounded-lg border border-emerald-300 dark:border-emerald-800 bg-emerald-50 dark:bg-emerald-950/40 text-xs font-bold text-emerald-700 dark:text-emerald-300 hover:bg-emerald-100 transition">
                        Select In-Stock Only
                    </button>

                    <!-- Auto-Fill Qty with Stock -->
                    <button type="button" @click="fillQuantitiesFromStock()"
                            class="py-1.5 px-3 rounded-lg border border-indigo-300 dark:border-indigo-800 bg-indigo-50 dark:bg-indigo-950/40 text-xs font-bold text-indigo-700 dark:text-indigo-300 hover:bg-indigo-100 transition flex items-center gap-1.5"
                            title="Set barcode quantity to match exactly how many pieces are in stock for each variant">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        <span>Match Qty to In-Stock Pieces</span>
                    </button>

                    <!-- Set All Qty to 1 -->
                    <button type="button" @click="setAllQuantities(1)"
                            class="py-1.5 px-2.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-medium text-slate-600 dark:text-slate-400 hover:bg-slate-100">
                        Set All Qty to 1
                    </button>
                </div>

                <div class="flex items-center gap-3">
                    <div class="text-xs font-semibold text-slate-500">
                        Selected: <strong class="text-indigo-600 dark:text-indigo-400 font-bold" x-text="selectedCount"></strong> items 
                        (<span class="font-bold text-slate-800 dark:text-slate-200" x-text="totalLabelsCount"></span> total labels)
                    </div>

                    <button type="submit" :disabled="selectedCount === 0"
                            class="py-2 px-5 rounded-xl font-bold text-xs text-white bg-indigo-600 hover:bg-indigo-700 disabled:opacity-40 disabled:cursor-not-allowed transition shadow-lg shadow-indigo-600/30 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                        <span>Print Selected (<span x-text="totalLabelsCount"></span> Labels)</span>
                    </button>
                </div>
            </div>

            <!-- Variants Table (with Real-time Visibility Binding) -->
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-800 text-slate-400 uppercase text-[10px] tracking-wider font-bold bg-slate-50/50 dark:bg-slate-850">
                            <th class="py-3 px-3 w-10 text-center">
                                <input type="checkbox" :checked="allSelected" @change="toggleSelectAll()"
                                       class="rounded text-indigo-600 focus:ring-indigo-500">
                            </th>
                            <th class="py-3 px-3">Product Name & Category</th>
                            <th class="py-3 px-3">SKU</th>
                            <th class="py-3 px-3">Variant (Color / Size)</th>
                            <th class="py-3 px-3">Barcode (Code 128)</th>
                            <th class="py-3 px-3 text-right">Selling MRP</th>
                            <th class="py-3 px-3 text-center">In-Stock</th>
                            <th class="py-3 px-3 w-24 text-center">Print Qty</th>
                            <th class="py-3 px-3 text-right">Quick Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800 font-medium">
                        @forelse($variants as $variant)
                            @php
                                $storeBal = $variant->stockBalances->firstWhere('store_id', $selectedStore?->id);
                                $storeStock = $storeBal ? (int) $storeBal->quantity_on_hand : 0;
                                $totalStock = (int) $variant->stockBalances->sum('quantity_on_hand');
                            @endphp
                            <tr x-show="isRowVisible({{ $variant->id }})" class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition">
                                <td class="py-3 px-3 text-center">
                                    <input type="checkbox" name="variant_ids[]" value="{{ $variant->id }}"
                                           :checked="isSelected({{ $variant->id }})"
                                           @change="toggleVariant({{ $variant->id }})"
                                           class="rounded text-indigo-600 focus:ring-indigo-500">
                                </td>
                                <td class="py-3 px-3 font-bold text-slate-900 dark:text-white">
                                    <a href="{{ route('products.show', $variant->product) }}" target="_blank" class="hover:underline">
                                        {{ $variant->product->name }}
                                    </a>
                                    <div class="text-[11px] font-normal text-slate-400">
                                        {{ $variant->product->category?->name ?? 'Uncategorized' }}
                                        @if($variant->product->subcategory)
                                            › {{ $variant->product->subcategory->name }}
                                        @endif
                                    </div>
                                </td>
                                <td class="py-3 px-3 font-mono font-semibold text-slate-600 dark:text-slate-400">
                                    {{ $variant->product->sku }}
                                </td>
                                <td class="py-3 px-3">
                                    <span class="px-2 py-0.5 rounded-md bg-slate-100 dark:bg-slate-800 font-semibold">{{ $variant->color }}</span>
                                    <span class="px-2 py-0.5 rounded-md bg-slate-100 dark:bg-slate-800 font-bold text-indigo-600">{{ $variant->size }}</span>
                                </td>
                                <td class="py-3 px-3 font-mono font-bold text-slate-900 dark:text-white tracking-wider">
                                    {{ $variant->barcode }}
                                </td>
                                <td class="py-3 px-3 text-right font-bold text-indigo-600 dark:text-indigo-400">
                                    @bdt($variant->effective_selling_price)
                                </td>
                                <td class="py-3 px-3 text-center">
                                    @if($storeStock > 0)
                                        <span class="px-2.5 py-0.5 rounded-full text-[11px] font-extrabold bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">
                                            {{ $storeStock }} pcs
                                        </span>
                                    @else
                                        <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400">
                                            0 pcs
                                        </span>
                                    @endif
                                    @if($totalStock > $storeStock)
                                        <div class="text-[9px] text-slate-400 mt-0.5" title="Total in all stores">
                                            Total: {{ $totalStock }}
                                        </div>
                                    @endif
                                </td>
                                <td class="py-3 px-3 text-center">
                                    <input type="number" name="quantities[{{ $variant->id }}]" min="1" max="200"
                                           :value="getQty({{ $variant->id }})"
                                           @input="setQty({{ $variant->id }}, $event.target.value)"
                                           class="w-16 text-center text-xs rounded-lg border-slate-300 dark:border-slate-700 py-1 font-semibold focus:ring-1 focus:ring-indigo-500">
                                </td>
                                <td class="py-3 px-3 text-right">
                                    <!-- Single Variant Quick Print Button -->
                                    <button type="button" @click="printSingleVariant({{ $variant->id }}, getQty({{ $variant->id }}))"
                                            class="inline-flex items-center gap-1 py-1 px-3 rounded-lg bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 hover:bg-indigo-600 hover:text-white font-bold text-xs transition border border-indigo-200/50 dark:border-indigo-800/50"
                                            title="Print only this variant's barcode labels">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                        <span>Print</span>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="py-8 text-center text-slate-400">
                                    No product variants found matching the selected filters.
                                </td>
                            </tr>
                        @endforelse

                        <!-- Real-time "No Matches" message if live search filters out all rows -->
                        <tr x-show="visibleVariantList.length === 0 && variantList.length > 0">
                            <td colspan="9" class="py-8 text-center text-slate-400">
                                <div>No visible product variants match "<strong class="text-slate-700 dark:text-slate-200" x-text="liveSearch"></strong>" on this page.</div>
                                <div class="mt-2">
                                    <button type="button" @click="submitFilterForm()" 
                                            class="py-1.5 px-3 rounded-lg bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400 font-bold text-xs hover:bg-indigo-100 transition">
                                        Press Enter or Click Here to search entire database →
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="p-4 border-t border-slate-100 dark:border-slate-800">
                {{ $variants->links() }}
            </div>
        </form>
    </div>

</div>

@push('scripts')
<script>
function barcodeWorkstation(config) {
    return {
        scannedBarcode: '',
        scannedResult: null,
        lookupError: '',
        cameraActive: false,
        scannerObj: null,
        quickScanPrintQty: 1,

        selectedStoreId: config.selectedStoreId || '',
        liveSearch: config.initialSearch || '',
        labelWidth: 50,
        labelHeight: 30,

        // Variants data
        variantList: config.variants || [],
        selectedMap: {},
        qtyMap: {},

        init() {
            // Default select all visible variants and set their initial quantities
            this.variantList.forEach(v => {
                this.selectedMap[v.id] = true;
                this.qtyMap[v.id] = v.qty || 1;
            });

            // Register hardware keyboard scanner listener
            if (window.initHardwareScanner) {
                window.initHardwareScanner((barcode) => {
                    this.scannedBarcode = barcode;
                    this.performLookup(barcode);
                });
            }

            // Restore search box cursor focus if loaded with query
            if (this.liveSearch && this.$refs.searchInput) {
                this.$nextTick(() => {
                    this.$refs.searchInput.focus();
                    const len = this.$refs.searchInput.value.length;
                    this.$refs.searchInput.setSelectionRange(len, len);
                });
            }
        },

        // Real-time instant row search filtering (0ms latency, pure client-side without page reload)
        isRowVisible(vId) {
            if (!this.liveSearch || !this.liveSearch.trim()) return true;
            const term = this.liveSearch.toLowerCase().trim();
            const item = this.variantList.find(v => v.id === vId);
            return item && item.meta ? item.meta.includes(term) : true;
        },

        get visibleVariantList() {
            if (!this.liveSearch || !this.liveSearch.trim()) return this.variantList;
            const term = this.liveSearch.toLowerCase().trim();
            return this.variantList.filter(v => v.meta && v.meta.includes(term));
        },

        clearLiveSearch() {
            this.liveSearch = '';
            this.$nextTick(() => {
                if (this.$refs.searchInput) {
                    this.$refs.searchInput.focus();
                }
            });
            if ('{{ request('search') }}') {
                this.submitFilterForm();
            }
        },

        submitFilterForm() {
            if (this.$refs.filterForm) {
                this.$refs.filterForm.submit();
            }
        },

        onStoreChange(storeId) {
            this.selectedStoreId = storeId;
            // Submit filter form so stock values recalculate for chosen store
            this.submitFilterForm();
        },

        get selectedCount() {
            return this.visibleVariantList.filter(v => this.selectedMap[v.id]).length;
        },

        get allSelected() {
            const list = this.visibleVariantList;
            if (list.length === 0) return false;
            return list.every(v => this.selectedMap[v.id]);
        },

        get totalLabelsCount() {
            let total = 0;
            this.visibleVariantList.forEach(v => {
                if (this.selectedMap[v.id]) {
                    total += parseInt(this.qtyMap[v.id] || 1, 10);
                }
            });
            return total;
        },

        isSelected(id) {
            return !!this.selectedMap[id];
        },

        toggleVariant(id) {
            this.selectedMap[id] = !this.selectedMap[id];
        },

        getQty(id) {
            return this.qtyMap[id] !== undefined ? this.qtyMap[id] : 1;
        },

        setQty(id, val) {
            const num = Math.max(1, parseInt(val, 10) || 1);
            this.qtyMap[id] = num;
        },

        toggleSelectAll() {
            const newState = !this.allSelected;
            this.visibleVariantList.forEach(v => {
                this.selectedMap[v.id] = newState;
            });
        },

        selectInStockOnly() {
            this.visibleVariantList.forEach(v => {
                this.selectedMap[v.id] = (v.stock > 0);
            });
        },

        fillQuantitiesFromStock() {
            this.visibleVariantList.forEach(v => {
                const stock = parseInt(v.stock, 10) || 0;
                this.qtyMap[v.id] = stock > 0 ? stock : 1;
                // If in stock, automatically select it
                if (stock > 0) {
                    this.selectedMap[v.id] = true;
                }
            });
        },

        setAllQuantities(qty) {
            this.visibleVariantList.forEach(v => {
                this.qtyMap[v.id] = Math.max(1, qty);
            });
        },

        performLookup(barcode) {
            const code = barcode ? barcode.trim() : '';
            if (!code) return;

            this.lookupError = '';
            fetch('{{ route('barcodes.scan-lookup') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ 
                    barcode: code,
                    store_id: this.selectedStoreId 
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    this.scannedResult = data.variant;
                    this.quickScanPrintQty = data.variant.stock_on_hand > 0 ? data.variant.stock_on_hand : 1;
                    this.lookupError = '';
                } else {
                    this.scannedResult = null;
                    this.lookupError = data.message || 'Product variant not found.';
                }
            })
            .catch(() => {
                this.lookupError = 'Network or server error during barcode lookup.';
            });
        },

        printSingleVariant(variantId, qty = 1) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ route('barcodes.print') }}';
            form.target = '_blank';

            const csrf = document.createElement('input');
            csrf.type = 'hidden';
            csrf.name = '_token';
            csrf.value = '{{ csrf_token() }}';
            form.appendChild(csrf);

            const vInput = document.createElement('input');
            vInput.type = 'hidden';
            vInput.name = 'single_variant_id';
            vInput.value = variantId;
            form.appendChild(vInput);

            const qInput = document.createElement('input');
            qInput.type = 'hidden';
            qInput.name = 'single_quantity';
            qInput.value = qty || 1;
            form.appendChild(qInput);

            const sInput = document.createElement('input');
            sInput.type = 'hidden';
            sInput.name = 'store_id';
            sInput.value = this.selectedStoreId;
            form.appendChild(sInput);

            const widthInput = document.createElement('input');
            widthInput.type = 'hidden';
            widthInput.name = 'label_width';
            widthInput.value = this.labelWidth;
            form.appendChild(widthInput);

            const heightInput = document.createElement('input');
            heightInput.type = 'hidden';
            heightInput.name = 'label_height';
            heightInput.value = this.labelHeight;
            form.appendChild(heightInput);

            document.body.appendChild(form);
            form.submit();
            document.body.removeChild(form);
        },

        onBatchSubmit(event) {
            if (this.selectedCount === 0) {
                event.preventDefault();
                alert('Please select at least one product variant to print.');
            }
        },

        toggleCamera() {
            if (this.cameraActive) {
                if (this.scannerObj) {
                    this.scannerObj.clear();
                }
                this.cameraActive = false;
            } else {
                this.cameraActive = true;
                this.$nextTick(() => {
                    if (window.Html5QrcodeScanner) {
                        this.scannerObj = new Html5QrcodeScanner("reader", { fps: 10, qrbox: { width: 250, height: 150 } });
                        this.scannerObj.render((decodedText) => {
                            this.scannedBarcode = decodedText;
                            this.performLookup(decodedText);
                        });
                    }
                });
            }
        }
    };
}
</script>
@endpush
@endsection
