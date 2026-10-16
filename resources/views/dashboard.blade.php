@extends('layouts.app')

@section('title', __('messages.dashboard'))
@section('header', __('messages.dashboard'))

@section('content')
<div class="space-y-6">

    <!-- WELCOME BANNER -->
    <div class="bg-gradient-to-r from-indigo-900 via-indigo-800 to-slate-900 rounded-3xl p-6 sm:p-8 text-white shadow-xl relative overflow-hidden">
        <div class="relative z-10 max-w-2xl">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-500/30 border border-indigo-400/30 text-indigo-200 text-xs font-semibold mb-3">
                <span>{{ auth()->user()->role ? auth()->user()->role->label() : 'Staff User' }}</span>
                <span>•</span>
                <span>{{ auth()->user()->store ? auth()->user()->store->name : 'Central Headquarters' }}</span>
            </div>
            <h2 class="text-2xl sm:text-3xl font-extrabold tracking-tight">
                Welcome back, {{ auth()->user()->name }}
            </h2>
            <p class="text-indigo-200 text-sm mt-2 leading-relaxed">
                Centralized Bangladesh Retail Architecture • BDT Currency • Immutable Inventory Ledger • Multi-Store Security
            </p>
        </div>
        <div class="absolute right-0 bottom-0 opacity-10 translate-x-10 translate-y-10 pointer-events-none">
            <svg class="w-96 h-96 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
        </div>
    </div>

    <!-- ROLE SPECIFIC METRICS -->

    @if(auth()->user()->isSuperAdmin())
        <!-- SUPER ADMIN OVERVIEW -->
        <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-slate-900 rounded-2xl p-5 border border-slate-200/80 dark:border-slate-800 shadow-sm">
                <div class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Retail Stores</div>
                <div class="text-2xl font-black text-slate-900 dark:text-white">{{ $metrics['total_stores'] }} Outlets</div>
                <div class="text-[11px] text-emerald-600 dark:text-emerald-400 font-semibold mt-1">+ {{ $metrics['total_warehouses'] }} Central Warehouse</div>
            </div>

            <div class="bg-white dark:bg-slate-900 rounded-2xl p-5 border border-slate-200/80 dark:border-slate-800 shadow-sm">
                <div class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Catalog Products</div>
                <div class="text-2xl font-black text-slate-900 dark:text-white">{{ $metrics['total_products'] }} Styles</div>
                <div class="text-[11px] text-indigo-600 dark:text-indigo-400 font-semibold mt-1">{{ $metrics['total_variants'] }} Unique Barcode Variants</div>
            </div>

            <div class="bg-white dark:bg-slate-900 rounded-2xl p-5 border border-slate-200/80 dark:border-slate-800 shadow-sm">
                <div class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Pending Adjustments</div>
                <div class="text-2xl font-black {{ $metrics['pending_adjustments'] > 0 ? 'text-amber-500' : 'text-slate-900 dark:text-white' }}">
                    {{ $metrics['pending_adjustments'] }}
                </div>
                <div class="text-[11px] text-slate-500 mt-1">Requires Super Admin Approval</div>
            </div>

            <div class="bg-white dark:bg-slate-900 rounded-2xl p-5 border border-slate-200/80 dark:border-slate-800 shadow-sm">
                <div class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Staff Accounts</div>
                <div class="text-2xl font-black text-slate-900 dark:text-white">{{ $metrics['total_staff'] }} Users</div>
                <div class="text-[11px] text-slate-500 mt-1">Protected by Server Policies</div>
            </div>
        </div>

        <!-- STORES GRID & RECENT AUDIT LOGS -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Stores List -->
            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-6 shadow-sm">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-bold text-base text-slate-900 dark:text-white">Active Retail & Warehouse Network</h3>
                    <a href="{{ route('stores.create') }}" class="text-xs font-bold text-indigo-600 dark:text-indigo-400 hover:underline">+ New Store</a>
                </div>
                <div class="space-y-3">
                    @foreach($metrics['stores_list'] as $store)
                        <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/60 dark:border-slate-700/60 flex items-center justify-between">
                            <div>
                                <div class="font-bold text-sm text-slate-900 dark:text-white flex items-center gap-2">
                                    <span>{{ $store->name }}</span>
                                    @if($store->is_warehouse)
                                        <span class="px-2 py-0.5 text-[10px] rounded bg-purple-100 text-purple-700 dark:bg-purple-900/50 dark:text-purple-300 font-bold">WAREHOUSE</span>
                                    @endif
                                </div>
                                <div class="text-xs text-slate-500 mt-0.5">Code: <span class="font-mono">{{ $store->code }}</span> • Manager: {{ $store->manager ? $store->manager->name : 'Unassigned' }}</div>
                            </div>
                            <a href="{{ route('stores.show', $store) }}" class="text-xs font-semibold py-1 px-3 rounded-lg bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-600 hover:bg-slate-50">View Store</a>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Recent Security & Activity Audit Log -->
            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-6 shadow-sm">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-bold text-base text-slate-900 dark:text-white">Recent Security & Activity Audit</h3>
                    <a href="{{ route('audit.index') }}" class="text-xs font-bold text-indigo-600 dark:text-indigo-400 hover:underline">View All Logs</a>
                </div>
                <div class="space-y-2.5">
                    @forelse($metrics['recent_audits'] as $audit)
                        <div class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800/40 text-xs flex items-center justify-between">
                            <div>
                                <div class="font-semibold text-slate-800 dark:text-slate-200">
                                    <span class="font-mono text-[11px] px-1.5 py-0.5 rounded bg-slate-200 dark:bg-slate-700 mr-1.5">{{ $audit->action }}</span>
                                    {{ $audit->user ? $audit->user->name : 'System/Guest' }}
                                </div>
                                <div class="text-[11px] text-slate-400 mt-0.5">{{ $audit->reason ?? 'Standard system event' }}</div>
                            </div>
                            <div class="text-[10px] text-slate-400 font-mono">
                                @posDate($audit->created_at) {{ $audit->created_at->format('H:i') }}
                            </div>
                        </div>
                    @empty
                        <div class="text-xs text-slate-500 py-4 text-center">No recent audit logs.</div>
                    @endforelse
                </div>
            </div>
        </div>

    @elseif(auth()->user()->isStoreManager())
        <!-- STORE MANAGER VIEW -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="bg-white dark:bg-slate-900 rounded-2xl p-5 border border-slate-200/80 dark:border-slate-800 shadow-sm">
                <div class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Store Stock on Hand</div>
                <div class="text-2xl font-black text-slate-900 dark:text-white">{{ number_format($metrics['store_stock_on_hand']) }} Units</div>
                <div class="text-[11px] text-slate-500 mt-1">{{ auth()->user()->store->name }}</div>
            </div>

            <div class="bg-white dark:bg-slate-900 rounded-2xl p-5 border border-slate-200/80 dark:border-slate-800 shadow-sm">
                <div class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Low Stock Alerts</div>
                <div class="text-2xl font-black {{ $metrics['store_low_stock'] > 0 ? 'text-rose-500' : 'text-slate-900 dark:text-white' }}">
                    {{ $metrics['store_low_stock'] }} Items
                </div>
                <div class="text-[11px] text-slate-500 mt-1">At or below reorder threshold</div>
            </div>

            <div class="bg-white dark:bg-slate-900 rounded-2xl p-5 border border-slate-200/80 dark:border-slate-800 shadow-sm">
                <div class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Pending Adjustments</div>
                <div class="text-2xl font-black text-amber-500">{{ $metrics['store_pending_adjustments'] }}</div>
                <div class="text-[11px] text-slate-500 mt-1">Awaiting Super Admin Approval</div>
            </div>
        </div>

    @elseif(auth()->user()->isWarehouseStaff())
        <!-- WAREHOUSE STAFF VIEW -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="bg-white dark:bg-slate-900 rounded-2xl p-6 border border-slate-200/80 dark:border-slate-800 shadow-sm">
                <div class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Warehouse Physical Inventory</div>
                <div class="text-3xl font-black text-slate-900 dark:text-white">{{ number_format($metrics['warehouse_stock_on_hand']) }} Units</div>
                <div class="mt-4">
                    <a href="{{ route('inventory.warehouse.create') }}" class="inline-flex items-center gap-2 py-2 px-4 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs shadow-md shadow-indigo-600/30">
                        + New Stock Intake
                    </a>
                </div>
            </div>

            <div class="bg-white dark:bg-slate-900 rounded-2xl p-6 border border-slate-200/80 dark:border-slate-800 shadow-sm">
                <h3 class="font-bold text-sm text-slate-800 dark:text-slate-200 mb-3">Warehouse Quick Tools</h3>
                <div class="flex flex-wrap gap-2 text-xs">
                    <a href="{{ route('barcodes.index') }}" class="py-2 px-3 rounded-lg bg-slate-100 dark:bg-slate-800 font-semibold hover:bg-slate-200">Barcode Workstation</a>
                    <a href="{{ route('stock-counts.create') }}" class="py-2 px-3 rounded-lg bg-slate-100 dark:bg-slate-800 font-semibold hover:bg-slate-200">Start Physical Count</a>
                    <a href="{{ route('inventory.ledger') }}" class="py-2 px-3 rounded-lg bg-slate-100 dark:bg-slate-800 font-semibold hover:bg-slate-200">Intake History</a>
                </div>
            </div>
        </div>

    @elseif(auth()->user()->isAccountant())
        <!-- ACCOUNTANT VALUATION VIEW -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div class="bg-white dark:bg-slate-900 rounded-2xl p-6 border border-slate-200/80 dark:border-slate-800 shadow-sm">
                <div class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Total Stock Cost Value</div>
                <div class="text-2xl font-black text-indigo-600 dark:text-indigo-400">@bdt($metrics['total_cost_value'])</div>
                <div class="text-[11px] text-slate-500 mt-1">Based on purchase prices</div>
            </div>

            <div class="bg-white dark:bg-slate-900 rounded-2xl p-6 border border-slate-200/80 dark:border-slate-800 shadow-sm">
                <div class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Total Retail MRP Valuation</div>
                <div class="text-2xl font-black text-emerald-600 dark:text-emerald-400">@bdt($metrics['total_retail_value'])</div>
                <div class="text-[11px] text-slate-500 mt-1">Potential gross realization</div>
            </div>

            <div class="bg-white dark:bg-slate-900 rounded-2xl p-6 border border-slate-200/80 dark:border-slate-800 shadow-sm">
                <div class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Gross Inventory Margin</div>
                @php
                    $costVal = (float)$metrics['total_cost_value'];
                    $retVal = (float)$metrics['total_retail_value'];
                    $margin = $retVal > 0 ? (($retVal - $costVal) / $retVal) * 100 : 0;
                @endphp
                <div class="text-2xl font-black text-slate-900 dark:text-white">{{ number_format($margin, 1) }}%</div>
                <div class="text-[11px] text-slate-500 mt-1">Across all variants</div>
            </div>
        </div>

    @else
        <!-- CASHIER VIEW -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl p-8 border border-slate-200/80 dark:border-slate-800 text-center max-w-lg mx-auto shadow-sm">
            <div class="w-16 h-16 rounded-full bg-emerald-100 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400 flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
            </div>
            <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-2">Cashier Terminal Ready</h3>
            <p class="text-sm text-slate-500 dark:text-slate-400 mb-6">Assigned Store: <span class="font-bold text-slate-800 dark:text-slate-200">{{ auth()->user()->store ? auth()->user()->store->name : 'N/A' }}</span></p>
            <div class="flex flex-col sm:flex-row gap-3 justify-center">
                <a href="{{ route('barcodes.index') }}" class="py-3 px-6 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-sm shadow-lg shadow-emerald-600/30">
                    Open Barcode Scanner Workstation
                </a>
                <a href="{{ route('inventory.index') }}" class="py-3 px-6 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-200 font-bold text-sm hover:bg-slate-200">
                    Check Store Stock
                </a>
            </div>
        </div>
    @endif

</div>
@endsection
