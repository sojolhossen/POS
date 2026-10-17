@extends('layouts.app')

@section('title', 'Retail Sales History & Invoices')
@section('header', 'Sales History & Invoices')

@section('content')
<div class="space-y-6" x-data="{ selectedSale: null, showModal: false }">

    <!-- TOP STATS CARDS -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Total Revenue -->
        <div class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 shadow-sm relative overflow-hidden group">
            <div class="absolute -right-4 -bottom-4 w-20 h-20 bg-indigo-500/10 rounded-full group-hover:scale-125 transition"></div>
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Total Sales Revenue</span>
                <span class="p-2 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </span>
            </div>
            <div class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">
                ৳{{ number_format($totalSalesAmount, 2) }}
            </div>
            <div class="text-[11px] font-medium text-slate-500 mt-1">
                From current filter selection
            </div>
        </div>

        <!-- Total Invoices -->
        <div class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 shadow-sm relative overflow-hidden group">
            <div class="absolute -right-4 -bottom-4 w-20 h-20 bg-emerald-500/10 rounded-full group-hover:scale-125 transition"></div>
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Invoices Processed</span>
                <span class="p-2 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </span>
            </div>
            <div class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">
                {{ number_format($totalInvoicesCount) }} <span class="text-xs font-semibold text-slate-400">orders</span>
            </div>
            <div class="text-[11px] font-medium text-slate-500 mt-1">
                Discount Given: ৳{{ number_format($totalDiscountAmount, 2) }}
            </div>
        </div>

        <!-- Cash In Hand -->
        <div class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 shadow-sm relative overflow-hidden group">
            <div class="absolute -right-4 -bottom-4 w-20 h-20 bg-cyan-500/10 rounded-full group-hover:scale-125 transition"></div>
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Cash Received</span>
                <span class="p-2 rounded-xl bg-cyan-50 dark:bg-cyan-950/60 text-cyan-600 dark:text-cyan-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                </span>
            </div>
            <div class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">
                ৳{{ number_format($cashTotal, 2) }}
            </div>
            <div class="text-[11px] font-medium text-emerald-600 dark:text-emerald-400 mt-1 font-semibold">
                Physically collected cash
            </div>
        </div>

        <!-- Digital & MFS -->
        <div class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 shadow-sm relative overflow-hidden group">
            <div class="absolute -right-4 -bottom-4 w-20 h-20 bg-violet-500/10 rounded-full group-hover:scale-125 transition"></div>
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Digital & MFS / Card</span>
                <span class="p-2 rounded-xl bg-violet-50 dark:bg-violet-950/60 text-violet-600 dark:text-violet-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                </span>
            </div>
            <div class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">
                ৳{{ number_format($digitalTotal, 2) }}
            </div>
            <div class="text-[11px] font-medium text-violet-600 dark:text-violet-400 mt-1 font-semibold">
                bKash, Nagad, Rocket & Cards
            </div>
        </div>
    </div>

    <!-- FILTER & SEARCH PANEL -->
    <div class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 shadow-sm">
        <form method="GET" action="{{ route('pos.sales.history') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3">
            
            <!-- Search Keyword -->
            <div class="lg:col-span-2">
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">
                    Search Invoice / Customer
                </label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </span>
                    <input type="text" name="search" value="{{ request('search') }}" 
                           placeholder="Invoice # (e.g. POS-2026...), phone or name" 
                           class="w-full pl-9 pr-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                </div>
            </div>

            <!-- Date From -->
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">
                    From Date
                </label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" 
                       class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
            </div>

            <!-- Date To -->
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">
                    To Date
                </label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" 
                       class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
            </div>

            <!-- Payment Method -->
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">
                    Payment Rail
                </label>
                <select name="payment_method" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    <option value="">All Methods</option>
                    <option value="cash" {{ request('payment_method') === 'cash' ? 'selected' : '' }}>Cash</option>
                    <option value="bkash" {{ request('payment_method') === 'bkash' ? 'selected' : '' }}>bKash</option>
                    <option value="nagad" {{ request('payment_method') === 'nagad' ? 'selected' : '' }}>Nagad</option>
                    <option value="rocket" {{ request('payment_method') === 'rocket' ? 'selected' : '' }}>Rocket</option>
                    <option value="card" {{ request('payment_method') === 'card' ? 'selected' : '' }}>Debit/Credit Card</option>
                </select>
            </div>

            <!-- Store Selector (For Super Admin) -->
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">
                    Store / Branch
                </label>
                <select name="store_id" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    <option value="">All Stores</option>
                    @foreach($stores as $s)
                        <option value="{{ $s->id }}" {{ (string) request('store_id') === (string) $s->id ? 'selected' : '' }}>
                            {{ $s->name }} ({{ $s->code }})
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Buttons -->
            <div class="lg:col-span-6 flex items-center justify-between pt-2 border-t border-slate-100 dark:border-slate-800">
                <div class="flex items-center gap-2">
                    <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-xl transition shadow-sm flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                        Filter Sales
                    </button>
                    @if(request()->anyFilled(['search', 'date_from', 'date_to', 'payment_method', 'store_id', 'cashier_id']))
                        <a href="{{ route('pos.sales.history') }}" class="px-3.5 py-2 text-xs font-semibold text-slate-500 hover:text-slate-800 dark:hover:text-slate-200 bg-slate-100 dark:bg-slate-800 rounded-xl transition">
                            Clear Filters
                        </a>
                    @endif
                </div>

                <a href="{{ route('pos.index') }}" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl transition shadow-md shadow-emerald-600/20 flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                    New POS Checkout
                </a>
            </div>

        </form>
    </div>

    <!-- INVOICES TABLE -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/90 dark:border-slate-800 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-200/90 dark:border-slate-800 flex items-center justify-between">
            <h3 class="font-extrabold text-sm text-slate-900 dark:text-white flex items-center gap-2">
                <svg class="w-4 h-4 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                Retail Sales Ledger & Tax Invoices
            </h3>
            <span class="text-xs text-slate-500 font-semibold">
                Showing {{ $sales->firstItem() ?? 0 }} - {{ $sales->lastItem() ?? 0 }} of {{ $sales->total() }} sales
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600 dark:text-slate-300">
                <thead class="bg-slate-50 dark:bg-slate-800/60 uppercase text-[10px] font-extrabold tracking-wider text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
                    <tr>
                        <th class="py-3 px-4">Invoice #</th>
                        <th class="py-3 px-4">Date & Time</th>
                        <th class="py-3 px-4">Branch / Store</th>
                        <th class="py-3 px-4">Cashier</th>
                        <th class="py-3 px-4">Customer</th>
                        <th class="py-3 px-4 text-center">Items</th>
                        <th class="py-3 px-4 text-right">Net Payable</th>
                        <th class="py-3 px-4">Payment Method</th>
                        <th class="py-3 px-4 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($sales as $sale)
                        <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition">
                            <!-- Invoice # -->
                            <td class="py-3 px-4 font-mono font-bold text-indigo-600 dark:text-indigo-400">
                                <a href="{{ route('pos.receipt', $sale) }}" target="_blank" class="hover:underline flex items-center gap-1.5" title="Click to open thermal receipt">
                                    <span>{{ $sale->invoice_number }}</span>
                                    <svg class="w-3.5 h-3.5 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                </a>
                            </td>

                            <!-- Date & Time -->
                            <td class="py-3 px-4">
                                <div class="font-bold text-slate-900 dark:text-white">{{ $sale->created_at->format('d M Y') }}</div>
                                <div class="text-[10px] text-slate-400 font-mono">{{ $sale->created_at->format('h:i A') }}</div>
                            </td>

                            <!-- Store -->
                            <td class="py-3 px-4 font-semibold text-slate-800 dark:text-slate-200">
                                {{ $sale->store->name ?? 'Main Outlet' }}
                            </td>

                            <!-- Cashier -->
                            <td class="py-3 px-4">
                                <span class="font-medium text-slate-700 dark:text-slate-300">{{ $sale->cashier->name ?? 'Admin' }}</span>
                            </td>

                            <!-- Customer -->
                            <td class="py-3 px-4">
                                @if($sale->customer_name || $sale->customer_phone)
                                    <div class="font-bold text-slate-800 dark:text-slate-200">{{ $sale->customer_name ?: 'Walk-in' }}</div>
                                    <div class="text-[11px] font-mono text-slate-400">{{ $sale->customer_phone ?: '—' }}</div>
                                @else
                                    <span class="text-slate-400 italic">Walk-in Customer</span>
                                @endif
                            </td>

                            <!-- Items Count & Preview -->
                            <td class="py-3 px-4 text-center">
                                <button type="button" 
                                        @click="selectedSale = {{ $sale->toJson() }}; showModal = true"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 font-bold hover:bg-indigo-100 transition" 
                                        title="Click to view line items">
                                    <span>{{ $sale->items->sum('quantity') }} pcs</span>
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                </button>
                            </td>

                            <!-- Net Payable -->
                            <td class="py-3 px-4 text-right">
                                <div class="font-black text-sm text-slate-900 dark:text-white">
                                    ৳{{ number_format($sale->total_payable, 2) }}
                                </div>
                                @if($sale->discount_amount > 0)
                                    <div class="text-[10px] text-rose-500 font-semibold">
                                        -৳{{ number_format($sale->discount_amount, 2) }} disc
                                    </div>
                                @endif
                            </td>

                            <!-- Payment Rails -->
                            <td class="py-3 px-4">
                                <div class="flex flex-wrap gap-1">
                                    @foreach($sale->payments as $pm)
                                        @php
                                            $badgeClass = match($pm->payment_method) {
                                                'cash' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300',
                                                'bkash' => 'bg-pink-100 text-pink-800 dark:bg-pink-950 dark:text-pink-300',
                                                'nagad' => 'bg-orange-100 text-orange-800 dark:bg-orange-950 dark:text-orange-300',
                                                'card' => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-950 dark:text-indigo-300',
                                                default => 'bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-300',
                                            };
                                        @endphp
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase {{ $badgeClass }}">
                                            {{ $pm->payment_method }} (৳{{ number_format($pm->amount, 0) }})
                                        </span>
                                    @endforeach
                                </div>
                            </td>

                            <!-- Actions -->
                            <td class="py-3 px-4 text-center">
                                <div class="flex items-center justify-center gap-1.5">
                                    <!-- Print Receipt -->
                                    <a href="{{ route('pos.receipt', $sale) }}" target="_blank" 
                                       class="p-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300 transition" 
                                       title="View / Print 80mm Receipt">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                    </a>

                                    <!-- Reprint Receipt -->
                                    <a href="{{ route('pos.reprint', $sale) }}" target="_blank" 
                                       class="p-1.5 rounded-lg bg-slate-100 hover:bg-indigo-50 hover:text-indigo-600 dark:bg-slate-800 dark:hover:bg-indigo-950 dark:hover:text-indigo-300 text-slate-600 dark:text-slate-300 transition" 
                                       title="Reprint Receipt (Customer Duplicate)">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"/></svg>
                                    </a>

                                    <!-- Return / Exchange -->
                                    @if(auth()->user()->canAccessModule('returns'))
                                        <a href="{{ route('returns.create', ['invoice_number' => $sale->invoice_number]) }}" 
                                           class="p-1.5 rounded-lg bg-slate-100 hover:bg-amber-50 hover:text-amber-600 dark:bg-slate-800 dark:hover:bg-amber-950 dark:hover:text-amber-300 text-slate-600 dark:text-slate-300 transition" 
                                           title="Process Return or Exchange">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 15v-1a4 4 0 00-4-4H4m0 0l4-4m-4 4l4 4m6 5v1a4 4 0 004 4h8m0 0l-4 4m4-4l-4-4"/></svg>
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="py-12 text-center text-slate-400">
                                <div class="w-12 h-12 mx-auto mb-3 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                </div>
                                <p class="text-sm font-bold text-slate-600 dark:text-slate-400">No Sales Records Found</p>
                                <p class="text-xs text-slate-400 mt-1">Try adjusting your filters or date range.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($sales->hasPages())
            <div class="p-4 border-t border-slate-200/90 dark:border-slate-800">
                {{ $sales->links() }}
            </div>
        @endif
    </div>

    <!-- SALE ITEMS DETAILS MODAL -->
    <div x-cloak x-show="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 backdrop-blur-sm p-4">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl max-w-2xl w-full p-6 shadow-2xl relative"
             @click.away="showModal = false">
            
            <div class="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-slate-800">
                <div>
                    <h3 class="text-base font-extrabold text-slate-900 dark:text-white flex items-center gap-2">
                        <span>Invoice:</span>
                        <span class="font-mono text-indigo-600 dark:text-indigo-400" x-text="selectedSale?.invoice_number"></span>
                    </h3>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Date: <span x-text="selectedSale?.created_at ? new Date(selectedSale.created_at).toLocaleString() : ''"></span>
                    </p>
                </div>
                <button @click="showModal = false" class="p-2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 rounded-xl">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <!-- Items Table in Modal -->
            <div class="mt-4 max-h-72 overflow-y-auto">
                <table class="w-full text-xs text-left">
                    <thead class="bg-slate-50 dark:bg-slate-800 text-[10px] font-bold uppercase tracking-wider text-slate-500">
                        <tr>
                            <th class="p-2.5">Item & Variant</th>
                            <th class="p-2.5 text-center">Qty</th>
                            <th class="p-2.5 text-right">Unit Price</th>
                            <th class="p-2.5 text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        <template x-for="item in selectedSale?.items" :key="item.id">
                            <tr>
                                <td class="p-2.5">
                                    <div class="font-bold text-slate-800 dark:text-slate-200" x-text="item.product_name"></div>
                                    <div class="text-[11px] text-slate-400 font-mono">
                                        <span x-text="item.size ? 'Size: ' + item.size : ''"></span>
                                        <span x-text="item.color ? ' • Color: ' + item.color : ''"></span>
                                        <span x-text="item.barcode ? ' • Barcode: ' + item.barcode : ''"></span>
                                    </div>
                                </td>
                                <td class="p-2.5 text-center font-bold" x-text="item.quantity"></td>
                                <td class="p-2.5 text-right font-mono" x-text="'৳' + parseFloat(item.unit_price).toFixed(2)"></td>
                                <td class="p-2.5 text-right font-bold text-slate-900 dark:text-white font-mono" x-text="'৳' + parseFloat(item.line_total).toFixed(2)"></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <!-- Modal Financial Summary -->
            <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800 flex justify-between items-center text-xs">
                <div class="text-slate-500">
                    <div>Customer: <strong class="text-slate-800 dark:text-slate-200" x-text="selectedSale?.customer_name || 'Walk-in'"></strong></div>
                    <div>Phone: <span class="font-mono" x-text="selectedSale?.customer_phone || 'None'"></span></div>
                </div>
                <div class="text-right">
                    <div class="text-xs text-slate-500">Total Payable:</div>
                    <div class="text-lg font-black text-indigo-600 dark:text-indigo-400 font-mono" x-text="'৳' + parseFloat(selectedSale?.total_payable || 0).toFixed(2)"></div>
                </div>
            </div>

            <div class="mt-5 flex justify-end gap-2">
                <a :href="'/pos/receipt/' + selectedSale?.id" target="_blank" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-xl transition flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                    Print 80mm Receipt
                </a>
                <button type="button" @click="showModal = false" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 font-bold text-xs rounded-xl transition">
                    Close
                </button>
            </div>

        </div>
    </div>

</div>
@endsection
