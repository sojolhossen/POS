@extends('layouts.app')

@section('title', __('messages.stores'))
@section('header', __('messages.stores'))

@section('content')
<div class="space-y-6">

    <!-- Header Actions -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold text-slate-900 dark:text-white">{{ __('messages.stores') }}</h2>
            <p class="text-xs text-slate-500 mt-0.5">Centralized Retail Outlet & Warehouse Directory</p>
        </div>

        @if(auth()->user()->isSuperAdmin())
            <div>
                <a href="{{ route('stores.create') }}" 
                   class="inline-flex items-center gap-2 py-2.5 px-4 rounded-xl font-bold text-xs text-white bg-indigo-600 hover:bg-indigo-700 transition shadow-lg shadow-indigo-600/30">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    <span>+ Add New Store / Warehouse</span>
                </a>
            </div>
        @endif
    </div>

    <!-- Stores Table Card -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm overflow-hidden">
        
        <div class="p-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
            <form method="GET" action="{{ route('stores.index') }}" class="flex items-center gap-2 max-w-sm w-full">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by name, code, phone..."
                       class="w-full text-xs rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 py-2 px-3 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                <button type="submit" class="py-2 px-3 rounded-xl bg-slate-100 dark:bg-slate-800 text-xs font-semibold hover:bg-slate-200">
                    Search
                </button>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-slate-200 dark:border-slate-800 text-slate-400 uppercase text-[10px] tracking-wider font-bold">
                        <th class="py-3.5 px-4">Type</th>
                        <th class="py-3.5 px-4">Store Code</th>
                        <th class="py-3.5 px-4">Store Name</th>
                        <th class="py-3.5 px-4">Manager</th>
                        <th class="py-3.5 px-4">Phone / Address</th>
                        <th class="py-3.5 px-4">Hours</th>
                        <th class="py-3.5 px-4">Status</th>
                        <th class="py-3.5 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800 font-medium">
                    @forelse($stores as $store)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition">
                            <td class="py-3.5 px-4">
                                @if($store->is_warehouse)
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-purple-100 text-purple-700 dark:bg-purple-900/50 dark:text-purple-300">
                                        WAREHOUSE
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300">
                                        RETAIL
                                    </span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 font-mono font-bold text-slate-900 dark:text-white">
                                {{ $store->code }}
                            </td>
                            <td class="py-3.5 px-4 font-bold text-slate-900 dark:text-white">
                                {{ $store->name }}
                            </td>
                            <td class="py-3.5 px-4 text-slate-600 dark:text-slate-300">
                                {{ $store->manager ? $store->manager->name : '—' }}
                            </td>
                            <td class="py-3.5 px-4 text-slate-500">
                                <div>{{ $store->phone ?? '—' }}</div>
                                <div class="text-[11px] text-slate-400 truncate max-w-xs">{{ $store->address ?? '—' }}</div>
                            </td>
                            <td class="py-3.5 px-4 text-slate-600 dark:text-slate-300 font-mono text-[11px]">
                                {{ $store->opening_time ? substr($store->opening_time, 0, 5) . ' - ' . substr($store->closing_time, 0, 5) : '—' }}
                            </td>
                            <td class="py-3.5 px-4">
                                @if($store->is_active)
                                    <span class="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-600">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Active
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 text-[11px] font-bold text-slate-400">
                                        <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span> Inactive
                                    </span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('stores.show', $store) }}" class="p-1 text-slate-500 hover:text-indigo-600" title="View Details">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    </a>
                                    @if(auth()->user()->isSuperAdmin())
                                        <a href="{{ route('stores.edit', $store) }}" class="p-1 text-slate-500 hover:text-indigo-600" title="Edit Store">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-8 text-center text-slate-400">No stores found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t border-slate-100 dark:border-slate-800">
            {{ $stores->links() }}
        </div>
    </div>

</div>
@endsection
