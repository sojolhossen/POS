@extends('layouts.app')

@section('title', __('messages.dashboard'))
@section('header', __('messages.dashboard'))

@section('content')
<div class="space-y-6">

    <!-- ENTERPRISE EXECUTIVE HEADER & STATS BAR -->
    <div class="bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 rounded-2xl p-6 text-white shadow-xl border border-slate-800 relative overflow-hidden">
        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <div class="inline-flex items-center gap-2 px-2.5 py-1 rounded-md bg-white/10 backdrop-blur-md border border-white/15 text-[11px] font-semibold text-indigo-200 mb-2">
                    <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                    <span>{{ auth()->user()->role ? (is_object(auth()->user()->role) && method_exists(auth()->user()->role, 'label') ? auth()->user()->role->label() : (string) auth()->user()->role) : 'Staff User' }}</span>
                    <span>•</span>
                    <span>{{ auth()->user()->store ? auth()->user()->store->name : 'Central Headquarters (All Stores)' }}</span>
                </div>
                <h2 class="text-xl sm:text-2xl font-black tracking-tight">
                    Welcome back, {{ auth()->user()->name }}
                </h2>
                <p class="text-xs text-slate-300 mt-1 max-w-xl">
                    Centralized Bangladesh Multi-Store Retail ERP • BDT (৳) Currency • Code 128 Variant Traceability
                </p>
            </div>

            <!-- Quick Action Hub -->
            <div class="flex flex-wrap items-center gap-2">
                @if(auth()->user()->isSuperAdmin())
                    <a href="{{ route('products.create') }}" class="py-2 px-3 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs transition shadow-md shadow-indigo-600/30 flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        <span>Add Product</span>
                    </a>
                    <a href="{{ route('stores.create') }}" class="py-2 px-3 rounded-xl bg-white/10 hover:bg-white/20 text-white font-bold text-xs border border-white/20 transition">
                        + New Store
                    </a>
                @endif
                <a href="{{ route('barcodes.index') }}" class="py-2 px-3 rounded-xl bg-white/10 hover:bg-white/20 text-white font-bold text-xs border border-white/20 transition flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                    <span>Barcode Station</span>
                </a>
            </div>
        </div>
    </div>

    <!-- ROLE SPECIFIC METRICS -->

    @if(auth()->user()->isSuperAdmin())
        <!-- SUPER ADMIN OVERVIEW TILES -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            
            <div class="bg-white dark:bg-slate-900 rounded-xl p-5 border border-slate-200/80 dark:border-slate-800 shadow-sm flex flex-col justify-between">
                <div class="flex items-center justify-between text-slate-500 dark:text-slate-400 mb-2">
                    <span class="text-[11px] font-bold uppercase tracking-wider">Retail Stores</span>
                    <div class="w-7 h-7 rounded-lg bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    </div>
                </div>
                <div>
                    <div class="text-2xl font-black text-slate-900 dark:text-white">{{ $metrics['total_stores'] }} Outlets</div>
                    <div class="text-[11px] text-emerald-600 dark:text-emerald-400 font-semibold mt-0.5 flex items-center gap-1">
                        <span>+ {{ $metrics['total_warehouses'] }} Central Warehouse</span>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-slate-900 rounded-xl p-5 border border-slate-200/80 dark:border-slate-800 shadow-sm flex flex-col justify-between">
                <div class="flex items-center justify-between text-slate-500 dark:text-slate-400 mb-2">
                    <span class="text-[11px] font-bold uppercase tracking-wider">Catalog Styles</span>
                    <div class="w-7 h-7 rounded-lg bg-purple-50 dark:bg-purple-950/60 text-purple-600 dark:text-purple-400 flex items-center justify-center">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                    </div>
                </div>
                <div>
                    <div class="text-2xl font-black text-slate-900 dark:text-white">{{ $metrics['total_products'] }} Styles</div>
                    <div class="text-[11px] text-indigo-600 dark:text-indigo-400 font-semibold mt-0.5">
                        {{ $metrics['total_variants'] }} Unique Barcode Variants
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-slate-900 rounded-xl p-5 border border-slate-200/80 dark:border-slate-800 shadow-sm flex flex-col justify-between">
                <div class="flex items-center justify-between text-slate-500 dark:text-slate-400 mb-2">
                    <span class="text-[11px] font-bold uppercase tracking-wider">Pending Adjustments</span>
                    <div class="w-7 h-7 rounded-lg bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 flex items-center justify-center">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    </div>
                </div>
                <div>
                    <div class="text-2xl font-black {{ $metrics['pending_adjustments'] > 0 ? 'text-amber-500' : 'text-slate-900 dark:text-white' }}">
                        {{ $metrics['pending_adjustments'] }}
                    </div>
                    <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                        Requires Super Admin Approval
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-slate-900 rounded-xl p-5 border border-slate-200/80 dark:border-slate-800 shadow-sm flex flex-col justify-between">
                <div class="flex items-center justify-between text-slate-500 dark:text-slate-400 mb-2">
                    <span class="text-[11px] font-bold uppercase tracking-wider">Staff Accounts</span>
                    <div class="w-7 h-7 rounded-lg bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    </div>
                </div>
                <div>
                    <div class="text-2xl font-black text-slate-900 dark:text-white">{{ $metrics['total_staff'] }} Active Users</div>
                    <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                        Protected by Server Policies
                    </div>
                </div>
            </div>

        </div>

        <!-- STORES GRID & RECENT AUDIT LOGS -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            
            <!-- Stores Network (7 cols) -->
            <div class="lg:col-span-7 bg-white dark:bg-slate-900 rounded-xl border border-slate-200/80 dark:border-slate-800 p-5 shadow-sm">
                <div class="flex items-center justify-between pb-3 mb-3 border-b border-slate-100 dark:border-slate-800">
                    <div>
                        <h3 class="font-bold text-sm text-slate-900 dark:text-white">Active Retail & Warehouse Network</h3>
                        <p class="text-[11px] text-slate-500">Central Warehouse + 6 Regional Outlets</p>
                    </div>
                    <a href="{{ route('stores.index') }}" class="text-xs font-bold text-indigo-600 dark:text-indigo-400 hover:underline">
                        View Network →
                    </a>
                </div>

                <div class="space-y-2.5">
                    @foreach($metrics['stores_list'] as $store)
                        <div class="p-3 rounded-lg bg-slate-50 dark:bg-slate-800/50 border border-slate-200/60 dark:border-slate-700/60 flex items-center justify-between hover:border-slate-300 dark:hover:border-slate-600 transition">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg {{ $store->is_warehouse ? 'bg-purple-100 dark:bg-purple-950/60 text-purple-700 dark:text-purple-300' : 'bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300' }} flex items-center justify-center font-bold text-xs flex-shrink-0">
                                    {{ $store->is_warehouse ? 'WH' : 'ST' }}
                                </div>
                                <div>
                                    <div class="font-bold text-xs text-slate-900 dark:text-white flex items-center gap-2">
                                        <span>{{ $store->name }}</span>
                                        <span class="font-mono text-[10px] px-1.5 py-0.2 rounded bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300 font-semibold">{{ $store->code }}</span>
                                    </div>
                                    <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                                        Manager: {{ $store->manager ? $store->manager->name : 'Unassigned' }} • {{ $store->phone ?? 'Dhaka, BD' }}
                                    </div>
                                </div>
                            </div>
                            <a href="{{ route('stores.show', $store) }}" class="text-xs font-semibold py-1 px-2.5 rounded-lg bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-600 text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-600 transition">
                                Manage
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Recent Security & Activity Audit Log (5 cols) -->
            <div class="lg:col-span-5 bg-white dark:bg-slate-900 rounded-xl border border-slate-200/80 dark:border-slate-800 p-5 shadow-sm flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between pb-3 mb-3 border-b border-slate-100 dark:border-slate-800">
                        <div>
                            <h3 class="font-bold text-sm text-slate-900 dark:text-white">Security & Audit Trail</h3>
                            <p class="text-[11px] text-slate-500">Real-time immutable log of admin actions</p>
                        </div>
                        <a href="{{ route('audit.index') }}" class="text-xs font-bold text-indigo-600 dark:text-indigo-400 hover:underline">
                            All Logs →
                        </a>
                    </div>

                    <div class="space-y-2">
                        @forelse($metrics['recent_audits'] as $audit)
                            <div class="p-2.5 rounded-lg bg-slate-50 dark:bg-slate-800/40 border border-slate-100 dark:border-slate-800 text-xs flex items-center justify-between">
                                <div class="truncate mr-2">
                                    <div class="font-semibold text-slate-800 dark:text-slate-200 truncate">
                                        <span class="font-mono text-[10px] px-1.5 py-0.5 rounded bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold mr-1">{{ $audit->action }}</span>
                                        <span class="text-[11px]">{{ $audit->user ? $audit->user->name : 'System' }}</span>
                                    </div>
                                    <div class="text-[10px] text-slate-400 truncate mt-0.5">
                                        {{ $audit->reason ?? 'Verified action recorded' }}
                                    </div>
                                </div>
                                <div class="text-[10px] text-slate-400 font-mono whitespace-nowrap">
                                    @posDate($audit->created_at)
                                </div>
                            </div>
                        @empty
                            <div class="text-xs text-slate-500 py-6 text-center">No recent audit records found.</div>
                        @endforelse
                    </div>
                </div>

                <div class="pt-4 mt-4 border-t border-slate-100 dark:border-slate-800 text-[11px] text-slate-400 flex items-center justify-between">
                    <span>Audit Retention: <strong class="text-slate-700 dark:text-slate-300">Permanent</strong></span>
                    <span>Single Session: <strong class="text-emerald-500">Enforced</strong></span>
                </div>
            </div>

        </div>

    @elseif(auth()->user()->isStoreManager())
        <!-- STORE MANAGER TILES -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="bg-white dark:bg-slate-900 rounded-xl p-5 border border-slate-200/80 dark:border-slate-800 shadow-sm">
                <div class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Store Stock on Hand</div>
                <div class="text-3xl font-black text-slate-900 dark:text-white">{{ number_format($metrics['store_stock_on_hand']) }} Units</div>
                <div class="text-[11px] text-slate-500 mt-1 font-semibold">{{ auth()->user()->store->name }}</div>
            </div>

            <div class="bg-white dark:bg-slate-900 rounded-xl p-5 border border-slate-200/80 dark:border-slate-800 shadow-sm">
                <div class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Low Stock Alerts</div>
                <div class="text-3xl font-black {{ $metrics['store_low_stock'] > 0 ? 'text-rose-500' : 'text-slate-900 dark:text-white' }}">
                    {{ $metrics['store_low_stock'] }} Items
                </div>
                <div class="text-[11px] text-slate-500 mt-1">Reorder threshold monitoring</div>
            </div>

            <div class="bg-white dark:bg-slate-900 rounded-xl p-5 border border-slate-200/80 dark:border-slate-800 shadow-sm">
                <div class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Pending Adjustments</div>
                <div class="text-3xl font-black text-amber-500">{{ $metrics['store_pending_adjustments'] }}</div>
                <div class="text-[11px] text-slate-500 mt-1">Awaiting Super Admin Approval</div>
            </div>
        </div>

    @elseif(auth()->user()->isWarehouseStaff())
        <!-- WAREHOUSE STAFF VIEW -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="bg-white dark:bg-slate-900 rounded-xl p-6 border border-slate-200/80 dark:border-slate-800 shadow-sm">
                <div class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Warehouse Stock Inventory</div>
                <div class="text-3xl font-black text-slate-900 dark:text-white">{{ number_format($metrics['warehouse_stock_on_hand']) }} Units</div>
                <div class="mt-4">
                    <a href="{{ route('inventory.warehouse.create') }}" class="inline-flex items-center gap-2 py-2 px-4 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs shadow-md shadow-indigo-600/30">
                        + New Stock Intake
                    </a>
                </div>
            </div>

            <div class="bg-white dark:bg-slate-900 rounded-xl p-6 border border-slate-200/80 dark:border-slate-800 shadow-sm">
                <h3 class="font-bold text-sm text-slate-800 dark:text-slate-200 mb-3">Warehouse Workstation</h3>
                <div class="flex flex-wrap gap-2 text-xs">
                    <a href="{{ route('barcodes.index') }}" class="py-2 px-3 rounded-lg bg-slate-100 dark:bg-slate-800 font-semibold hover:bg-slate-200">Barcode Labels</a>
                    <a href="{{ route('stock-counts.create') }}" class="py-2 px-3 rounded-lg bg-slate-100 dark:bg-slate-800 font-semibold hover:bg-slate-200">Physical Count</a>
                    <a href="{{ route('inventory.ledger') }}" class="py-2 px-3 rounded-lg bg-slate-100 dark:bg-slate-800 font-semibold hover:bg-slate-200">Intake Ledger</a>
                </div>
            </div>
        </div>

    @elseif(auth()->user()->isAccountant())
        <!-- ACCOUNTANT VALUATION VIEW -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="bg-white dark:bg-slate-900 rounded-xl p-5 border border-slate-200/80 dark:border-slate-800 shadow-sm">
                <div class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Total Stock Purchase Cost</div>
                <div class="text-2xl font-black text-indigo-600 dark:text-indigo-400">@bdt($metrics['total_cost_value'])</div>
                <div class="text-[11px] text-slate-500 mt-1">Based on purchase cost</div>
            </div>

            <div class="bg-white dark:bg-slate-900 rounded-xl p-5 border border-slate-200/80 dark:border-slate-800 shadow-sm">
                <div class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Retail MRP Valuation</div>
                <div class="text-2xl font-black text-emerald-600 dark:text-emerald-400">@bdt($metrics['total_retail_value'])</div>
                <div class="text-[11px] text-slate-500 mt-1">Gross realizable value</div>
            </div>

            <div class="bg-white dark:bg-slate-900 rounded-xl p-5 border border-slate-200/80 dark:border-slate-800 shadow-sm">
                <div class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Gross Realization Margin</div>
                @php
                    $costVal = (float)$metrics['total_cost_value'];
                    $retVal = (float)$metrics['total_retail_value'];
                    $margin = $retVal > 0 ? (($retVal - $costVal) / $retVal) * 100 : 0;
                @endphp
                <div class="text-2xl font-black text-slate-900 dark:text-white">{{ number_format($margin, 1) }}%</div>
                <div class="text-[11px] text-slate-500 mt-1">Average across all categories</div>
            </div>
        </div>

    @else
        <!-- CASHIER VIEW -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl p-8 border border-slate-200/80 dark:border-slate-800 text-center max-w-lg mx-auto shadow-sm">
            <div class="w-14 h-14 rounded-full bg-emerald-100 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400 flex items-center justify-center mx-auto mb-4">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
            </div>
            <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-1">Cashier Workstation</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mb-6">Assigned Outlet: <strong class="text-slate-800 dark:text-slate-200">{{ auth()->user()->store ? auth()->user()->store->name : 'N/A' }}</strong></p>
            <div class="flex flex-col sm:flex-row gap-2.5 justify-center">
                <a href="{{ route('barcodes.index') }}" class="py-2.5 px-5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-md shadow-emerald-600/30">
                    Scan Barcodes
                </a>
                <a href="{{ route('inventory.index') }}" class="py-2.5 px-5 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-200 font-bold text-xs hover:bg-slate-200">
                    Check Inventory
                </a>
            </div>
        </div>
    @endif

</div>
@endsection
