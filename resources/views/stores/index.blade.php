@extends('layouts.app')

@section('title', __('messages.stores'))
@section('header', __('messages.stores'))

@section('content')
<div class="space-y-6">

    <!-- Header Actions & Store Metrics -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <h2 class="text-xl font-bold text-slate-900 dark:text-white">{{ __('messages.stores') }}</h2>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700 dark:bg-indigo-950/50 dark:text-indigo-300 border border-indigo-200/60 dark:border-indigo-800/60">
                    Grid View
                </span>
            </div>
            <p class="text-xs text-slate-500 mt-1">Centralized Retail Outlet & Warehouse Directory</p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <!-- Summary Stats Badges -->
            <div class="hidden sm:flex items-center gap-2 bg-white dark:bg-slate-900 p-1.5 rounded-xl border border-slate-200/80 dark:border-slate-800 shadow-2xs">
                <div class="flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold text-slate-600 dark:text-slate-300">
                    <span class="w-2 h-2 rounded-full bg-slate-400"></span>
                    <span>Total: <strong>{{ $stats['total'] ?? $stores->total() }}</strong></span>
                </div>
                <div class="w-px h-4 bg-slate-200 dark:bg-slate-700"></div>
                <div class="flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold text-emerald-600 dark:text-emerald-400">
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                    <span>Retail: <strong>{{ $stats['retail'] ?? 0 }}</strong></span>
                </div>
                <div class="w-px h-4 bg-slate-200 dark:bg-slate-700"></div>
                <div class="flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold text-purple-600 dark:text-purple-400">
                    <span class="w-2 h-2 rounded-full bg-purple-500"></span>
                    <span>Warehouse: <strong>{{ $stats['warehouse'] ?? 0 }}</strong></span>
                </div>
            </div>

            @if(auth()->user()->isSuperAdmin())
                <a href="{{ route('stores.create') }}" 
                   class="inline-flex items-center gap-2 py-2 px-3.5 rounded-xl font-bold text-xs text-white bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 transition shadow-sm shadow-indigo-600/30">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    <span>Add New Store / Warehouse</span>
                </a>
            @endif
        </div>
    </div>

    <!-- Search & Filter Controls Toolbar -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-2xs p-3.5 sm:p-4">
        <form method="GET" action="{{ route('stores.index') }}" class="flex flex-col md:flex-row md:items-center justify-between gap-3">
            <!-- Search Input -->
            <div class="relative flex-1 max-w-md">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <input type="text" name="search" value="{{ request('search') }}" 
                       placeholder="Search stores by name, code, phone..."
                       class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/80 pl-9 pr-3 py-2.5 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 focus:outline-none transition">
            </div>

            <!-- Filter Controls -->
            <div class="flex flex-wrap items-center gap-2">
                <!-- Type Filter -->
                <select name="type" onchange="this.form.submit()" 
                        class="text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/80 py-2 px-3 text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 focus:outline-none font-medium">
                    <option value="" {{ request('type') == '' ? 'selected' : '' }}>All Store Types</option>
                    <option value="retail" {{ request('type') == 'retail' ? 'selected' : '' }}>Retail Outlets Only</option>
                    <option value="warehouse" {{ request('type') == 'warehouse' ? 'selected' : '' }}>Warehouses Only</option>
                </select>

                <!-- Status Filter -->
                <select name="status" onchange="this.form.submit()" 
                        class="text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/80 py-2 px-3 text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 focus:outline-none font-medium">
                    <option value="" {{ request('status') == '' ? 'selected' : '' }}>All Status</option>
                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active Stores</option>
                    <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive Stores</option>
                </select>

                <button type="submit" 
                        class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-xs font-bold text-slate-700 dark:text-slate-200 transition">
                    <span>Filter</span>
                </button>

                @if(request()->hasAny(['search', 'type', 'status']))
                    <a href="{{ route('stores.index') }}" 
                       class="inline-flex items-center gap-1 px-2.5 py-2 rounded-xl text-xs font-semibold text-rose-600 hover:bg-rose-50 dark:text-rose-400 dark:hover:bg-rose-950/40 transition" 
                       title="Reset Filters">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                        <span>Reset</span>
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Stores Responsive Grid View -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
        @forelse($stores as $store)
            <div class="group relative bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-2xs hover:shadow-md hover:border-indigo-300 dark:hover:border-indigo-700/60 transition-all duration-200 flex flex-col justify-between overflow-hidden">
                
                <!-- Top Accent Line -->
                <div class="h-1 w-full {{ $store->is_warehouse ? 'bg-gradient-to-r from-purple-500 to-indigo-500' : 'bg-gradient-to-r from-emerald-500 to-teal-500' }}"></div>

                <div class="p-5 flex-1 flex flex-col">
                    <!-- Card Header: Icon, Badges, Name & Code -->
                    <div class="flex items-start justify-between gap-3 mb-4">
                        <div class="flex items-center gap-3.5 min-w-0">
                            <!-- Store Avatar / Icon Box -->
                            <div class="w-12 h-12 rounded-xl flex items-center justify-center shrink-0 shadow-2xs transition group-hover:scale-105 duration-200 {{ $store->is_warehouse ? 'bg-purple-50 text-purple-600 dark:bg-purple-950/40 dark:text-purple-300 border border-purple-200/60 dark:border-purple-800/60' : 'bg-emerald-50 text-emerald-600 dark:bg-emerald-950/40 dark:text-emerald-300 border border-emerald-200/60 dark:border-emerald-800/60' }}">
                                @if($store->is_warehouse)
                                    <!-- Warehouse Building Icon -->
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                    </svg>
                                @else
                                    <!-- Storefront Icon -->
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                                    </svg>
                                @endif
                            </div>

                            <!-- Name & Code -->
                            <div class="min-w-0">
                                <h3 class="text-base font-bold text-slate-900 dark:text-white group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition truncate" title="{{ $store->name }}">
                                    <a href="{{ route('stores.show', $store) }}">
                                        {{ $store->name }}
                                    </a>
                                </h3>
                                <div class="flex items-center gap-1.5 mt-0.5">
                                    <span class="inline-block px-2 py-0.5 rounded text-[11px] font-mono font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200/80 dark:border-slate-700/80">
                                        {{ $store->code }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Status & Type Badges -->
                        <div class="flex flex-col items-end gap-1.5 shrink-0">
                            @if($store->is_warehouse)
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wide bg-purple-100 text-purple-700 dark:bg-purple-900/50 dark:text-purple-300 border border-purple-200/60 dark:border-purple-800/60">
                                    Warehouse
                                </span>
                            @else
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wide bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300 border border-emerald-200/60 dark:border-emerald-800/60">
                                    Retail
                                </span>
                            @endif

                            @if($store->is_active)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-400 border border-emerald-200/50 dark:border-emerald-800/50">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                    <span>Active</span>
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400 border border-slate-200/50 dark:border-slate-700/50">
                                    <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                                    <span>Inactive</span>
                                </span>
                            @endif
                        </div>
                    </div>

                    <!-- Meta Information Box -->
                    <div class="mt-2 bg-slate-50/80 dark:bg-slate-800/50 rounded-xl p-3 border border-slate-100 dark:border-slate-800/80 space-y-2 text-xs">
                        <!-- Manager & Staff Count -->
                        <div class="flex items-center justify-between gap-2">
                            <div class="flex items-center gap-2 min-w-0">
                                <div class="w-5 h-5 rounded-full bg-slate-200 dark:bg-slate-700 flex items-center justify-center text-[10px] font-bold text-slate-600 dark:text-slate-300 shrink-0">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                    </svg>
                                </div>
                                <span class="text-slate-700 dark:text-slate-200 font-medium truncate" title="Store Manager">
                                    {{ $store->manager ? $store->manager->name : 'No Manager Assigned' }}
                                </span>
                            </div>

                            @if(isset($store->staff_count))
                                <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-slate-500 dark:text-slate-400 bg-white dark:bg-slate-800 px-2 py-0.5 rounded-md border border-slate-200/60 dark:border-slate-700/60 shrink-0">
                                    <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                    </svg>
                                    <span>{{ $store->staff_count }} Staff</span>
                                </span>
                            @endif
                        </div>

                        <!-- Phone -->
                        <div class="flex items-center gap-2 text-slate-600 dark:text-slate-300">
                            <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                            </svg>
                            <span class="truncate">{{ $store->phone ?: 'No phone provided' }}</span>
                        </div>

                        <!-- Address -->
                        <div class="flex items-start gap-2 text-slate-500 dark:text-slate-400">
                            <svg class="w-3.5 h-3.5 text-slate-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <span class="truncate" title="{{ $store->address }}">{{ $store->address ?: 'No address specified' }}</span>
                        </div>

                        <!-- Operating Hours -->
                        <div class="flex items-center gap-2 text-slate-600 dark:text-slate-300 font-mono text-[11px] pt-1 border-t border-slate-200/60 dark:border-slate-700/60">
                            <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span>{{ $store->opening_time ? substr($store->opening_time, 0, 5) . ' - ' . substr($store->closing_time, 0, 5) : 'Hours Not Set' }}</span>
                        </div>
                    </div>
                </div>

                <!-- Card Action Footer -->
                <div class="px-5 py-3.5 bg-slate-50/60 dark:bg-slate-800/40 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between gap-2">
                    <a href="{{ route('stores.show', $store) }}" 
                       class="inline-flex items-center gap-1.5 py-1.5 px-3 rounded-lg text-xs font-bold text-indigo-600 hover:text-indigo-700 hover:bg-indigo-50 dark:text-indigo-400 dark:hover:bg-indigo-950/40 transition">
                        <span>View Details</span>
                        <svg class="w-3.5 h-3.5 group-hover:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>

                    <div class="flex items-center gap-1.5">
                        @if(!$store->is_warehouse && $store->is_active && auth()->user()->canAccessModule('pos'))
                            <a href="{{ route('pos.index') }}" 
                               class="inline-flex items-center gap-1 py-1.5 px-2.5 rounded-lg text-xs font-semibold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 dark:bg-emerald-950/40 dark:text-emerald-300 dark:hover:bg-emerald-900/60 transition"
                               title="Launch POS">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                </svg>
                                <span class="hidden sm:inline">POS</span>
                            </a>
                        @endif

                        @if(auth()->user()->isSuperAdmin())
                            <a href="{{ route('stores.edit', $store) }}" 
                               class="inline-flex items-center gap-1 py-1.5 px-2.5 rounded-lg text-xs font-semibold text-slate-700 dark:text-slate-300 bg-white dark:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-700 transition"
                               title="Edit Store Configuration">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                                <span>Edit</span>
                            </a>
                        @endif
                    </div>
                </div>

            </div>
        @empty
            <div class="col-span-1 md:col-span-2 lg:col-span-3 bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-12 text-center">
                <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                </div>
                <h3 class="text-base font-bold text-slate-800 dark:text-white">No Stores Found</h3>
                <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">
                    We couldn't find any retail stores or warehouses matching your current search and filter criteria.
                </p>
                <div class="mt-4 flex items-center justify-center gap-2">
                    <a href="{{ route('stores.index') }}" 
                       class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 text-slate-700 dark:text-slate-200 transition">
                        <span>Clear Filters</span>
                    </a>
                    @if(auth()->user()->isSuperAdmin())
                        <a href="{{ route('stores.create') }}" 
                           class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold bg-indigo-600 hover:bg-indigo-700 text-white transition shadow-sm">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            <span>Add New Store</span>
                        </a>
                    @endif
                </div>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if($stores->hasPages())
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-4 shadow-2xs">
            {{ $stores->links() }}
        </div>
    @endif

</div>
@endsection
