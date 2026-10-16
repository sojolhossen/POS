@extends('layouts.app')

@section('title', __('messages.stock_movements'))
@section('header', __('messages.stock_movements'))

@section('content')
<div class="space-y-6">

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold text-slate-900 dark:text-white">{{ __('messages.stock_movements') }}</h2>
            <p class="text-xs text-slate-500 mt-0.5">Immutable Double-Entry Stock Movement Ledger</p>
        </div>

        <a href="{{ route('inventory.index') }}" class="py-2 px-4 rounded-xl font-bold text-xs bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200">
            ← Back to Stock Balances
        </a>
    </div>

    <!-- Ledger Filter & Table -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm overflow-hidden">
        
        <div class="p-4 border-b border-slate-100 dark:border-slate-800">
            <form method="GET" action="{{ route('inventory.ledger') }}" class="flex flex-wrap items-center gap-3">
                @if(auth()->user()->isSuperAdmin())
                    <div>
                        <select name="store_id" onchange="this.form.submit()" class="text-xs rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 py-2 px-3">
                            <option value="">All Locations</option>
                            @foreach($stores as $st)
                                <option value="{{ $st->id }}" {{ request('store_id') == $st->id ? 'selected' : '' }}>
                                    {{ $st->name }} ({{ $st->code }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <div>
                    <select name="transaction_type" onchange="this.form.submit()" class="text-xs rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 py-2 px-3">
                        <option value="">All Transaction Types</option>
                        @foreach(\App\Enums\StockTransactionType::cases() as $tx)
                            <option value="{{ $tx->value }}" {{ request('transaction_type') === $tx->value ? 'selected' : '' }}>
                                {{ $tx->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="flex-1 min-w-[200px]">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search reference #, barcode, style..."
                           class="w-full text-xs rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 py-2 px-3">
                </div>

                <button type="submit" class="py-2 px-3.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-xs font-semibold hover:bg-slate-200">
                    Filter
                </button>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-slate-200 dark:border-slate-800 text-slate-400 uppercase text-[10px] tracking-wider font-bold">
                        <th class="py-3.5 px-4">Date & Time</th>
                        <th class="py-3.5 px-4">Type</th>
                        <th class="py-3.5 px-4">Store Location</th>
                        <th class="py-3.5 px-4">Product Style & Variant</th>
                        <th class="py-3.5 px-4">Reference #</th>
                        <th class="py-3.5 px-4 text-right">Qty Delta</th>
                        <th class="py-3.5 px-4 text-right">Balance After</th>
                        <th class="py-3.5 px-4">Actor</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800 font-medium">
                    @forelse($ledgers as $ledger)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition">
                            <td class="py-3.5 px-4 font-mono text-[11px] text-slate-500 whitespace-nowrap">
                                @posDate($ledger->created_at) {{ $ledger->created_at->format('H:i') }}
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-bold uppercase
                                    {{ $ledger->quantity > 0 ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300' : 'bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300' }}">
                                    {{ $ledger->transaction_type->label() }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 font-bold text-slate-900 dark:text-white">
                                {{ $ledger->store->name }}
                            </td>
                            <td class="py-3.5 px-4">
                                <div class="font-bold text-slate-900 dark:text-white">{{ $ledger->variant->product->name }}</div>
                                <div class="text-[11px] text-slate-500 font-mono">
                                    {{ $ledger->variant->color }} - {{ $ledger->variant->size }} • {{ $ledger->variant->barcode }}
                                </div>
                            </td>
                            <td class="py-3.5 px-4 font-mono font-bold text-slate-700 dark:text-slate-300">
                                {{ $ledger->reference_number ?? '—' }}
                            </td>
                            <td class="py-3.5 px-4 text-right font-black font-mono text-sm {{ $ledger->quantity > 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                                {{ $ledger->quantity > 0 ? '+' . $ledger->quantity : $ledger->quantity }}
                            </td>
                            <td class="py-3.5 px-4 text-right font-black font-mono text-sm text-slate-900 dark:text-white">
                                {{ $ledger->balance_after }}
                            </td>
                            <td class="py-3.5 px-4 text-slate-500">
                                {{ $ledger->user ? $ledger->user->name : 'System' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-8 text-center text-slate-400">No stock movements found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t border-slate-100 dark:border-slate-800">
            {{ $ledgers->links() }}
        </div>
    </div>

</div>
@endsection
