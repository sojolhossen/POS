@extends('layouts.app')

@section('title', $store->name . ' — Store Details')
@section('header', $store->name)

@section('content')
<div class="space-y-6" x-data="{ activeTab: '{{ request('tab', 'inventory') }}' }">

    <!-- Top Navigation & Action Controls -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-2 border-b border-slate-200/80 dark:border-slate-800">
        <div class="flex items-center gap-2">
            <a href="{{ route('stores.index') }}" 
               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-slate-600 dark:text-slate-300 hover:text-indigo-600 dark:hover:text-indigo-400 bg-white dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700/80 shadow-2xs transition">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                <span>Back to Stores Directory</span>
            </a>
            <span class="text-slate-300 dark:text-slate-700">/</span>
            <span class="text-xs font-mono font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">{{ $store->code }}</span>
        </div>

        <!-- Quick Action Buttons -->
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('transfers.create') }}" 
               class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700/60 shadow-2xs transition">
                <svg class="w-3.5 h-3.5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                </svg>
                <span>New Transfer</span>
            </a>

            <a href="{{ route('barcodes.index') }}" 
               class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700/60 shadow-2xs transition">
                <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
                </svg>
                <span>Barcodes</span>
            </a>

            @if(!$store->is_warehouse && auth()->user()->canAccessModule('pos'))
                <a href="{{ route('pos.index') }}" 
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white shadow-sm transition">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    </svg>
                    <span>Launch POS</span>
                </a>
            @endif

            @if(auth()->user()->isSuperAdmin())
                <a href="{{ route('stores.edit', $store) }}" 
                   class="inline-flex items-center gap-1.5 px-3.5 py-1.5 text-xs font-bold rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white shadow-sm transition">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    <span>Edit Store</span>
                </a>
            @endif
        </div>
    </div>

    <!-- Main Store Profile Hero Card -->
    <div class="relative overflow-hidden rounded-2xl border border-slate-200/80 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-sm">
        <!-- Subtle Top Gradient Bar -->
        <div class="h-2 w-full {{ $store->is_warehouse ? 'bg-gradient-to-r from-purple-500 via-indigo-500 to-sky-500' : 'bg-gradient-to-r from-emerald-500 via-teal-500 to-indigo-500' }}"></div>

        <div class="p-5 sm:p-7">
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                
                <!-- Store Identification & Badges -->
                <div class="flex items-start gap-4">
                    <!-- Icon Box -->
                    <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-2xl flex items-center justify-center shrink-0 shadow-inner {{ $store->is_warehouse ? 'bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300' : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300' }}">
                        @if($store->is_warehouse)
                            <svg class="w-7 h-7 sm:w-8 sm:h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                            </svg>
                        @else
                            <svg class="w-7 h-7 sm:w-8 sm:h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                            </svg>
                        @endif
                    </div>

                    <!-- Name & Information -->
                    <div class="space-y-1.5">
                        <div class="flex flex-wrap items-center gap-2">
                            <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white tracking-tight">{{ $store->name }}</h1>
                            
                            @if($store->is_warehouse)
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-purple-100 text-purple-700 dark:bg-purple-900/60 dark:text-purple-300 border border-purple-200 dark:border-purple-800">
                                    Central Warehouse
                                </span>
                            @else
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-emerald-100 text-emerald-700 dark:bg-emerald-900/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                    Retail Branch
                                </span>
                            @endif

                            @if($store->is_active)
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span> Active Facility
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400 border border-slate-200 dark:border-slate-700">
                                    <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span> Inactive
                                </span>
                            @endif
                        </div>

                        <!-- Code, Phone & Address Pills -->
                        <div class="flex flex-wrap items-center gap-y-1 gap-x-4 text-xs text-slate-500 dark:text-slate-400">
                            <span class="font-mono font-bold text-slate-700 dark:text-slate-200 bg-slate-100 dark:bg-slate-800 px-2 py-0.5 rounded">
                                {{ $store->code }}
                            </span>
                            @if($store->phone)
                                <span class="flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                    {{ $store->phone }}
                                </span>
                            @endif
                            @if($store->email)
                                <span class="flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                    {{ $store->email }}
                                </span>
                            @endif
                            @if($store->address)
                                <span class="flex items-center gap-1 truncate max-w-md" title="{{ $store->address }}">
                                    <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    {{ $store->address }}
                                </span>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Manager & Operating Hours Badge Block -->
                <div class="flex flex-wrap sm:flex-nowrap items-center gap-3 lg:border-l lg:border-slate-100 lg:dark:border-slate-800 lg:pl-6">
                    <!-- Manager Pill -->
                    <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/60 dark:border-slate-700/60 min-w-[190px]">
                        <div class="text-[10px] uppercase font-bold tracking-wider text-slate-400 dark:text-slate-400 mb-1">
                            Assigned Manager
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-7 h-7 rounded-full bg-indigo-100 text-indigo-700 dark:bg-indigo-900/60 dark:text-indigo-300 font-black text-xs flex items-center justify-center shrink-0">
                                {{ $store->manager ? strtoupper(substr($store->manager->name, 0, 1)) : '?' }}
                            </div>
                            <div class="truncate">
                                <div class="text-xs font-bold text-slate-900 dark:text-white truncate">
                                    {{ $store->manager ? $store->manager->name : 'Unassigned' }}
                                </div>
                                <div class="text-[11px] text-slate-400 truncate">
                                    {{ $store->manager ? ($store->manager->phone ?? $store->manager->email) : 'Requires assignment' }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Operating Hours Pill -->
                    <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/60 dark:border-slate-700/60 min-w-[170px]">
                        <div class="text-[10px] uppercase font-bold tracking-wider text-slate-400 dark:text-slate-400 mb-1">
                            Operating Hours
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-7 h-7 rounded-full bg-amber-100 text-amber-700 dark:bg-amber-900/60 dark:text-amber-300 flex items-center justify-center shrink-0">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                            <div>
                                <div class="text-xs font-mono font-bold text-slate-900 dark:text-white">
                                    {{ $store->opening_time ? substr($store->opening_time, 0, 5) . ' — ' . substr($store->closing_time, 0, 5) : '10:00 — 22:00' }}
                                </div>
                                <div class="text-[11px] text-slate-400">
                                    {{ $store->is_warehouse ? 'Logistics Shifts' : 'Daily Retail Shift' }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- 5 Enterprise KPI Summary Cards -->
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3">
        
        <!-- Card 1: Total SKUs / Variants -->
        <div class="p-4 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-2xs flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Total Variants</span>
                <span class="p-1 rounded bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                </span>
            </div>
            <div class="mt-2">
                <div class="text-xl font-black text-slate-900 dark:text-white">{{ number_format($totalSkus) }}</div>
                <div class="text-[11px] text-slate-500 mt-0.5">Assigned Catalog SKUs</div>
            </div>
        </div>

        <!-- Card 2: Units On Hand -->
        <div class="p-4 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-2xs flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Physical Stock</span>
                <span class="p-1 rounded bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                </span>
            </div>
            <div class="mt-2">
                <div class="text-xl font-black text-slate-900 dark:text-white">{{ number_format($totalUnitsOnHand) }} <span class="text-xs font-normal text-slate-400">pcs</span></div>
                <div class="text-[11px] text-emerald-600 font-semibold mt-0.5">{{ number_format($totalUnitsAvailable) }} Available / {{ number_format($totalUnitsReserved) }} Resv</div>
            </div>
        </div>

        <!-- Card 3: Inventory Valuation -->
        <div class="p-4 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-2xs flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Stock Valuation</span>
                <span class="p-1 rounded bg-sky-50 dark:bg-sky-950/50 text-sky-600 dark:text-sky-400">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </span>
            </div>
            <div class="mt-2">
                <div class="text-xl font-black text-indigo-600 dark:text-indigo-400">@bdt($retailValuation)</div>
                <div class="text-[11px] text-slate-500 mt-0.5">Est. Cost: @bdt($costValuation)</div>
            </div>
        </div>

        <!-- Card 4: Low & Out of Stock Alerts -->
        <div class="p-4 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-2xs flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Stock Health</span>
                <span class="p-1 rounded {{ $lowStockCount > 0 || $outOfStockCount > 0 ? 'bg-amber-50 dark:bg-amber-950/50 text-amber-600 dark:text-amber-400' : 'bg-slate-100 dark:bg-slate-800 text-slate-500' }}">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </span>
            </div>
            <div class="mt-2">
                <div class="text-xl font-black {{ $lowStockCount > 0 ? 'text-amber-600' : 'text-slate-900 dark:text-white' }}">
                    {{ $lowStockCount }} <span class="text-xs font-normal text-slate-400">Low</span>
                </div>
                <div class="text-[11px] {{ $outOfStockCount > 0 ? 'text-rose-600 font-bold' : 'text-slate-500' }} mt-0.5">
                    {{ $outOfStockCount }} Out of Stock
                </div>
            </div>
        </div>

        <!-- Card 5: Operational Performance -->
        <div class="p-4 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-2xs flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">
                    {{ $store->is_warehouse ? 'Logistics Flow' : "Today's POS Sales" }}
                </span>
                <span class="p-1 rounded bg-purple-50 dark:bg-purple-950/50 text-purple-600 dark:text-purple-400">
                    @if($store->is_warehouse)
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                    @else
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                    @endif
                </span>
            </div>
            <div class="mt-2">
                @if($store->is_warehouse)
                    <div class="text-xl font-black text-slate-900 dark:text-white">{{ $inTransitInbound + $inTransitOutbound }} <span class="text-xs font-normal text-slate-400">transfers</span></div>
                    <div class="text-[11px] text-slate-500 mt-0.5">{{ $inTransitOutbound }} Outbound | {{ $inTransitInbound }} Inbound</div>
                @else
                    <div class="text-xl font-black text-emerald-600">@bdt($todaySalesAmount)</div>
                    <div class="text-[11px] text-slate-500 mt-0.5">{{ $todaySalesCount }} Receipts Today</div>
                @endif
            </div>
        </div>

    </div>

    <!-- SUB-NAV / TAB BAR (Enterprise Style) -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-200 dark:border-slate-800">
        <div class="flex items-center gap-1 overflow-x-auto text-xs font-medium pb-px">
            <button @click="activeTab = 'inventory'" 
                    :class="activeTab === 'inventory' ? 'text-indigo-600 dark:text-indigo-400 font-bold border-b-2 border-indigo-600 dark:border-indigo-400 bg-slate-100/70 dark:bg-slate-800/60' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'"
                    class="px-4 py-2.5 rounded-t-lg transition flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                <span>Stock on Hand</span>
                <span class="text-[10px] px-1.5 py-0.2 rounded-full bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300 font-mono font-bold">{{ $stockBalances->total() }}</span>
            </button>

            <button @click="activeTab = 'transfers'" 
                    :class="activeTab === 'transfers' ? 'text-indigo-600 dark:text-indigo-400 font-bold border-b-2 border-indigo-600 dark:border-indigo-400 bg-slate-100/70 dark:bg-slate-800/60' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'"
                    class="px-4 py-2.5 rounded-t-lg transition flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                <span>Transfers & Shipments</span>
                @if($inTransitInbound + $inTransitOutbound > 0)
                    <span class="text-[10px] px-1.5 py-0.2 rounded-full bg-indigo-100 dark:bg-indigo-900/60 text-indigo-700 dark:text-indigo-300 font-mono font-bold">
                        {{ $inTransitInbound + $inTransitOutbound }}
                    </span>
                @endif
            </button>

            <button @click="activeTab = 'staff'" 
                    :class="activeTab === 'staff' ? 'text-indigo-600 dark:text-indigo-400 font-bold border-b-2 border-indigo-600 dark:border-indigo-400 bg-slate-100/70 dark:bg-slate-800/60' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'"
                    class="px-4 py-2.5 rounded-t-lg transition flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                <span>Store Team & Staff</span>
                <span class="text-[10px] px-1.5 py-0.2 rounded-full bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300 font-mono font-bold">{{ $staffMembers->count() }}</span>
            </button>

            @if(!$store->is_warehouse)
                <button @click="activeTab = 'sales'" 
                        :class="activeTab === 'sales' ? 'text-indigo-600 dark:text-indigo-400 font-bold border-b-2 border-indigo-600 dark:border-indigo-400 bg-slate-100/70 dark:bg-slate-800/60' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'"
                        class="px-4 py-2.5 rounded-t-lg transition flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    <span>Sales & Cash Drawers</span>
                </button>
            @endif

            <button @click="activeTab = 'facility'" 
                    :class="activeTab === 'facility' ? 'text-indigo-600 dark:text-indigo-400 font-bold border-b-2 border-indigo-600 dark:border-indigo-400 bg-slate-100/70 dark:bg-slate-800/60' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'"
                    class="px-4 py-2.5 rounded-t-lg transition flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span>Store Specifications</span>
            </button>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- TAB 1: STOCK ON HAND (INVENTORY)           -->
    <!-- ========================================== -->
    <div x-show="activeTab === 'inventory'" class="space-y-4">
        
        <!-- Search & Filter Workstation Bar -->
        <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
            <!-- Filter Form -->
            <form method="GET" action="{{ route('stores.show', $store) }}" class="flex flex-wrap items-center gap-3 w-full md:w-auto flex-1">
                <input type="hidden" name="tab" value="inventory">
                
                <div class="relative flex-1 min-w-[240px]">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </span>
                    <input type="text" 
                           name="search" 
                           value="{{ request('search') }}" 
                           placeholder="Search variant by product name, SKU, barcode, size, color..."
                           class="w-full pl-9 pr-3 py-2 text-xs rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:outline-none placeholder-slate-400">
                </div>

                <!-- Stock Level Filter -->
                <select name="stock_status" 
                        onchange="this.form.submit()"
                        class="text-xs rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-200 py-2 px-3 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    <option value="all" {{ request('stock_status') === 'all' ? 'selected' : '' }}>All Stock Levels</option>
                    <option value="in_stock" {{ request('stock_status') === 'in_stock' ? 'selected' : '' }}>Healthy Stock (> 5 pcs)</option>
                    <option value="low_stock" {{ request('stock_status') === 'low_stock' ? 'selected' : '' }}>Low Stock (1 — 5 pcs)</option>
                    <option value="out_of_stock" {{ request('stock_status') === 'out_of_stock' ? 'selected' : '' }}>Out of Stock (0 pcs)</option>
                </select>

                <button type="submit" 
                        class="py-2 px-4 rounded-xl bg-slate-100 dark:bg-slate-800 text-xs font-bold text-slate-700 dark:text-slate-200 hover:bg-slate-200 dark:hover:bg-slate-700 transition">
                    Apply Filter
                </button>

                @if(request('search') || request('stock_status') && request('stock_status') !== 'all')
                    <a href="{{ route('stores.show', ['store' => $store, 'tab' => 'inventory']) }}" 
                       class="text-xs font-bold text-rose-500 hover:text-rose-600 underline">
                        Clear Filters
                    </a>
                @endif
            </form>

            <!-- Export / Quick Info -->
            <div class="text-xs text-slate-400 shrink-0 font-medium">
                Showing <strong class="text-slate-700 dark:text-slate-200">{{ $stockBalances->firstItem() ?? 0 }}-{{ $stockBalances->lastItem() ?? 0 }}</strong> of <strong class="text-slate-700 dark:text-slate-200">{{ $stockBalances->total() }}</strong> records
            </div>
        </div>

        <!-- Inventory Table Card -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-slate-200/80 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/40 text-slate-400 uppercase text-[10px] tracking-wider font-bold">
                            <th class="py-3 px-4">Product & Category</th>
                            <th class="py-3 px-3">SKU</th>
                            <th class="py-3 px-3">Variant (Size / Color)</th>
                            <th class="py-3 px-3">Barcode</th>
                            <th class="py-3 px-3 text-right">Selling Price</th>
                            <th class="py-3 px-3 text-right">On Hand</th>
                            <th class="py-3 px-3 text-right">Available</th>
                            <th class="py-3 px-4 text-center">Stock Health</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80 font-medium">
                        @forelse($stockBalances as $balance)
                            @php
                                $variant = $balance->variant;
                                $product = $variant?->product;
                                $onHand = $balance->quantity_on_hand;
                                $available = $balance->available_quantity;
                            @endphp
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition">
                                <!-- Product & Category -->
                                <td class="py-3.5 px-4">
                                    <div class="font-bold text-slate-900 dark:text-white text-xs">
                                        {{ $product?->name ?? 'Unknown Product' }}
                                    </div>
                                    <div class="flex items-center gap-2 mt-0.5">
                                        @if($product?->category)
                                            <span class="text-[10px] px-1.5 py-0.2 rounded bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-medium">
                                                {{ $product->category->name }}
                                            </span>
                                        @endif
                                        @if($product?->brand)
                                            <span class="text-[10px] text-slate-400 font-medium">
                                                {{ $product->brand }}
                                            </span>
                                        @endif
                                    </div>
                                </td>

                                <!-- SKU -->
                                <td class="py-3.5 px-3 font-mono font-bold text-slate-600 dark:text-slate-300">
                                    {{ $product?->sku ?? '—' }}
                                </td>

                                <!-- Variant (Size / Color) -->
                                <td class="py-3.5 px-3">
                                    <div class="flex items-center gap-1.5">
                                        <span class="px-2 py-0.5 rounded text-[11px] font-black bg-indigo-50 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300 border border-indigo-200/60 dark:border-indigo-800/60 font-mono">
                                            {{ $variant?->size ?? '—' }}
                                        </span>
                                        <span class="text-xs text-slate-700 dark:text-slate-200 font-medium">
                                            {{ $variant?->color ?? 'Standard' }}
                                        </span>
                                    </div>
                                </td>

                                <!-- Barcode -->
                                <td class="py-3.5 px-3 font-mono text-slate-600 dark:text-slate-300">
                                    <div class="flex items-center gap-1">
                                        <span class="font-bold">{{ $variant?->barcode ?? '—' }}</span>
                                    </div>
                                </td>

                                <!-- Selling Price -->
                                <td class="py-3.5 px-3 text-right font-black text-slate-900 dark:text-white">
                                    @bdt($variant?->effective_selling_price ?? 0)
                                </td>

                                <!-- On Hand -->
                                <td class="py-3.5 px-3 text-right font-black text-slate-900 dark:text-white text-sm">
                                    {{ $onHand }}
                                </td>

                                <!-- Available -->
                                <td class="py-3.5 px-3 text-right font-black text-emerald-600 dark:text-emerald-400 text-sm">
                                    {{ $available }}
                                </td>

                                <!-- Stock Status Badge -->
                                <td class="py-3.5 px-4 text-center">
                                    @if($onHand <= 0)
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-rose-100 text-rose-700 dark:bg-rose-950/60 dark:text-rose-400 border border-rose-200 dark:border-rose-900">
                                            <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span> Out of Stock
                                        </span>
                                    @elseif($onHand <= 5)
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-amber-100 text-amber-700 dark:bg-amber-950/60 dark:text-amber-400 border border-amber-200 dark:border-amber-900">
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span> Low Stock
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-900">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Adequate
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-12 text-center text-slate-400">
                                    <div class="flex flex-col items-center justify-center space-y-2">
                                        <svg class="w-8 h-8 text-slate-300 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                        <div class="text-sm font-semibold text-slate-600 dark:text-slate-400">No stock balances match your criteria.</div>
                                        <p class="text-xs text-slate-400 max-w-sm">No items were found for this store with the current search or filter query.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="p-4 border-t border-slate-100 dark:border-slate-800">
                {{ $stockBalances->links() }}
            </div>
        </div>

    </div>

    <!-- ========================================== -->
    <!-- TAB 2: TRANSFERS & SHIPMENTS               -->
    <!-- ========================================== -->
    <div x-show="activeTab === 'transfers'" class="space-y-4" style="display: none;">
        
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-base font-bold text-slate-900 dark:text-white">Inter-Store Logistics & Transfers</h3>
                <p class="text-xs text-slate-500">Active and recent inventory dispatches involving {{ $store->name }}</p>
            </div>
            <a href="{{ route('transfers.create') }}" 
               class="inline-flex items-center gap-1.5 py-2 px-3 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-xs font-bold text-white transition shadow-sm">
                <span>+ Create Transfer</span>
            </a>
        </div>

        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-slate-200/80 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/40 text-slate-400 uppercase text-[10px] tracking-wider font-bold">
                            <th class="py-3 px-4">Transfer Reference</th>
                            <th class="py-3 px-3">Flow Type</th>
                            <th class="py-3 px-3">Partner Location</th>
                            <th class="py-3 px-3 text-center">Status</th>
                            <th class="py-3 px-3 text-right">Total Items</th>
                            <th class="py-3 px-3">Date</th>
                            <th class="py-3 px-4 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80 font-medium">
                        @forelse($recentTransfers as $transfer)
                            @php
                                $isSource = $transfer->source_store_id === $store->id;
                            @endphp
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition">
                                <td class="py-3.5 px-4 font-mono font-bold text-indigo-600 dark:text-indigo-400">
                                    {{ $transfer->reference_number }}
                                </td>
                                <td class="py-3.5 px-3">
                                    @if($isSource)
                                        <span class="inline-flex items-center gap-1 text-[11px] font-bold text-amber-600 dark:text-amber-400">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                                            Outbound Dispatch
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-600 dark:text-emerald-400">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
                                            Inbound Receiving
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-3 font-semibold text-slate-800 dark:text-slate-200">
                                    {{ $isSource ? $transfer->destinationStore?->name : $transfer->sourceStore?->name }}
                                </td>
                                <td class="py-3.5 px-3 text-center">
                                    @if($transfer->status === 'received')
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-emerald-100 text-emerald-800 dark:bg-emerald-900/60 dark:text-emerald-300">
                                            Received
                                        </span>
                                    @elseif($transfer->status === 'in_transit')
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-amber-100 text-amber-800 dark:bg-amber-900/60 dark:text-amber-300 animate-pulse">
                                            In Transit
                                        </span>
                                    @elseif($transfer->status === 'cancelled')
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-slate-200 text-slate-700 dark:bg-slate-700 dark:text-slate-300">
                                            Cancelled
                                        </span>
                                    @else
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-sky-100 text-sky-800 dark:bg-sky-900/60 dark:text-sky-300">
                                            {{ ucfirst($transfer->status) }}
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-3 text-right font-black text-slate-900 dark:text-white">
                                    {{ $transfer->items->sum('quantity') }} pcs
                                </td>
                                <td class="py-3.5 px-3 text-slate-500 text-[11px]">
                                    {{ $transfer->created_at->format('d M Y, h:i A') }}
                                </td>
                                <td class="py-3.5 px-4 text-right">
                                    <a href="{{ route('transfers.show', $transfer) }}" class="text-xs font-bold text-indigo-600 hover:text-indigo-700 underline">
                                        View Details
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-8 text-center text-slate-400">
                                    No transfer shipments recorded for this location yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- ========================================== -->
    <!-- TAB 3: STORE TEAM & PERSONNEL              -->
    <!-- ========================================== -->
    <div x-show="activeTab === 'staff'" class="space-y-4" style="display: none;">
        
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-base font-bold text-slate-900 dark:text-white">Assigned Personnel & Staff</h3>
                <p class="text-xs text-slate-500">Staff members authorized for this location's operations</p>
            </div>
            @if(auth()->user()->isSuperAdmin())
                <a href="{{ route('staff.index') }}" 
                   class="inline-flex items-center gap-1.5 py-2 px-3 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-xs font-bold text-slate-700 dark:text-slate-200 transition">
                    <span>Manage All Staff</span>
                </a>
            @endif
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            
            <!-- Manager Card -->
            @if($store->manager)
                <div class="p-5 rounded-2xl border-2 border-indigo-200 dark:border-indigo-900/60 bg-white dark:bg-slate-900 shadow-sm relative">
                    <div class="absolute top-4 right-4">
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-indigo-100 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300">
                            Primary Manager
                        </span>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-xl bg-indigo-600 text-white font-black text-lg flex items-center justify-center shrink-0">
                            {{ strtoupper(substr($store->manager->name, 0, 1)) }}
                        </div>
                        <div>
                            <div class="font-bold text-slate-900 dark:text-white text-sm">{{ $store->manager->name }}</div>
                            <div class="text-xs text-indigo-600 font-medium">{{ $store->manager->role_name }}</div>
                        </div>
                    </div>
                    <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800 space-y-1 text-xs text-slate-500">
                        <div class="flex items-center gap-2">
                            <span class="text-slate-400">Email:</span>
                            <span class="font-medium text-slate-700 dark:text-slate-200">{{ $store->manager->email }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-slate-400">Phone:</span>
                            <span class="font-medium text-slate-700 dark:text-slate-200">{{ $store->manager->phone ?? 'Not provided' }}</span>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Other Store Staff -->
            @forelse($staffMembers as $member)
                @if(!$store->manager || $member->id !== $store->manager->id)
                    <div class="p-5 rounded-2xl border border-slate-200/80 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-sm">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200 font-bold text-sm flex items-center justify-center shrink-0">
                                    {{ strtoupper(substr($member->name, 0, 1)) }}
                                </div>
                                <div>
                                    <div class="font-bold text-slate-900 dark:text-white text-xs">{{ $member->name }}</div>
                                    <div class="text-[11px] text-slate-500">{{ $member->role_name }}</div>
                                </div>
                            </div>
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        </div>
                        <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800 space-y-1 text-xs text-slate-500">
                            <div class="flex items-center gap-2 truncate">
                                <span class="text-slate-400">Email:</span>
                                <span class="font-medium text-slate-700 dark:text-slate-200 truncate">{{ $member->email }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-slate-400">Phone:</span>
                                <span class="font-medium text-slate-700 dark:text-slate-200">{{ $member->phone ?? '—' }}</span>
                            </div>
                        </div>
                    </div>
                @endif
            @empty
                @if(!$store->manager)
                    <div class="col-span-full py-8 text-center text-slate-400 bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800">
                        No staff members currently linked to this location.
                    </div>
                @endif
            @endforelse

        </div>

    </div>

    <!-- ========================================== -->
    <!-- TAB 4: SALES & CASH DRAWERS (RETAIL ONLY)  -->
    <!-- ========================================== -->
    @if(!$store->is_warehouse)
        <div x-show="activeTab === 'sales'" class="space-y-4" style="display: none;">
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- Cash Drawer Card -->
                <div class="p-5 rounded-2xl border border-slate-200/80 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-sm flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Cash Register Status</span>
                            @if($activeRegister)
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">
                                    OPEN
                                </span>
                            @else
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400">
                                    CLOSED
                                </span>
                            @endif
                        </div>
                        <div class="text-lg font-black text-slate-900 dark:text-white">
                            {{ $activeRegister ? 'Register is Live & Active' : 'No Open Register' }}
                        </div>
                        @if($activeRegister)
                            <div class="text-xs text-slate-500 mt-1">
                                Opened by <strong class="text-slate-700 dark:text-slate-200">{{ $activeRegister->opener?->name }}</strong> at {{ $activeRegister->opened_at?->format('h:i A') }}
                            </div>
                        @endif
                    </div>
                    <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between">
                        <span class="text-xs text-slate-400">Opening Float:</span>
                        <span class="text-xs font-bold text-slate-800 dark:text-slate-200">
                            {{ $activeRegister ? '@bdt(' . $activeRegister->opening_cash . ')' : '—' }}
                        </span>
                    </div>
                </div>

                <!-- Cumulative Sales Stat -->
                <div class="p-5 rounded-2xl border border-slate-200/80 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-sm flex flex-col justify-between">
                    <div>
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Total All-Time Sales</span>
                        <div class="text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-2">
                            @bdt($totalSalesAmount)
                        </div>
                        <div class="text-xs text-slate-500 mt-1">Cumulative completed POS revenue</div>
                    </div>
                    <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800">
                        <a href="{{ route('pos.sales.history') }}" class="text-xs font-bold text-indigo-600 hover:text-indigo-700 underline">
                            View All Invoices &rarr;
                        </a>
                    </div>
                </div>

                <!-- Fast POS Launcher -->
                <div class="p-5 rounded-2xl border border-indigo-200 dark:border-indigo-900/60 bg-gradient-to-br from-indigo-50 to-purple-50 dark:from-slate-900 dark:to-indigo-950/30 flex flex-col justify-between">
                    <div>
                        <span class="text-[10px] font-bold uppercase tracking-wider text-indigo-600 dark:text-indigo-400">Express Point of Sale</span>
                        <div class="text-base font-black text-slate-900 dark:text-white mt-1">Ready for Checkout?</div>
                        <p class="text-xs text-slate-500 mt-1">Open barcode terminal to scan and bill customers instantly.</p>
                    </div>
                    <div class="mt-4">
                        <a href="{{ route('pos.index') }}" class="w-full py-2.5 px-4 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold flex items-center justify-center gap-2 shadow-sm transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                            <span>Open POS Register</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Recent Invoices Table -->
            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm overflow-hidden">
                <div class="p-4 border-b border-slate-100 dark:border-slate-800">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-200">Recent Customer Invoices</h4>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="border-b border-slate-200/80 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/40 text-slate-400 uppercase text-[10px] tracking-wider font-bold">
                                <th class="py-3 px-4">Invoice #</th>
                                <th class="py-3 px-3">Cashier</th>
                                <th class="py-3 px-3">Customer</th>
                                <th class="py-3 px-3 text-right">Total Payable</th>
                                <th class="py-3 px-3 text-center">Status</th>
                                <th class="py-3 px-3">Date</th>
                                <th class="py-3 px-4 text-right">Receipt</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80 font-medium">
                            @forelse($recentSales as $sale)
                                <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition">
                                    <td class="py-3 px-4 font-mono font-bold text-slate-900 dark:text-white">
                                        {{ $sale->invoice_number }}
                                    </td>
                                    <td class="py-3 px-3 text-slate-700 dark:text-slate-300">
                                        {{ $sale->cashier?->name ?? '—' }}
                                    </td>
                                    <td class="py-3 px-3 text-slate-600 dark:text-slate-400">
                                        {{ $sale->customer_name ?: ($sale->customer?->name ?? 'Walk-in Guest') }}
                                    </td>
                                    <td class="py-3 px-3 text-right font-black text-slate-900 dark:text-white">
                                        @bdt($sale->total_payable)
                                    </td>
                                    <td class="py-3 px-3 text-center">
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">
                                            Completed
                                        </span>
                                    </td>
                                    <td class="py-3 px-3 text-slate-500 text-[11px]">
                                        {{ $sale->created_at->format('d M, h:i A') }}
                                    </td>
                                    <td class="py-3 px-4 text-right">
                                        <a href="{{ route('pos.receipt', $sale) }}" target="_blank" class="text-xs font-bold text-indigo-600 hover:text-indigo-700 underline">
                                            Print
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="py-8 text-center text-slate-400">
                                        No sales completed at this store yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    @endif

    <!-- ========================================== -->
    <!-- TAB 5: FACILITY & OPERATIONAL SPECS        -->
    <!-- ========================================== -->
    <div x-show="activeTab === 'facility'" class="space-y-4" style="display: none;">
        
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-6 sm:p-8 shadow-sm">
            <h3 class="text-base font-bold text-slate-900 dark:text-white mb-6">Facility Profile & Operational Configuration</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-xs">
                
                <div class="space-y-4">
                    <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-800">
                        <div class="text-[10px] uppercase font-bold text-slate-400 mb-1">Official Name</div>
                        <div class="text-sm font-bold text-slate-900 dark:text-white">{{ $store->name }}</div>
                    </div>

                    <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-800">
                        <div class="text-[10px] uppercase font-bold text-slate-400 mb-1">Facility Code & Designation</div>
                        <div class="text-sm font-mono font-bold text-indigo-600 dark:text-indigo-400">{{ $store->code }}</div>
                        <div class="text-[11px] text-slate-500 mt-1">Used for inter-store stock transfers, barcode tracking, and POS invoicing prefixes.</div>
                    </div>

                    <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-800">
                        <div class="text-[10px] uppercase font-bold text-slate-400 mb-1">Facility Role & Classification</div>
                        <div class="text-sm font-bold text-slate-800 dark:text-slate-200">
                            {{ $store->is_warehouse ? 'Central Logistics & Fulfillment Warehouse' : 'Physical Retail Point-of-Sale Storefront' }}
                        </div>
                    </div>
                </div>

                <div class="space-y-4">
                    <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-800">
                        <div class="text-[10px] uppercase font-bold text-slate-400 mb-1">Physical Location Address</div>
                        <div class="text-sm font-medium text-slate-800 dark:text-slate-200 leading-relaxed">
                            {{ $store->address ?? 'No physical address provided' }}
                        </div>
                    </div>

                    <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-800">
                        <div class="text-[10px] uppercase font-bold text-slate-400 mb-1">Contact Telephony & Email</div>
                        <div class="text-sm font-medium text-slate-800 dark:text-slate-200">
                            Phone: <span class="font-bold">{{ $store->phone ?? '—' }}</span>
                        </div>
                        <div class="text-sm font-medium text-slate-800 dark:text-slate-200 mt-0.5">
                            Email: <span class="font-bold">{{ $store->email ?? '—' }}</span>
                        </div>
                    </div>

                    <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-800">
                        <div class="text-[10px] uppercase font-bold text-slate-400 mb-1">Created & Maintained Since</div>
                        <div class="text-sm font-medium text-slate-800 dark:text-slate-200">
                            {{ $store->created_at->format('F d, Y \a\t h:i A') }} ({{ $store->created_at->diffForHumans() }})
                        </div>
                    </div>
                </div>

            </div>
        </div>

    </div>

</div>
@endsection
