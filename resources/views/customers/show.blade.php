@extends('layouts.app')

@section('title', 'Customer Ledger - ' . $customer->name)
@section('header', 'Customer Profile & Loyalty Ledger')

@section('content')
<div class="space-y-6">
    <div class="bg-white dark:bg-slate-900 rounded-2xl p-6 border border-slate-200 dark:border-slate-800 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="font-mono text-xs font-bold px-2.5 py-0.5 rounded bg-indigo-50 dark:bg-indigo-950/70 text-indigo-700 dark:text-indigo-300">
                    {{ $customer->phone }}
                </span>
            </div>
            <h1 class="text-2xl font-black text-slate-900 dark:text-white">{{ $customer->name }}</h1>
            <p class="text-xs text-slate-500 mt-1">
                Registered on {{ $customer->created_at->format('d M Y') }} • Email: {{ $customer->email ?? 'Not Provided' }}
            </p>
        </div>

        <a href="{{ route('customers.index') }}" class="px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 text-slate-600 dark:text-slate-300 text-xs font-semibold">
            ← Back to Directory
        </a>
    </div>

    <!-- Metrics Summary -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white dark:bg-slate-900 rounded-xl p-5 border border-slate-200 dark:border-slate-800 shadow-sm">
            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Total Lifetime Spend</span>
            <div class="text-2xl font-black text-slate-900 dark:text-white mt-1 font-mono">
                ৳{{ number_format($customer->total_spent, 2) }}
            </div>
        </div>

        <div class="bg-white dark:bg-slate-900 rounded-xl p-5 border border-slate-200 dark:border-slate-800 shadow-sm">
            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Active Loyalty Points</span>
            <div class="text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-1 font-mono">
                {{ $customer->loyalty_points }} pts
            </div>
            <p class="text-[11px] text-slate-400 mt-0.5">Worth ৳{{ $customer->loyalty_points }} at POS checkout</p>
        </div>

        <div class="bg-white dark:bg-slate-900 rounded-xl p-5 border border-slate-200 dark:border-slate-800 shadow-sm">
            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Total Purchase Visits</span>
            <div class="text-2xl font-black text-indigo-600 dark:text-indigo-400 mt-1 font-mono">
                {{ $customer->sales->count() }} Invoices
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Sales Invoices -->
        <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
            <div class="p-4 border-b border-slate-100 dark:border-slate-800">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-200">
                    Recent Purchase Invoices
                </h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="bg-slate-50 dark:bg-slate-800/60 text-slate-500 font-bold border-b border-slate-100 dark:border-slate-800">
                            <th class="p-2.5">Invoice #</th>
                            <th class="p-2.5">Date</th>
                            <th class="p-2.5 text-right">Amount</th>
                            <th class="p-2.5 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse($customer->sales as $sale)
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/40">
                                <td class="p-2.5 font-mono font-bold text-indigo-600 dark:text-indigo-400">
                                    {{ $sale->invoice_number }}
                                </td>
                                <td class="p-2.5 text-slate-500">{{ $sale->created_at->format('d M Y') }}</td>
                                <td class="p-2.5 text-right font-mono font-bold text-slate-900 dark:text-white">
                                    ৳{{ number_format($sale->total_amount, 2) }}
                                </td>
                                <td class="p-2.5 text-right">
                                    <a href="{{ route('pos.receipt', $sale) }}" class="text-[11px] text-indigo-600 hover:underline">
                                        Receipt →
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="p-4 text-center text-slate-400 text-xs">No purchase transactions found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Loyalty Points Ledger History -->
        <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
            <div class="p-4 border-b border-slate-100 dark:border-slate-800">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-200">
                    Loyalty Points Audit Ledger
                </h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs font-mono">
                    <thead>
                        <tr class="bg-slate-50 dark:bg-slate-800/60 text-slate-500 font-bold border-b border-slate-100 dark:border-slate-800 font-sans">
                            <th class="p-2.5">Date</th>
                            <th class="p-2.5">Type</th>
                            <th class="p-2.5">Description</th>
                            <th class="p-2.5 text-right">Points</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse($customer->pointLedgers as $entry)
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/40">
                                <td class="p-2.5 text-slate-500">{{ $entry->created_at->format('d M Y') }}</td>
                                <td class="p-2.5 uppercase text-[10px] font-sans font-bold
                                    {{ $entry->entry_type === 'earned' ? 'text-emerald-600' : ($entry->entry_type === 'redeemed' ? 'text-indigo-600' : 'text-rose-600') }}">
                                    {{ $entry->entry_type }}
                                </td>
                                <td class="p-2.5 font-sans text-slate-600 dark:text-slate-300 text-[11px]">
                                    {{ $entry->description ?? 'POS Checkout' }}
                                </td>
                                <td class="p-2.5 text-right font-bold {{ $entry->points > 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                                    {{ $entry->points > 0 ? '+' : '' }}{{ $entry->points }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="p-4 text-center text-slate-400 text-xs font-sans">No loyalty ledger activity recorded.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
