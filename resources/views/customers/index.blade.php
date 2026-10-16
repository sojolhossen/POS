@extends('layouts.app')

@section('title', __('messages.customers') . ' & Loyalty')
@section('header', __('messages.customers') . ' & Loyalty')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-black text-slate-900 dark:text-white flex items-center gap-2">
                <span>{{ __('messages.customers') }} & Loyalty Points</span>
                <span class="text-xs px-2.5 py-0.5 rounded-full bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 font-bold">
                    1 pt = ৳1
                </span>
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                Customer directory, purchase history, and auditable loyalty point ledgers (earned on sales, reversed on returns).
            </p>
        </div>
    </div>

    <!-- Search Form -->
    <div class="bg-white dark:bg-slate-900 rounded-xl p-4 border border-slate-200 dark:border-slate-800 shadow-sm">
        <form method="GET" action="{{ route('customers.index') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div class="sm:col-span-2">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search customer by mobile phone number or name..."
                       class="w-full text-xs rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white px-3 py-2 font-mono">
            </div>
            <div class="flex items-center gap-2">
                <button type="submit" class="px-4 py-2 rounded-lg bg-slate-800 dark:bg-slate-700 text-white text-xs font-semibold hover:bg-slate-900 transition">
                    Search
                </button>
                <a href="{{ route('customers.index') }}" class="px-3 py-2 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 text-xs hover:bg-slate-200 transition">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Customer Directory Table -->
    <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-800/60 border-b border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-300 font-bold">
                        <th class="p-3">Customer Name</th>
                        <th class="p-3">Mobile Phone</th>
                        <th class="p-3 text-right">Invoices</th>
                        <th class="p-3 text-right">Total Lifetime Spend</th>
                        <th class="p-3 text-right">Loyalty Points Balance</th>
                        <th class="p-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($customers as $cust)
                        <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition">
                            <td class="p-3 font-semibold text-slate-900 dark:text-white">
                                <a href="{{ route('customers.show', $cust) }}" class="hover:underline">
                                    {{ $cust->name }}
                                </a>
                            </td>
                            <td class="p-3 font-mono font-bold text-indigo-600 dark:text-indigo-400">
                                {{ $cust->phone }}
                            </td>
                            <td class="p-3 text-right font-mono text-slate-600 dark:text-slate-300">
                                {{ $cust->sales_count }} sales
                            </td>
                            <td class="p-3 text-right font-mono font-bold text-slate-800 dark:text-slate-200">
                                ৳{{ number_format($cust->total_spent, 2) }}
                            </td>
                            <td class="p-3 text-right font-mono font-black text-emerald-600 dark:text-emerald-400">
                                {{ $cust->loyalty_points }} pts (৳{{ $cust->loyalty_points }})
                            </td>
                            <td class="p-3 text-right">
                                <a href="{{ route('customers.show', $cust) }}" class="px-2.5 py-1 rounded bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-[11px] font-semibold">
                                    View Ledger
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-6 text-center text-slate-400">
                                No customer profiles registered yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($customers->hasPages())
            <div class="p-3 border-t border-slate-200 dark:border-slate-800">
                {{ $customers->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
