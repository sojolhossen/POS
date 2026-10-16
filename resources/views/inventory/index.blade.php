@extends('layouts.app')

@section('title', __('messages.inventory'))
@section('header', __('messages.inventory'))

@section('content')
<div class="space-y-6">

    <!-- Header Actions -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold text-slate-900 dark:text-white">{{ __('messages.inventory') }}</h2>
            <p class="text-xs text-slate-500 mt-0.5">Live Store Stock Balances with Strict Concurrency Protection</p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('inventory.ledger') }}" class="py-2.5 px-4 rounded-xl font-bold text-xs bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200">
                Movement Ledger →
            </a>
            <a href="{{ route('stock-adjustments.create') }}" class="py-2.5 px-4 rounded-xl font-bold text-xs bg-amber-500 hover:bg-amber-600 text-white shadow-md shadow-amber-500/20">
                Propose Stock Adjustment
            </a>
            @if(auth()->user()->isSuperAdmin() || auth()->user()->isWarehouseStaff())
                <a href="{{ route('inventory.warehouse.create') }}" class="py-2.5 px-4 rounded-xl font-bold text-xs text-white bg-indigo-600 hover:bg-indigo-700 shadow-md shadow-indigo-600/30">
                    + Warehouse Intake
                </a>
            @endif
        </div>
    </div>

    <!-- Filter & Store Scoping Card -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm overflow-hidden">
        
        <div class="p-4 border-b border-slate-100 dark:border-slate-800 flex flex-wrap items-center justify-between gap-3">
            <form method="GET" action="{{ route('inventory.index') }}" class="flex flex-wrap items-center gap-3 w-full sm:w-auto">
                @if(auth()->user()->isSuperAdmin() || auth()->user()->isWarehouseStaff())
                    <div>
                        <select name="store_id" onchange="this.form.submit()" class="text-xs rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 py-2 px-3 font-bold text-slate-800 dark:text-slate-200">
                            @foreach($stores as $st)
                                <option value="{{ $st->id }}" {{ $selectedStoreId == $st->id ? 'selected' : '' }}>
                                    {{ $st->name }} ({{ $st->code }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                @else
                    <div class="text-xs font-bold px-3 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                        Location: {{ $selectedStore?->name }}
                    </div>
                @endif

                <div class="flex-1 min-w-[200px]">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search style name, SKU, barcode..."
                           class="w-full text-xs rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 py-2 px-3">
                </div>

                <label class="flex items-center gap-1.5 text-xs text-slate-600 dark:text-slate-400 cursor-pointer">
                    <input type="checkbox" name="low_stock" value="1" {{ request('low_stock') ? 'checked' : '' }} onchange="this.form.submit()" class="rounded text-rose-500">
                    <span>Low Stock Only</span>
                </label>

                <button type="submit" class="py-2 px-3.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-xs font-semibold hover:bg-slate-200">
                    Filter
                </button>
            </form>
        </div>

        <!-- Inventory Balances Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-slate-200 dark:border-slate-800 text-slate-400 uppercase text-[10px] tracking-wider font-bold">
                        <th class="py-3.5 px-4">Product Name & Category</th>
                        <th class="py-3.5 px-4">SKU Code</th>
                        <th class="py-3.5 px-4">Variant (Color / Size)</th>
                        <th class="py-3.5 px-4">Barcode (Code 128)</th>
                        <th class="py-3.5 px-4 text-right">Selling MRP</th>
                        <th class="py-3.5 px-4 text-right">Quantity On Hand</th>
                        <th class="py-3.5 px-4 text-right">Available Stock</th>
                        <th class="py-3.5 px-4 text-center">Stock Health</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800 font-medium">
                    @forelse($balances as $balance)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition">
                            <td class="py-3.5 px-4 font-bold text-slate-900 dark:text-white">
                                {{ $balance->variant->product->name }}
                                <div class="text-[11px] font-normal text-slate-400">{{ $balance->variant->product->category->name }}</div>
                            </td>
                            <td class="py-3.5 px-4 font-mono font-bold text-indigo-600">
                                {{ $balance->variant->product->sku }}
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-800 font-semibold">{{ $balance->variant->color }}</span>
                                <span class="px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-800 font-bold text-indigo-600">{{ $balance->variant->size }}</span>
                            </td>
                            <td class="py-3.5 px-4 font-mono font-bold text-slate-900 dark:text-white">
                                {{ $balance->variant->barcode }}
                            </td>
                            <td class="py-3.5 px-4 text-right font-black text-indigo-600 dark:text-indigo-400">
                                @bdt($balance->variant->effective_selling_price)
                            </td>
                            <td class="py-3.5 px-4 text-right font-black text-slate-900 dark:text-white text-sm">
                                {{ $balance->quantity_on_hand }}
                            </td>
                            <td class="py-3.5 px-4 text-right font-black text-emerald-600 text-sm">
                                {{ $balance->available_quantity }}
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                @if($balance->quantity_on_hand <= 0)
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-rose-100 text-rose-700 dark:bg-rose-950 dark:text-rose-300">
                                        OUT OF STOCK
                                    </span>
                                @elseif($balance->quantity_on_hand <= $balance->reorder_level)
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300">
                                        LOW STOCK
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300">
                                        HEALTHY
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-8 text-center text-slate-400">No stock balances found for this location.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t border-slate-100 dark:border-slate-800">
            {{ $balances->links() }}
        </div>
    </div>

</div>
@endsection
