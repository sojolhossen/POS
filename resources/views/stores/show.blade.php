@extends('layouts.app')

@section('title', $store->name)
@section('header', $store->name)

@section('content')
<div class="space-y-6">

    <div class="flex items-center justify-between">
        <a href="{{ route('stores.index') }}" class="text-xs font-bold text-slate-500 hover:text-indigo-600 flex items-center gap-1">
            ← Back to Stores
        </a>
        @if(auth()->user()->isSuperAdmin())
            <a href="{{ route('stores.edit', $store) }}" class="py-2 px-4 rounded-xl bg-slate-100 dark:bg-slate-800 text-xs font-bold text-slate-700 dark:text-slate-300 hover:bg-slate-200">
                Edit Store
            </a>
        @endif
    </div>

    <!-- Store Info Card -->
    <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 sm:p-8 border border-slate-200/80 dark:border-slate-800 shadow-sm">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-6 border-b border-slate-100 dark:border-slate-800">
            <div>
                <div class="flex items-center gap-2">
                    <h2 class="text-2xl font-black text-slate-900 dark:text-white">{{ $store->name }}</h2>
                    @if($store->is_warehouse)
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-extrabold bg-purple-100 text-purple-700 dark:bg-purple-900/50 dark:text-purple-300">
                            WAREHOUSE
                        </span>
                    @else
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-extrabold bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300">
                            RETAIL OUTLET
                        </span>
                    @endif
                </div>
                <div class="text-xs text-slate-500 mt-1 font-mono">Code: {{ $store->code }} • {{ $store->phone ?? 'No phone' }}</div>
            </div>

            <div class="flex items-center gap-2">
                @if($store->is_active)
                    <span class="px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300">Active</span>
                @else
                    <span class="px-3 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400">Inactive</span>
                @endif
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 pt-6 text-xs">
            <div>
                <div class="text-slate-400 font-bold uppercase text-[10px] tracking-wider mb-1">Physical Address</div>
                <div class="font-medium text-slate-800 dark:text-slate-200 leading-relaxed">{{ $store->address ?? 'Not specified' }}</div>
            </div>
            <div>
                <div class="text-slate-400 font-bold uppercase text-[10px] tracking-wider mb-1">Assigned Manager</div>
                <div class="font-bold text-slate-900 dark:text-white">{{ $store->manager ? $store->manager->name : 'Unassigned' }}</div>
                <div class="text-slate-500">{{ $store->manager?->email }}</div>
            </div>
            <div>
                <div class="text-slate-400 font-bold uppercase text-[10px] tracking-wider mb-1">Operating Hours</div>
                <div class="font-mono text-slate-800 dark:text-slate-200 font-semibold">
                    {{ $store->opening_time ? substr($store->opening_time, 0, 5) . ' - ' . substr($store->closing_time, 0, 5) : 'Standard 10:00 - 22:00' }}
                </div>
            </div>
        </div>
    </div>

    <!-- Store Live Inventory -->
    <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 sm:p-8 border border-slate-200/80 dark:border-slate-800 shadow-sm">
        <h3 class="text-base font-bold text-slate-900 dark:text-white mb-4">Stock on Hand at {{ $store->name }}</h3>
        
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-slate-200 dark:border-slate-800 text-slate-400 uppercase text-[10px] tracking-wider font-bold">
                        <th class="py-3 px-3">Product Name</th>
                        <th class="py-3 px-3">SKU</th>
                        <th class="py-3 px-3">Variant (Size / Color)</th>
                        <th class="py-3 px-3">Barcode</th>
                        <th class="py-3 px-3 text-right">Selling Price</th>
                        <th class="py-3 px-3 text-right">On Hand</th>
                        <th class="py-3 px-3 text-right">Available</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800 font-medium">
                    @forelse($stockBalances as $balance)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                            <td class="py-3 px-3 font-bold text-slate-900 dark:text-white">
                                {{ $balance->variant->product->name }}
                            </td>
                            <td class="py-3 px-3 font-mono text-slate-500">
                                {{ $balance->variant->product->sku }}
                            </td>
                            <td class="py-3 px-3">
                                {{ $balance->variant->color }} - <strong>{{ $balance->variant->size }}</strong>
                            </td>
                            <td class="py-3 px-3 font-mono font-bold">
                                {{ $balance->variant->barcode }}
                            </td>
                            <td class="py-3 px-3 text-right font-bold text-indigo-600">
                                @bdt($balance->variant->effective_selling_price)
                            </td>
                            <td class="py-3 px-3 text-right font-black text-slate-900 dark:text-white">
                                {{ $balance->quantity_on_hand }}
                            </td>
                            <td class="py-3 px-3 text-right font-black text-emerald-600">
                                {{ $balance->available_quantity }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-slate-400">No stock recorded at this location yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $stockBalances->links() }}
        </div>
    </div>

</div>
@endsection
