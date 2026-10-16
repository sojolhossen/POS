@extends('layouts.app')

@section('title', __('messages.suppliers') . ' - Management')
@section('header', __('messages.suppliers'))

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-black text-slate-900 dark:text-white flex items-center gap-2">
                <span>{{ __('messages.suppliers') }}</span>
                <span class="text-xs px-2.5 py-0.5 rounded-full bg-indigo-100 text-indigo-700 dark:bg-indigo-900/60 dark:text-indigo-300 font-bold">
                    Super Admin Only
                </span>
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                Manage garment manufacturers, vendor profiles, contact information, and liability ledgers.
            </p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('suppliers.create') }}" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs shadow-md shadow-indigo-600/20 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span>Add Supplier</span>
            </a>
        </div>
    </div>

    <!-- Filters & Search -->
    <div class="bg-white dark:bg-slate-900 rounded-xl p-4 border border-slate-200 dark:border-slate-800 shadow-sm">
        <form method="GET" action="{{ route('suppliers.index') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search supplier by name, phone, code..."
                       class="w-full text-xs rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white px-3 py-2">
            </div>
            <div>
                <select name="status" class="w-full text-xs rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white px-3 py-2">
                    <option value="">All Statuses</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div class="flex items-center gap-2">
                <button type="submit" class="px-4 py-2 rounded-lg bg-slate-800 dark:bg-slate-700 text-white text-xs font-semibold hover:bg-slate-900 transition">
                    Filter
                </button>
                <a href="{{ route('suppliers.index') }}" class="px-3 py-2 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 text-xs hover:bg-slate-200 transition">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Suppliers Table -->
    <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-800/60 border-b border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-300 font-bold">
                        <th class="p-3">Code</th>
                        <th class="p-3">Supplier Name</th>
                        <th class="p-3">Contact Person & Phone</th>
                        <th class="p-3">City / Address</th>
                        <th class="p-3 text-right">Total Purchases</th>
                        <th class="p-3 text-right">Outstanding Balance</th>
                        <th class="p-3 text-center">Status</th>
                        <th class="p-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($suppliers as $supplier)
                        <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition">
                            <td class="p-3 font-mono font-bold text-indigo-600 dark:text-indigo-400">
                                {{ $supplier->code }}
                            </td>
                            <td class="p-3 font-semibold text-slate-900 dark:text-white">
                                <a href="{{ route('suppliers.show', $supplier) }}" class="hover:underline">
                                    {{ $supplier->name }}
                                </a>
                            </td>
                            <td class="p-3 text-slate-600 dark:text-slate-300">
                                <div>{{ $supplier->contact_person ?? '—' }}</div>
                                <div class="font-mono text-[11px] text-slate-400">{{ $supplier->phone }}</div>
                            </td>
                            <td class="p-3 text-slate-500 dark:text-slate-400">
                                {{ $supplier->city ?? $supplier->address ?? '—' }}
                            </td>
                            <td class="p-3 text-right font-mono font-medium text-slate-800 dark:text-slate-200">
                                ৳{{ number_format($supplier->total_purchases, 2) }}
                            </td>
                            <td class="p-3 text-right font-mono font-bold {{ $supplier->outstanding_balance > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-emerald-600 dark:text-emerald-400' }}">
                                ৳{{ number_format($supplier->outstanding_balance, 2) }}
                            </td>
                            <td class="p-3 text-center">
                                @if($supplier->is_active)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300">
                                        Active
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-300">
                                        Inactive
                                    </span>
                                @endif
                            </td>
                            <td class="p-3 text-right space-x-1">
                                <a href="{{ route('suppliers.show', $supplier) }}" class="px-2.5 py-1 rounded bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-semibold text-[11px] transition">
                                    View
                                </a>
                                <a href="{{ route('suppliers.edit', $supplier) }}" class="px-2.5 py-1 rounded bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-950/60 dark:hover:bg-indigo-900/80 text-indigo-700 dark:text-indigo-300 font-semibold text-[11px] transition">
                                    Edit
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="p-6 text-center text-slate-400 dark:text-slate-500">
                                No suppliers found. Click "Add Supplier" to create the first garment vendor.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($suppliers->hasPages())
            <div class="p-3 border-t border-slate-200 dark:border-slate-800">
                {{ $suppliers->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
