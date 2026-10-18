@extends('layouts.app')

@section('title', 'Point of Sale (POS) - Retail Checkout')
@section('header', 'Retail POS Checkout')

@section('content')
<div class="h-[calc(100vh-140px)] flex flex-col space-y-3" x-data="posApp()">

    <!-- TOP STATUS BAR & REGISTER AUDIT -->
    <div class="bg-white dark:bg-slate-900 rounded-xl px-4 py-2.5 border border-slate-200 dark:border-slate-800 shadow-sm flex flex-wrap items-center justify-between gap-3 text-xs">
        <div class="flex items-center gap-3">
            <div class="flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                <span class="font-bold text-slate-900 dark:text-white">{{ $store->name }}</span>
                <span class="text-slate-400 font-mono text-[11px]">({{ $store->code }})</span>
            </div>
            <span class="text-slate-300 dark:text-slate-700">|</span>
            <div class="text-slate-500">
                Cashier: <strong class="text-slate-800 dark:text-slate-200">{{ auth()->user()->name }}</strong>
            </div>
            <span class="text-slate-300 dark:text-slate-700">|</span>
            <div class="text-slate-500">
                Business Day: <strong class="text-indigo-600 dark:text-indigo-400 font-mono">{{ now()->format('d M Y') }}</strong>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <!-- Sales History Quick Link -->
            @if(auth()->user()->canAccessModule('sales_history'))
                <a href="{{ route('pos.sales.history') }}" 
                   class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-950/60 dark:hover:bg-indigo-900/60 border border-indigo-200 dark:border-indigo-800 text-indigo-700 dark:text-indigo-300 text-[11px] font-bold transition shadow-2xs">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                    <span>Sales History</span>
                </a>
            @endif

            @if($register && $register->isOpen())
                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    <span>Register Active (Open Cash: ৳{{ number_format($register->opening_cash, 2) }})</span>
                </span>
                <a href="{{ route('daily-registers.index') }}" class="text-[11px] text-slate-500 hover:text-slate-800 dark:hover:text-white underline">
                    Register Control
                </a>
            @else
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-lg bg-amber-50 dark:bg-amber-950/60 border border-amber-200 dark:border-amber-800 text-amber-800 dark:text-amber-300 text-[11px] font-bold">
                    <span>⚠️ Daily Cash Register Not Opened Yet!</span>
                    <a href="{{ route('daily-registers.index') }}" class="px-2 py-0.5 rounded bg-amber-600 hover:bg-amber-700 text-white font-bold text-[10px] transition">
                        Open Day Register
                    </a>
                </div>
            @endif
        </div>
    </div>

    <!-- MAIN TWO-PANEL WORKSTATION -->
    <div class="flex-1 grid grid-cols-1 lg:grid-cols-12 gap-3 min-h-0">

        <!-- LEFT PANEL: CART & SCANNING (8 Cols) -->
        <div class="lg:col-span-8 flex flex-col bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
            
            <!-- REAL-TIME STOCK WARNING BANNER -->
            <div x-show="stockAlertMessage" x-cloak
                 class="px-4 py-2.5 bg-rose-500 text-white font-bold text-xs flex items-center justify-between shadow-sm animate-pulse">
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-white shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                    <span x-text="stockAlertMessage"></span>
                </div>
                <button type="button" @click="stockAlertMessage = ''" class="text-white hover:text-slate-200 text-sm font-black ml-4">✕</button>
            </div>

            <!-- FAST SCANNER INPUT HUD -->
            <div class="p-3 bg-slate-50 dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 flex items-center gap-3">
                <div class="relative flex-1">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                    </div>
                    <input type="text" x-ref="barcodeInput" x-model="searchQuery"
                           autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false"
                           @keydown.enter.prevent="handleSearchOrScan()"
                           @input="onSearchInput()"
                           placeholder="Scan Code 128 / EAN Barcode or type garment name/SKU..."
                           class="w-full text-xs rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 pl-9 pr-4 py-2.5 font-mono shadow-2xs focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 focus:outline-none">
                </div>

                <button type="button" @click="handleSearchOrScan()" class="px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-2xs transition flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    <span>Scan / Add</span>
                </button>
            </div>

            <!-- SEARCH AUTOCOMPLETE RESULTS (IF VISIBLE) -->
            <div x-show="searchResults.length > 0" class="max-h-48 overflow-y-auto bg-slate-50 dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700 p-2 space-y-1">
                <template x-for="item in searchResults" :key="item.id">
                    <div @click="addToCart(item)" class="flex items-center justify-between p-2 rounded-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 hover:border-indigo-500 cursor-pointer text-xs">
                        <div>
                            <span class="font-bold text-slate-900 dark:text-white" x-text="item.name"></span>
                            <span class="text-slate-400 text-[11px]" x-text="' (' + (item.color || 'Standard') + ' / ' + (item.size || 'Free') + ')'"></span>
                            <span class="text-indigo-600 dark:text-indigo-400 font-mono text-[10px] ml-1" x-text="'[' + item.barcode + ']'"></span>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="font-mono text-slate-500 text-[11px]" x-text="'Stock: ' + item.available_stock + ' pcs'"></span>
                            <span class="font-mono font-bold text-emerald-600" x-text="'৳' + item.price.toFixed(2)"></span>
                        </div>
                    </div>
                </template>
            </div>

            <!-- CART ITEMS TABLE (FLEX-1 SCROLLABLE) -->
            <div class="flex-1 overflow-y-auto">
                <table class="w-full text-left text-xs">
                    <thead class="sticky top-0 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-bold border-b border-slate-200 dark:border-slate-700">
                        <tr>
                            <th class="p-3 w-8 text-center">#</th>
                            <th class="p-3">Garment Item & Variant</th>
                            <th class="p-3 text-right w-24">Price (৳)</th>
                            <th class="p-3 text-center w-24">Stock Avail</th>
                            <th class="p-3 text-center w-32">Scan Qty</th>
                            <th class="p-3 text-right w-28">Subtotal (৳)</th>
                            <th class="p-3 w-10 text-center"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        <template x-for="(line, index) in cart" :key="line.variant_id">
                            <tr :class="line.quantity > line.available_stock ? 'bg-rose-50/60 dark:bg-rose-950/20' : 'hover:bg-slate-50/50 dark:hover:bg-slate-800/40'" class="transition">
                                <td class="p-3 text-center text-slate-400 font-mono" x-text="index + 1"></td>
                                <td class="p-3">
                                    <div class="font-bold text-slate-900 dark:text-white" x-text="line.name"></div>
                                    <div class="text-[11px] text-slate-400" x-text="(line.color || 'Standard') + ' / ' + (line.size || 'Free') + ' • ' + line.barcode"></div>
                                    <div x-show="line.quantity > line.available_stock" class="text-[10px] text-rose-600 dark:text-rose-400 font-bold flex items-center gap-1 mt-0.5">
                                        <svg class="w-3 h-3 text-rose-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                                        <span>Stock shortage! Only <span x-text="line.available_stock"></span> pcs available</span>
                                    </div>
                                </td>
                                <td class="p-3 text-right font-mono text-slate-700 dark:text-slate-300" x-text="line.price.toFixed(2)"></td>
                                <td class="p-3 text-center">
                                    <span class="px-2 py-0.5 rounded-full text-[11px] font-mono font-bold"
                                          :class="line.available_stock > 0 ? (line.quantity > line.available_stock ? 'bg-rose-100 text-rose-700 dark:bg-rose-950/70 dark:text-rose-400 font-black' : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300') : 'bg-rose-100 text-rose-700 dark:bg-rose-950/70 dark:text-rose-400 font-black'"
                                          x-text="line.available_stock + ' pcs'">
                                    </span>
                                </td>
                                <td class="p-3">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <button type="button" @click="decrementQty(index)" class="w-6 h-6 rounded bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 text-slate-700 dark:text-slate-300 font-bold flex items-center justify-center">
                                            -
                                        </button>
                                        <span class="w-8 text-center font-mono font-bold"
                                              :class="line.quantity > line.available_stock ? 'text-rose-600 font-black' : 'text-slate-900 dark:text-white'"
                                              x-text="line.quantity"></span>
                                        <button type="button" @click="incrementQty(index)" class="w-6 h-6 rounded bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 text-slate-700 dark:text-slate-300 font-bold flex items-center justify-center">
                                            +
                                        </button>
                                    </div>
                                </td>
                                <td class="p-3 text-right font-mono font-bold text-slate-900 dark:text-white" x-text="(line.price * line.quantity).toFixed(2)"></td>
                                <td class="p-3 text-center">
                                    <button type="button" @click="removeLine(index)" class="text-rose-500 hover:text-rose-700 p-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </td>
                            </tr>
                        </template>

                        <tr x-show="cart.length === 0">
                            <td colspan="7" class="p-12 text-center text-slate-400 dark:text-slate-500">
                                <svg class="w-12 h-12 mx-auto text-slate-300 dark:text-slate-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                                <div class="font-bold">Cart is Empty</div>
                                <div class="text-[11px] mt-0.5">Scan product barcode tag with scanner or enter SKU above.</div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- CART FOOTER QUICK BAR -->
            <div class="p-3 bg-slate-50 dark:bg-slate-800/60 border-t border-slate-200 dark:border-slate-800 flex items-center justify-between text-xs">
                <button type="button" @click="clearCart()" x-show="cart.length > 0" class="text-rose-600 hover:underline font-semibold">
                    Clear Entire Cart
                </button>
                <div class="text-slate-500 font-mono">
                    Total Items: <strong class="text-slate-900 dark:text-white" x-text="totalItemCount()"></strong> pcs
                </div>
            </div>
        </div>

        <!-- RIGHT PANEL: CUSTOMER, DISCOUNTS, TOTALS & PAY (4 Cols) -->
        <div class="lg:col-span-4 flex flex-col bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm p-4 space-y-4">
            
            <!-- CUSTOMER & LOYALTY LOOKUP (AUTOSEARCH & DROPDOWN) -->
            <div class="bg-slate-50 dark:bg-slate-800/50 p-3 rounded-xl border border-slate-100 dark:border-slate-800 space-y-2 relative"
                 @click.outside="showCustomerDropdown = false">
                <div class="flex items-center justify-between">
                    <div class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        <span>Customer & Loyalty (Optional)</span>
                    </div>
                    <!-- Clear customer button -->
                    <button type="button" 
                            x-show="customerPhone || customerName" 
                            @click="clearCustomer()" 
                            class="text-[10px] text-slate-400 hover:text-rose-600 dark:hover:text-rose-400 font-semibold flex items-center gap-0.5 transition cursor-pointer">
                        <span>Clear</span>
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="grid grid-cols-2 gap-2 relative">
                    <!-- Mobile Number Input with Auto-search -->
                    <div class="relative">
                        <input type="text" 
                               x-model="customerPhone" 
                               @input="searchCustomerLookup($event.target.value)"
                               @focus="if(customerSuggestions.length > 0) showCustomerDropdown = true"
                               @keydown.escape="showCustomerDropdown = false"
                               placeholder="Mobile Number..."
                               autocomplete="off"
                               class="w-full text-xs rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white px-2.5 py-1.5 font-mono focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500">
                        <!-- Loading spinner -->
                        <div x-show="isSearchingCustomer" x-cloak class="absolute right-2 top-2 text-indigo-500 animate-spin pointer-events-none">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        </div>
                    </div>

                    <!-- Customer Name Input -->
                    <div class="relative">
                        <input type="text" 
                               x-model="customerName" 
                               @input="searchCustomerLookup($event.target.value)"
                               @focus="if(customerSuggestions.length > 0) showCustomerDropdown = true"
                               @keydown.escape="showCustomerDropdown = false"
                               placeholder="Customer Name..."
                               autocomplete="off"
                               class="w-full text-xs rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white px-2.5 py-1.5 focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                </div>

                <!-- Autocomplete Dropdown Menu for Matching Customers -->
                <div x-show="showCustomerDropdown && customerSuggestions.length > 0"
                     x-cloak
                     x-transition
                     class="absolute left-0 right-0 top-full mt-1 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl shadow-2xl z-40 max-h-56 overflow-y-auto divide-y divide-slate-100 dark:divide-slate-800">
                    <div class="px-3 py-1.5 bg-slate-50 dark:bg-slate-800/80 text-[10px] font-bold text-slate-500 dark:text-slate-400 flex items-center justify-between">
                        <span>Matching Customers (Click to select)</span>
                        <span x-text="customerSuggestions.length + ' found'"></span>
                    </div>
                    <template x-for="cust in customerSuggestions" :key="cust.id">
                        <button type="button" 
                                @click="selectCustomer(cust)"
                                class="w-full text-left px-3 py-2 hover:bg-indigo-50/80 dark:hover:bg-indigo-950/50 transition flex items-center justify-between gap-2 cursor-pointer group">
                            <div class="min-w-0">
                                <div class="flex items-center gap-1.5">
                                    <span class="font-bold text-xs text-slate-900 dark:text-white group-hover:text-indigo-600 dark:group-hover:text-indigo-400 truncate" x-text="cust.name"></span>
                                    <span class="text-[10px] font-semibold text-emerald-600 dark:text-emerald-400 px-1 rounded bg-emerald-50 dark:bg-emerald-950/50 border border-emerald-200 dark:border-emerald-800/60" x-show="cust.loyalty_points > 0" x-text="cust.loyalty_points + ' pts'"></span>
                                </div>
                                <div class="text-[11px] font-mono text-slate-500 dark:text-slate-400" x-text="cust.phone"></div>
                            </div>
                            <div class="text-right flex-shrink-0 text-[10px] text-slate-400">
                                <span class="block font-medium text-slate-600 dark:text-slate-300" x-text="'৳' + Number(cust.total_spend || 0).toLocaleString()"></span>
                                <span>Total spent</span>
                            </div>
                        </button>
                    </template>
                </div>

                <!-- Selected / Matched Registered Customer Loyalty Badge -->
                <div x-show="selectedCustomerDetails" x-cloak class="flex items-center justify-between p-2 rounded-lg bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/60 text-[11px] text-emerald-900 dark:text-emerald-200">
                    <div class="flex items-center gap-1.5 truncate">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 flex-shrink-0"></span>
                        <span class="font-bold truncate" x-text="selectedCustomerDetails?.name"></span>
                        <span class="font-mono text-[10px] text-emerald-700 dark:text-emerald-300" x-text="'(' + selectedCustomerDetails?.phone + ')'"></span>
                    </div>
                    <div class="flex items-center gap-2 flex-shrink-0">
                        <span class="px-2 py-0.5 rounded-full bg-emerald-100 dark:bg-emerald-900/60 font-bold text-[10px] text-emerald-800 dark:text-emerald-300" 
                              x-text="(selectedCustomerDetails?.loyalty_points || 0) + ' Loyalty Pts'"></span>
                    </div>
                </div>
            </div>

            <!-- PROMOTION & CAMPAIGN RULE (SUPER ADMIN DEFINED - PRODUCT / CATEGORY AWARE) -->
            <div class="space-y-1.5">
                <div class="flex items-center justify-between">
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                        <span>Product / Order Discounts</span>
                    </label>
                    <span x-show="availablePromotions().length > 0" class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-400 border border-emerald-200/60 dark:border-emerald-800/60" x-text="availablePromotions().length + ' coupon(s) available'"></span>
                </div>

                <!-- Empty Cart State -->
                <div x-show="cart.length === 0" class="text-[11px] text-slate-400 p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800/40 border border-slate-200/60 dark:border-slate-800 flex items-center gap-2">
                    <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span>Add products to cart to reveal matching discount coupons.</span>
                </div>

                <!-- Cart Has Items, but No Eligible Promotions -->
                <div x-show="cart.length > 0 && availablePromotions().length === 0" class="text-[11px] text-slate-400 p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800/40 border border-slate-200/60 dark:border-slate-800 flex items-center gap-2">
                    <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    <span>No active discount coupon applies to the items in cart.</span>
                </div>

                <!-- Eligible Promotions Available for Current Cart -->
                <div x-show="availablePromotions().length > 0" class="space-y-2">
                    <select x-model="selectedPromotion" class="w-full text-xs rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white px-2.5 py-2 font-medium focus:ring-1 focus:ring-indigo-500">
                        <option value="">No Promotion Applied</option>
                        <template x-for="promo in availablePromotions()" :key="promo.code">
                            <option :value="promo.code" x-text="promo.label"></option>
                        </template>
                    </select>

                    <!-- Quick Clickable Coupon Cards / Pills -->
                    <div class="flex flex-wrap gap-1.5 pt-0.5">
                        <template x-for="promo in availablePromotions()" :key="promo.code">
                            <button type="button" 
                                    @click="selectedPromotion = (selectedPromotion === promo.code ? '' : promo.code)"
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-[11px] font-bold transition border cursor-pointer"
                                    :class="selectedPromotion === promo.code 
                                        ? 'bg-emerald-600 text-white border-emerald-600 shadow-sm' 
                                        : 'bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 border-slate-200 dark:border-slate-700 hover:border-indigo-400 dark:hover:border-indigo-600'">
                                <svg x-show="selectedPromotion === promo.code" class="w-3.5 h-3.5 text-white shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                <svg x-show="selectedPromotion !== promo.code" class="w-3.5 h-3.5 text-indigo-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                                <span class="font-mono" x-text="promo.code"></span>
                                <span class="font-normal text-[10px]" x-text="'(' + promo.discount_percentage + '% OFF)'"></span>
                            </button>
                        </template>
                    </div>
                </div>
            </div>

            <!-- FINANCIAL TOTALS BREAKDOWN -->
            <div class="flex-1 bg-slate-50 dark:bg-slate-800/40 p-4 rounded-xl border border-slate-200/80 dark:border-slate-800 flex flex-col justify-between space-y-3 font-mono text-xs">
                <div class="space-y-2">
                    <div class="flex items-center justify-between text-slate-500">
                        <span>Cart Subtotal</span>
                        <span class="font-bold text-slate-800 dark:text-slate-200">৳<span x-text="subtotal().toFixed(2)"></span></span>
                    </div>

                    <div class="flex items-center justify-between text-emerald-600 dark:text-emerald-400" x-show="promoDiscount() > 0">
                        <span>Promotion Discount</span>
                        <span class="font-bold">-৳<span x-text="promoDiscount().toFixed(2)"></span></span>
                    </div>

                    <div class="flex items-center justify-between text-slate-500">
                        <div class="flex items-center gap-1.5">
                            <span x-text="taxRatePercent > 0 ? (vatLabel + ' (' + taxRatePercent + '%)') : (vatLabel + ' (OFF - 0%)')"></span>
                            @if(auth()->user()?->isSuperAdmin())
                                <button type="button" @click="showTaxModal = true" class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-bold bg-indigo-50 hover:bg-indigo-100 text-indigo-600 dark:bg-indigo-900/40 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800 transition" title="Tax / VAT Configure or Turn Off">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    <span>⚙️ Config</span>
                                </button>
                            @endif
                        </div>
                        <span class="font-bold text-slate-800 dark:text-slate-200">৳<span x-text="vatAmount().toFixed(2)"></span></span>
                    </div>

                    <div class="flex items-center justify-between text-slate-400 text-[11px]" x-show="roundingAdjustment() !== 0">
                        <span>BDT Rounding</span>
                        <span x-text="(roundingAdjustment() > 0 ? '+৳' : '-৳') + Math.abs(roundingAdjustment()).toFixed(2)"></span>
                    </div>
                </div>

                <div class="pt-3 border-t-2 border-slate-200 dark:border-slate-700 flex items-baseline justify-between">
                    <div>
                        <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Net Payable Total</div>
                        <div class="text-xs text-slate-400">Rounded to Nearest BDT ৳</div>
                    </div>
                    <div class="text-2xl font-black text-indigo-600 dark:text-indigo-400">
                        ৳<span x-text="roundedTotal()"></span>
                    </div>
                </div>
            </div>

            <!-- TENDER / PAY BUTTON -->
            <button type="button" @click="openPaymentModal()" :disabled="cart.length === 0"
                    class="w-full py-3.5 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 disabled:opacity-40 disabled:cursor-not-allowed text-white font-black text-sm shadow-lg shadow-emerald-600/30 transition flex items-center justify-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                <span>Settle & Checkout [F9]</span>
            </button>
        </div>
    </div>

    @if(auth()->user()?->isSuperAdmin())
    <!-- QUICK TAX / VAT CONFIGURATION MODAL (SUPER ADMIN ONLY) -->
    <div x-show="showTaxModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/70 backdrop-blur-sm flex items-center justify-center p-4">
        <div @click.away="showTaxModal = false" class="bg-white dark:bg-slate-900 rounded-2xl max-w-md w-full p-6 border border-slate-200 dark:border-slate-800 shadow-2xl space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                <div class="flex items-center gap-2">
                    <span class="p-1.5 rounded-lg bg-indigo-50 text-indigo-600 dark:bg-indigo-950/60 dark:text-indigo-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/></svg>
                    </span>
                    <div>
                        <h3 class="text-sm font-extrabold text-slate-900 dark:text-white">Tax / VAT Quick Settings</h3>
                        <p class="text-[11px] text-slate-500">Shop Owner Authority Control</p>
                    </div>
                </div>
                <button type="button" @click="showTaxModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1 text-lg font-bold">&times;</button>
            </div>

            <!-- SUCCESS / ERROR ALERT -->
            <div x-show="taxModalSuccess" class="p-3 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-bold" x-text="taxModalSuccess"></div>
            <div x-show="taxModalError" class="p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-bold" x-text="taxModalError"></div>

            <!-- TOGGLE TAX ON/OFF -->
            <div class="space-y-2">
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">Tax / VAT Status</label>
                <div class="grid grid-cols-2 gap-2">
                    <button type="button" @click="modalVatEnabled = true; if(parseFloat(modalVatRate) === 0) modalVatRate = '5.00'"
                            class="p-2.5 rounded-xl border text-xs font-bold transition flex items-center justify-center gap-2"
                            :class="modalVatEnabled ? 'bg-emerald-50 border-emerald-500 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300' : 'bg-slate-50 border-slate-200 text-slate-600 dark:bg-slate-800 dark:border-slate-700'">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        <span>Enabled (ON)</span>
                    </button>
                    <button type="button" @click="modalVatEnabled = false"
                            class="p-2.5 rounded-xl border text-xs font-bold transition flex items-center justify-center gap-2"
                            :class="!modalVatEnabled ? 'bg-rose-50 border-rose-500 text-rose-700 dark:bg-rose-950/40 dark:text-rose-300' : 'bg-slate-50 border-slate-200 text-slate-600 dark:bg-slate-800 dark:border-slate-700'">
                        <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                        <span>Turn OFF (0%)</span>
                    </button>
                </div>
            </div>

            <!-- RATE INPUT & PRESETS -->
            <div class="space-y-2" x-show="modalVatEnabled">
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">VAT Percentage (%)</label>
                <div class="flex items-center gap-2">
                    <input type="number" step="0.01" min="0" max="100" x-model="modalVatRate"
                           class="w-32 text-sm font-bold text-slate-900 dark:text-white dark:bg-slate-800 rounded-xl border-slate-300 dark:border-slate-700 px-3 py-2">
                    <span class="text-xs font-bold text-slate-400">%</span>

                    <div class="flex items-center gap-1.5 ml-auto">
                        <button type="button" @click="modalVatRate = '5.00'" class="px-2 py-1 text-[11px] font-bold rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700">5%</button>
                        <button type="button" @click="modalVatRate = '7.50'" class="px-2 py-1 text-[11px] font-bold rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700">7.5%</button>
                        <button type="button" @click="modalVatRate = '15.00'" class="px-2 py-1 text-[11px] font-bold rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700">15%</button>
                    </div>
                </div>
            </div>

            <!-- LABEL INPUT -->
            <div class="space-y-1">
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">Display Label</label>
                <input type="text" x-model="modalVatLabel" maxlength="50"
                       class="w-full text-xs font-semibold text-slate-900 dark:text-white dark:bg-slate-800 rounded-xl border-slate-300 dark:border-slate-700 px-3 py-2">
            </div>

            <p class="text-[11px] text-slate-400 leading-relaxed">
                Turn OFF করলে কার্ট এবং প্রিন্ট করা ক্যাশ মেমোতে কোনো ভ্যাট ধরা হবে না।
            </p>

            <div class="flex items-center justify-between pt-2 border-t border-slate-100 dark:border-slate-800">
                <a href="{{ route('admin.tax.index') }}" class="text-[11px] font-bold text-indigo-600 dark:text-indigo-400 hover:underline">
                    Full Settings Page &rarr;
                </a>
                <div class="flex items-center gap-2">
                    <button type="button" @click="showTaxModal = false" class="px-3 py-1.5 text-xs font-bold text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg">
                        Cancel
                    </button>
                    <button type="button" @click="saveTaxSettings()" :disabled="taxModalSubmitting"
                            class="px-4 py-1.5 text-xs font-extrabold text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl shadow transition disabled:opacity-50">
                        <span x-show="!taxModalSubmitting">Apply & Save</span>
                        <span x-show="taxModalSubmitting">Saving...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- SPLIT / MULTI-RAIL PAYMENT CHECKOUT MODAL -->
    <div x-show="showPayModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm" x-cloak>
        <div class="w-full max-w-xl bg-white dark:bg-slate-900 rounded-2xl shadow-xl border border-slate-200 dark:border-slate-800 p-6 space-y-4" @click.away="showPayModal = false">
            
            <!-- HEADER -->
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                <div>
                    <h3 class="text-base font-bold text-slate-800 dark:text-white">Multi-Rail Split Payment Checkout</h3>
                    <div class="text-xs text-slate-500 mt-0.5">
                        Invoice Total: <strong class="text-indigo-600 dark:text-indigo-400 font-mono text-sm" x-text="'৳' + roundedTotal()"></strong>
                    </div>
                </div>
                <button type="button" @click="showPayModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-white text-lg font-bold p-1">✕</button>
            </div>

            <!-- PAYMENT RAILS -->
            <div class="space-y-3 max-h-[50vh] overflow-y-auto pr-1">
                <template x-for="(pay, idx) in paymentSplits" :key="idx">
                    <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-slate-700 dark:text-slate-300" x-text="'Payment Rail #' + (idx + 1)"></span>
                            <button type="button" @click="removeSplit(idx)" x-show="paymentSplits.length > 1" class="text-rose-500 text-[11px] font-semibold hover:underline">Remove</button>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
                            <!-- Method -->
                            <div>
                                <label class="block text-[10px] font-bold text-slate-400 mb-0.5">Method</label>
                                <select x-model="pay.payment_method" @change="onMethodChange(idx)" class="w-full text-xs rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white px-2.5 py-1.5 font-medium">
                                    <option value="cash">Cash Tender</option>
                                    <option value="bkash">bKash Mobile</option>
                                    <option value="nagad">Nagad Mobile</option>
                                    <option value="rocket">Rocket Mobile</option>
                                    <option value="card">Debit / Credit Card</option>
                                </select>
                            </div>

                            <!-- Payment Amount (Locked in single mode) -->
                            <div>
                                <div class="flex items-center justify-between mb-0.5">
                                    <label class="block text-[10px] font-bold text-slate-400">
                                        Payment Amount (৳)
                                    </label>
                                    <span x-show="paymentSplits.length === 1" class="text-[9px] text-slate-400 font-medium">Locked</span>
                                    <button type="button" x-show="paymentSplits.length > 1" @click="setFullDue(idx)" class="text-[9px] text-indigo-600 dark:text-indigo-400 font-semibold hover:underline">Fill Max</button>
                                </div>
                                <input type="number" step="1" min="1"
                                       :value="paymentSplits.length === 1 ? roundedTotal() : pay.amount"
                                       @input="if (paymentSplits.length > 1) pay.amount = Number($event.target.value)"
                                       :readonly="paymentSplits.length === 1"
                                       :class="paymentSplits.length === 1 ? 'bg-slate-100 dark:bg-slate-800/80 cursor-not-allowed text-slate-600 dark:text-slate-400' : 'bg-white dark:bg-slate-800 dark:text-white'"
                                       class="w-full text-xs rounded-lg border-slate-300 dark:border-slate-700 px-2.5 py-1.5 font-mono font-bold">
                            </div>

                            <!-- Customer Gave (৳) / Reference -->
                            <div x-show="pay.payment_method === 'cash'">
                                <label class="block text-[10px] font-bold text-slate-400 mb-0.5">Customer Gave (৳)</label>
                                <input type="number" step="1" min="0" x-model.number="pay.tendered_amount"
                                       :id="'splitTenderedInput_' + idx"
                                       placeholder="Cash Given"
                                       class="w-full text-xs rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white px-2.5 py-1.5 font-mono font-bold">
                            </div>

                            <div x-show="pay.payment_method !== 'cash'">
                                <label class="block text-[10px] font-bold text-slate-400 mb-0.5">Txn / Slip Ref</label>
                                <input type="text" x-model="pay.reference" placeholder="e.g. 9B8721X"
                                       class="w-full text-xs rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white px-2.5 py-1.5 font-mono">
                            </div>
                        </div>

                        <!-- Due/Shortage alert directly on the rail -->
                        <div x-show="pay.payment_method === 'cash' && Number(pay.tendered_amount || 0) < (paymentSplits.length === 1 ? roundedTotal() : Number(pay.amount || 0))" class="text-[11px] text-rose-500 font-semibold pt-0.5 flex items-center gap-1">
                            <span>⚠️ Due / Shortage: ৳<span x-text="((paymentSplits.length === 1 ? roundedTotal() : Number(pay.amount || 0)) - Number(pay.tendered_amount || 0)).toFixed(2)"></span> (Cannot checkout with due)</span>
                        </div>
                    </div>
                </template>

                <!-- ADD SPLIT BUTTON -->
                <button type="button" @click="addSplit()" class="text-xs text-indigo-600 dark:text-indigo-400 font-semibold hover:underline block pt-1">
                    + Add Another Payment Method (Split Bill)
                </button>
            </div>

            <!-- SUMMARY & STATUS -->
            <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/50 font-mono text-xs space-y-2 border border-slate-100 dark:border-slate-800">
                <div class="flex items-center justify-between text-slate-500 dark:text-slate-400">
                    <span>Net Invoice Amount:</span>
                    <strong class="font-bold text-slate-800 dark:text-slate-100" x-text="'৳' + roundedTotal()"></strong>
                </div>
                <div class="flex items-center justify-between text-slate-500 dark:text-slate-400">
                    <span>Total Paid / Allocated:</span>
                    <strong class="font-bold text-slate-800 dark:text-slate-100" x-text="'৳' + totalAllocated()"></strong>
                </div>

                <!-- DUE STATUS: RED ALERT (WHEN DUE EXISTS) -->
                <div x-show="totalDue() > 0" class="p-2.5 rounded-lg bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-900 text-rose-700 dark:text-rose-300 font-bold flex items-center justify-between">
                    <span class="flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        <span>Status: Due / Shortage (Checkout Blocked)</span>
                    </span>
                    <span class="font-mono text-sm" x-text="'৳' + totalDue().toFixed(2)"></span>
                </div>

                <!-- FULL AMOUNT PAID: GREEN ALERT -->
                <div x-show="totalDue() === 0 && cashChange() === 0" class="p-2.5 rounded-lg bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-900 text-emerald-700 dark:text-emerald-300 font-bold flex items-center justify-between">
                    <span class="flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span>Status: Full Amount Paid</span>
                    </span>
                    <span>৳0</span>
                </div>

                <!-- CHANGE RETURN: GREEN ALERT -->
                <div x-show="totalDue() === 0 && cashChange() > 0" class="p-2.5 rounded-lg bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-900 text-emerald-700 dark:text-emerald-300 font-bold flex items-center justify-between">
                    <span class="flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span>Status: Cash Change Return</span>
                    </span>
                    <span class="font-mono text-sm text-emerald-800 dark:text-emerald-200" x-text="'৳' + cashChange().toFixed(2)"></span>
                </div>
            </div>

            <!-- FOOTER ACTIONS -->
            <div class="flex items-center justify-end gap-2 pt-2">
                <button type="button" @click="showPayModal = false" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-semibold transition">
                    Back to Cart
                </button>
                <button type="button" @click="submitCheckout()" :disabled="!canCheckout()"
                        class="px-6 py-2.5 rounded-xl font-bold text-xs shadow-md transition flex items-center gap-1.5"
                        :class="canCheckout() ? 'bg-emerald-600 hover:bg-emerald-700 text-white' : 'bg-slate-300 dark:bg-slate-800 text-slate-400 dark:text-slate-500 cursor-not-allowed opacity-50 shadow-none'">
                    <span x-show="!isSubmitting">Complete Sale & Print Receipt</span>
                    <span x-show="isSubmitting">Processing Transaction...</span>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function posApp() {
    return {
        searchQuery: '',
        searchResults: [],
        cart: [],
        customerPhone: '',
        customerName: '',
        customerSuggestions: [],
        showCustomerDropdown: false,
        isSearchingCustomer: false,
        customerSearchTimer: null,
        selectedCustomerDetails: null,
        selectedPromotion: '',
        activePromotions: {!! json_encode($activePromotions->map(function($p) {
            return [
                'id' => $p->id,
                'code' => $p->code,
                'name' => $p->name,
                'discount_percentage' => (float) $p->discount_percentage,
                'product_id' => $p->product_id,
                'product_name' => $p->product?->name,
                'product_sku' => $p->product?->sku,
                'category_id' => $p->category_id,
                'category_name' => $p->category?->name,
                'min_spend' => (float) ($p->min_spend ?? 0),
            ];
        })->values()) !!},
        taxRatePercent: {{ \App\Models\AppSetting::getVatRate() }},
        vatEnabled: {{ \App\Models\AppSetting::isVatEnabled() ? 'true' : 'false' }},
        vatLabel: '{{ addslashes(\App\Models\AppSetting::getVatLabel()) }}',
        showTaxModal: false,
        taxModalSubmitting: false,
        taxModalError: '',
        taxModalSuccess: '',
        modalVatEnabled: {{ \App\Models\AppSetting::isVatEnabled() ? 'true' : 'false' }},
        modalVatRate: '{{ number_format(\App\Models\AppSetting::get('vat_rate', 5.00), 2, '.', '') }}',
        showPayModal: false,
        isSubmitting: false,
        stockAlertMessage: '',
        scanQueue: [],
        isProcessingScan: false,
        idempotencyKey: 'IDEM-' + Date.now() + '-' + Math.random().toString(36).substring(2, 8),
        paymentSplits: [
            { payment_method: 'cash', amount: 0, tendered_amount: 0, reference: '' }
        ],

        init() {
            // Restore cart and customer details from localStorage if available
            try {
                const savedCart = localStorage.getItem('pos_active_cart');
                if (savedCart) {
                    const parsed = JSON.parse(savedCart);
                    if (Array.isArray(parsed)) {
                        this.cart = parsed;
                    }
                }
                const savedCustPhone = localStorage.getItem('pos_active_customer_phone');
                if (savedCustPhone) this.customerPhone = savedCustPhone;
                const savedCustName = localStorage.getItem('pos_active_customer_name');
                if (savedCustName) this.customerName = savedCustName;
            } catch (e) {
                console.warn('Could not read cached POS cart from localStorage', e);
            }

            // Watch cart changes and automatically persist to localStorage and check coupon eligibility
            this.$watch('cart', (val) => {
                this.persistCart();
                this.checkPromotionValidity();
            });
            this.$watch('customerPhone', (val) => {
                try { localStorage.setItem('pos_active_customer_phone', val || ''); } catch (e) {}
            });
            this.$watch('customerName', (val) => {
                try { localStorage.setItem('pos_active_customer_name', val || ''); } catch (e) {}
            });

            // Auto focus the barcode input on load
            this.$nextTick(() => {
                if (this.$refs.barcodeInput) {
                    this.$refs.barcodeInput.focus();
                }
            });

            // Register global hardware barcode scanner listener
            if (window.initHardwareScanner) {
                window.initHardwareScanner((barcode) => {
                    if (barcode && barcode.trim()) {
                        this.enqueueScan(barcode.trim());
                    }
                });
            }
            // If phone was loaded from localStorage, lookup details to display badge
            if (this.customerPhone && this.customerPhone.trim().length >= 4) {
                this.searchCustomerLookup(this.customerPhone);
            }
        },

        searchCustomerLookup(query) {
            clearTimeout(this.customerSearchTimer);
            const q = (query || '').trim();
            if (q.length < 2) {
                this.customerSuggestions = [];
                this.showCustomerDropdown = false;
                this.isSearchingCustomer = false;
                return;
            }

            this.isSearchingCustomer = true;
            this.customerSearchTimer = setTimeout(() => {
                fetch(`{{ route('pos.customers.search') }}?q=${encodeURIComponent(q)}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(res => res.json())
                .then(data => {
                    this.isSearchingCustomer = false;
                    if (Array.isArray(data)) {
                        this.customerSuggestions = data;
                        this.showCustomerDropdown = data.length > 0;

                        // Auto-match if exact phone match found
                        const exactMatch = data.find(c => c.phone && c.phone.trim() === this.customerPhone.trim());
                        if (exactMatch) {
                            if (!this.customerName || this.customerName.trim() === '' || this.customerName === 'Walk-in Customer') {
                                this.customerName = exactMatch.name;
                            }
                            this.selectedCustomerDetails = exactMatch;
                        }
                    } else {
                        this.customerSuggestions = [];
                        this.showCustomerDropdown = false;
                    }
                })
                .catch(err => {
                    this.isSearchingCustomer = false;
                    console.warn('Customer lookup error:', err);
                });
            }, 180);
        },

        selectCustomer(cust) {
            if (!cust) return;
            this.customerPhone = cust.phone || '';
            this.customerName = cust.name || '';
            this.selectedCustomerDetails = cust;
            this.customerSuggestions = [];
            this.showCustomerDropdown = false;
            try {
                localStorage.setItem('pos_active_customer_phone', this.customerPhone);
                localStorage.setItem('pos_active_customer_name', this.customerName);
            } catch (e) {}
        },

        clearCustomer() {
            this.customerPhone = '';
            this.customerName = '';
            this.selectedCustomerDetails = null;
            this.customerSuggestions = [];
            this.showCustomerDropdown = false;
            try {
                localStorage.removeItem('pos_active_customer_phone');
                localStorage.removeItem('pos_active_customer_name');
            } catch (e) {}
        },

        persistCart() {
            try {
                localStorage.setItem('pos_active_cart', JSON.stringify(this.cart));
            } catch (e) {
                console.warn('Could not save cart to localStorage', e);
            }
        },

        // Triggered instantly on every keystroke, barcode scan, or barcode gun input
        onSearchInput() {
            clearTimeout(this.scanDebounceTimer);
            let q = (this.searchQuery || '').trim();
            if (!q) {
                this.searchResults = [];
                return;
            }

            // A barcode scanner types rapidly or pastes the full barcode and hits Enter
            // If length is at least 4 chars, debounce 180ms to catch fast barcode input and auto-add immediately
            this.scanDebounceTimer = setTimeout(() => {
                let code = (this.searchQuery || '').trim();
                if (code.length >= 4) {
                    // Check if it looks like a barcode or exact item code (digits or alphanumeric)
                    this.searchQuery = '';
                    this.enqueueScan(code, false);
                }
            }, 180);
        },

        handleSearchOrScan() {
            clearTimeout(this.scanDebounceTimer);
            let q = (this.searchQuery || '').trim();
            if (!q) return;
            // IMMEDIATELY clear input field so rapid second scanner burst has a fresh blank box
            this.searchQuery = '';
            this.enqueueScan(q, true);
        },

        enqueueScan(barcode, showAlertOnEmpty = false) {
            const cleanCode = (barcode || '').trim();
            if (!cleanCode) return;
            this.scanQueue.push({ code: cleanCode, showAlert: showAlertOnEmpty });
            this.processScanQueue();
        },

        processScanQueue() {
            if (this.isProcessingScan || this.scanQueue.length === 0) return;

            const next = this.scanQueue.shift();
            const q = next.code;
            this.isProcessingScan = true;

            fetch('{{ route('pos.search') }}?q=' + encodeURIComponent(q))
                .then(r => r.json())
                .then(data => {
                    this.isProcessingScan = false;
                    if (data.length === 1) {
                        // Exactly 1 match found -> AUTO-ADD to cart immediately without clicking button
                        this.addToCart(data[0]);
                        this.searchResults = [];
                    } else if (data.length > 1) {
                        // Check if one of them is an EXACT barcode or SKU match
                        const exactMatch = data.find(item => 
                            (item.barcode && item.barcode.toLowerCase() === q.toLowerCase()) ||
                            (item.sku && item.sku.toLowerCase() === q.toLowerCase())
                        );
                        if (exactMatch) {
                            this.addToCart(exactMatch);
                            this.searchResults = [];
                        } else {
                            this.searchResults = data;
                        }
                    } else {
                        this.searchResults = [];
                        if (next.showAlert) {
                            this.triggerStockWarning('No product found for barcode / SKU: ' + q);
                        }
                    }
                    // Process next queued scan if user scanned multiple items in fast succession
                    this.processScanQueue();
                })
                .catch(() => {
                    this.isProcessingScan = false;
                    if (next.showAlert) {
                        this.triggerStockWarning('Search network error while scanning: ' + q);
                    }
                    this.processScanQueue();
                });
        },

        triggerStockWarning(msg) {
            this.stockAlertMessage = msg;
            // Auto dismiss after 6 seconds
            setTimeout(() => {
                if (this.stockAlertMessage === msg) {
                    this.stockAlertMessage = '';
                }
            }, 6000);
        },

        persistCart() {
            try {
                localStorage.setItem('pos_active_cart', JSON.stringify(this.cart || []));
            } catch (e) {}
        },

        addToCart(item) {
            let existing = this.cart.find(c => c.variant_id === item.id);
            const availableStock = Number(item.available_stock !== undefined ? item.available_stock : 0);

            if (existing) {
                const requestedQty = existing.quantity + 1;
                if (requestedQty > availableStock) {
                    this.triggerStockWarning(`⚠️ স্টক ঘাটতি সতর্কবার্তা: "${item.name} (${item.color || ''}/${item.size || ''})" স্টকে আছে মাত্র ${availableStock} পিস! স্ক্যান করা হয়েছে (${requestedQty} পিস)।`);
                }
                existing.quantity += 1;
                existing.available_stock = availableStock;
            } else {
                if (availableStock <= 0) {
                    this.triggerStockWarning(`⚠️ স্টক শেষ সতর্কবার্তা: "${item.name} (${item.color || ''}/${item.size || ''})" বর্তমানে স্টকে নেই (০ পিস)!`);
                }
                this.cart.push({
                    variant_id: item.id,
                    product_id: item.product_id || null,
                    name: item.name,
                    color: item.color,
                    size: item.size,
                    category_id: item.category_id || null,
                    barcode: item.barcode,
                    sku: item.sku,
                    price: Number(item.price),
                    available_stock: availableStock,
                    quantity: 1
                });
            }
            this.searchResults = [];
            this.searchQuery = '';
            this.checkPromotionValidity();
            this.$nextTick(() => {
                if (this.$refs.barcodeInput) {
                    this.$refs.barcodeInput.focus();
                }
            });
        },

        incrementQty(idx) {
            const line = this.cart[idx];
            if (line.quantity + 1 > line.available_stock) {
                this.triggerStockWarning(`Stock Warning: "${line.name}" only has ${line.available_stock} pcs available.`);
            }
            line.quantity += 1;
            this.checkPromotionValidity();
        },

        decrementQty(idx) {
            if (this.cart[idx].quantity > 1) {
                this.cart[idx].quantity -= 1;
            } else {
                this.removeLine(idx);
            }
            this.checkPromotionValidity();
        },

        removeLine(idx) {
            this.cart.splice(idx, 1);
            this.checkPromotionValidity();
        },

        clearCart() {
            if (confirm('Clear all items from current cart?')) {
                this.cart = [];
                this.selectedPromotion = '';
            }
        },

        totalItemCount() {
            return this.cart.reduce((sum, line) => sum + line.quantity, 0);
        },

        subtotal() {
            return this.cart.reduce((sum, line) => sum + (line.price * line.quantity), 0);
        },

        /**
         * Dynamic coupons that are valid for the products currently inside the cart.
         * If a coupon is assigned to a specific product, it only appears if that product is in the cart!
         */
        availablePromotions() {
            if (!this.cart || this.cart.length === 0) {
                return [];
            }

            const currentProductIds = this.cart.map(i => Number(i.product_id)).filter(Boolean);
            const currentCategoryIds = this.cart.map(i => Number(i.category_id)).filter(Boolean);

            return this.activePromotions.filter(promo => {
                // If promotion is restricted to a specific product:
                if (promo.product_id) {
                    return currentProductIds.includes(Number(promo.product_id));
                }

                // If promotion is restricted to a category:
                if (promo.category_id) {
                    return currentCategoryIds.includes(Number(promo.category_id));
                }

                // Storewide promotion: valid for any cart
                return true;
            }).map(promo => {
                let badge = '';
                if (promo.product_id) {
                    badge = ` (For: ${promo.product_name || 'Product'})`;
                } else if (promo.category_id) {
                    badge = ` (Cat: ${promo.category_name || 'Category'})`;
                } else {
                    badge = ' (Storewide)';
                }

                return {
                    ...promo,
                    label: `${promo.name} - ${Number(promo.discount_percentage)}% OFF${badge}`
                };
            });
        },

        checkPromotionValidity() {
            if (!this.selectedPromotion) return;
            const validCodes = this.availablePromotions().map(p => p.code);
            if (!validCodes.includes(this.selectedPromotion)) {
                this.selectedPromotion = '';
            }
        },

        promoDiscount() {
            if (!this.selectedPromotion) return 0;
            const promo = this.activePromotions.find(p => p.code === this.selectedPromotion);
            if (!promo) return 0;

            const rate = Number(promo.discount_percentage || 0);
            if (rate <= 0) return 0;

            if (promo.min_spend && this.subtotal() < promo.min_spend) {
                return 0;
            }

            // Product-specific discount: ONLY discounts the specific product's lines
            if (promo.product_id) {
                let eligibleSubtotal = 0;
                this.cart.forEach(item => {
                    if (Number(item.product_id) === Number(promo.product_id)) {
                        eligibleSubtotal += (item.price * item.quantity);
                    }
                });
                return Math.round((eligibleSubtotal * (rate / 100)) * 100) / 100;
            }

            // Category-specific discount: ONLY discounts that category's lines
            if (promo.category_id) {
                let eligibleSubtotal = 0;
                this.cart.forEach(item => {
                    if (Number(item.category_id) === Number(promo.category_id)) {
                        eligibleSubtotal += (item.price * item.quantity);
                    }
                });
                return Math.round((eligibleSubtotal * (rate / 100)) * 100) / 100;
            }

            // Storewide discount
            return Math.round((this.subtotal() * (rate / 100)) * 100) / 100;
        },

        taxableAmount() {
            return Math.max(0, this.subtotal() - this.promoDiscount());
        },

        vatAmount() {
            if (!this.vatEnabled || this.taxRatePercent <= 0) return 0;
            return this.taxableAmount() * (this.taxRatePercent / 100);
        },

        rawTotal() {
            return this.taxableAmount() + this.vatAmount();
        },

        roundedTotal() {
            return Math.round(this.rawTotal());
        },

        roundingAdjustment() {
            return this.roundedTotal() - this.rawTotal();
        },

        saveTaxSettings() {
            this.taxModalSubmitting = true;
            this.taxModalError = '';
            this.taxModalSuccess = '';

            const payload = {
                vat_enabled: this.modalVatEnabled ? '1' : '0',
                vat_rate: this.modalVatEnabled ? this.modalVatRate : '0.00',
                vat_label: this.modalVatLabel || 'Tax / VAT',
                _token: '{{ csrf_token() }}'
            };

            fetch('{{ route('admin.tax.update') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify(payload)
            })
            .then(res => res.json())
            .then(data => {
                this.taxModalSubmitting = false;
                if (data.success) {
                    this.vatEnabled = data.vat_enabled;
                    this.taxRatePercent = data.vat_rate;
                    this.vatLabel = data.vat_label;
                    this.taxModalSuccess = data.message;
                    setTimeout(() => {
                        this.showTaxModal = false;
                        this.taxModalSuccess = '';
                    }, 1200);
                } else {
                    this.taxModalError = data.message || 'Failed to update tax settings';
                }
            })
            .catch(err => {
                this.taxModalSubmitting = false;
                this.taxModalError = 'Network or server error updating settings';
            });
        },

        openPaymentModal() {
            let total = this.roundedTotal();
            this.paymentSplits = [
                { payment_method: 'cash', amount: total, tendered_amount: total, reference: '' }
            ];
            this.showPayModal = true;
            this.$nextTick(() => {
                let el = document.getElementById('splitTenderedInput_0');
                if (el) {
                    el.focus();
                    el.select();
                }
            });
        },

        onMethodChange(idx) {
            let pay = this.paymentSplits[idx];
            let req = this.paymentSplits.length === 1 ? this.roundedTotal() : Number(pay.amount || 0);
            if (pay.payment_method === 'cash') {
                if (!pay.tendered_amount || Number(pay.tendered_amount) < req) {
                    pay.tendered_amount = req;
                }
                this.$nextTick(() => {
                    let el = document.getElementById('splitTenderedInput_' + idx);
                    if (el) { el.focus(); el.select(); }
                });
            } else {
                pay.tendered_amount = req;
            }
        },

        addSplit() {
            let rem = this.remainingDue();
            this.paymentSplits.push({ payment_method: 'bkash', amount: rem, tendered_amount: rem, reference: '' });
        },

        removeSplit(idx) {
            if (this.paymentSplits.length > 1) {
                this.paymentSplits.splice(idx, 1);
                if (this.paymentSplits.length === 1) {
                    this.paymentSplits[0].amount = this.roundedTotal();
                }
            }
        },

        setFullDue(idx) {
            let other = this.paymentSplits.reduce((sum, p, i) => i !== idx ? sum + Number(p.amount || 0) : sum, 0);
            let needed = Math.max(0, this.roundedTotal() - other);
            this.paymentSplits[idx].amount = needed;
            if (this.paymentSplits[idx].payment_method === 'cash') {
                this.paymentSplits[idx].tendered_amount = needed;
            }
        },

        totalAllocated() {
            if (this.paymentSplits.length === 1) return this.roundedTotal();
            return this.paymentSplits.reduce((sum, p) => sum + Number(p.amount || 0), 0);
        },

        remainingDue() {
            if (this.paymentSplits.length === 1) return 0;
            return Math.max(0, this.roundedTotal() - this.totalAllocated());
        },

        cashShortage() {
            return this.paymentSplits.reduce((sum, p) => {
                if (p.payment_method === 'cash') {
                    let req = this.paymentSplits.length === 1 ? this.roundedTotal() : Number(p.amount || 0);
                    let tendered = Number(p.tendered_amount || 0);
                    let diff = req - tendered;
                    return diff > 0 ? sum + diff : sum;
                }
                return sum;
            }, 0);
        },

        totalDue() {
            return this.remainingDue() + this.cashShortage();
        },

        cashChange() {
            if (this.totalDue() > 0) return 0;
            return this.paymentSplits.reduce((sum, p) => {
                if (p.payment_method === 'cash') {
                    let req = this.paymentSplits.length === 1 ? this.roundedTotal() : Number(p.amount || 0);
                    let tendered = Number(p.tendered_amount || 0);
                    let diff = tendered - req;
                    return diff > 0 ? sum + diff : sum;
                }
                return sum;
            }, 0);
        },

        canCheckout() {
            if (this.isSubmitting || this.roundedTotal() <= 0) {
                return false;
            }
            return this.totalDue() === 0 && this.remainingDue() === 0;
        },

        submitCheckout() {
            if (!this.canCheckout()) {
                if (this.totalDue() > 0) {
                    alert('Cannot complete checkout: Due / Shortage of ৳' + this.totalDue().toFixed(2) + ' exists. Customer must pay full amount.');
                }
                return;
            }

            this.isSubmitting = true;

            let paymentsPayload = this.paymentSplits.map(p => {
                let reqAmount = this.paymentSplits.length === 1 ? this.roundedTotal() : Number(p.amount || 0);
                let tendered = p.payment_method === 'cash' ? Number(p.tendered_amount || 0) : reqAmount;
                return {
                    payment_method: p.payment_method,
                    amount: reqAmount,
                    tendered_amount: tendered,
                    reference: p.reference || ''
                };
            });

            let payload = {
                items: this.cart.map(c => ({
                    product_variant_id: c.variant_id,
                    quantity: c.quantity
                })),
                payments: paymentsPayload,
                customer_phone: this.customerPhone,
                customer_name: this.customerName,
                promotion_code: this.selectedPromotion,
                idempotency_key: this.idempotencyKey
            };

            fetch('{{ route('pos.checkout') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify(payload)
            })
            .then(res => res.json())
            .then(data => {
                this.isSubmitting = false;
                if (data.success) {
                    try {
                        localStorage.removeItem('pos_active_cart');
                        localStorage.removeItem('pos_active_customer_phone');
                        localStorage.removeItem('pos_active_customer_name');
                    } catch (e) {}
                    window.location.href = data.receipt_url;
                } else {
                    alert('Checkout Error: ' + (data.message || 'Validation failed.'));
                }
            })
            .catch(err => {
                this.isSubmitting = false;
                alert('Network connection lost or checkout failed.');
            });
        }
    }
}
</script>
@endsection
