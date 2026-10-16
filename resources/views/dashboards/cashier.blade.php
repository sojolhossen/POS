@extends('layouts.app')

@section('title', 'Cashier Terminal Dashboard')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">Cashier POS Terminal Hub</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">Cash drawer control, today's sales summary, and quick access to checkout.</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('pos.index') }}" class="inline-flex items-center px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-sm rounded-lg shadow-md transition">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                Launch POS Checkout Screen
            </a>
        </div>
    </div>

    <!-- Active Register Session Status -->
    <div class="bg-white dark:bg-slate-900 p-6 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="space-y-1">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Cash Drawer Session</span>
            @if($activeSession)
                <div class="flex items-center gap-3">
                    <div class="text-2xl font-bold font-mono text-emerald-600 dark:text-emerald-400">
                        ৳{{ number_format($activeSession->cash_in_hand_expected, 2) }}
                    </div>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-300">
                        Active & Open
                    </span>
                </div>
                <p class="text-xs text-slate-400">Opened at {{ \Carbon\Carbon::parse($activeSession->opened_at)->format('h:i A') }} with ৳{{ number_format($activeSession->opening_cash ?? $activeSession->opening_amount ?? 0, 2) }} opening float.</p>
            @else
                <div class="text-xl font-bold text-amber-600">Register Closed / Not Initialized</div>
                <p class="text-xs text-slate-400">Launch POS to enter opening cash float before ringing up sales.</p>
            @endif
        </div>
        <div class="flex items-center gap-3">
            @if($activeSession)
                <form action="{{ route('daily-registers.close', $activeSession) }}" method="POST" onsubmit="return confirm('Initiate end-of-day register closing and physical cash count?')">
                    @csrf
                    <input type="hidden" name="actual_cash_counted" value="{{ $activeSession->cash_in_hand_expected }}">
                    <button type="submit" class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white font-medium text-xs rounded-lg shadow-sm">
                        Close Register for Today
                    </button>
                </form>
            @endif
        </div>
    </div>

    <!-- Shift Totals -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="bg-white dark:bg-slate-900 p-5 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm">
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Today's Invoices</span>
            <div class="text-3xl font-bold font-mono text-slate-900 dark:text-white mt-1">{{ $todayOrdersCount }} Orders</div>
            <span class="text-xs text-slate-400">Completed POS sales</span>
        </div>
        <div class="bg-white dark:bg-slate-900 p-5 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm">
            <span class="text-xs font-semibold text-emerald-600 uppercase tracking-wider">Today's Total Sales</span>
            <div class="text-3xl font-bold font-mono text-emerald-600 dark:text-emerald-400 mt-1">৳{{ number_format($todaySalesTotal, 2) }}</div>
            <span class="text-xs text-slate-400">Gross receipt revenue</span>
        </div>
    </div>

    <!-- Recent Shift Invoices -->
    <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden p-6 space-y-4">
        <h3 class="text-base font-bold text-slate-900 dark:text-white">Recent Shift Invoices</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600 dark:text-slate-300">
                <thead class="bg-slate-50 dark:bg-slate-800/60 text-xs uppercase font-semibold text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
                    <tr>
                        <th class="px-4 py-2.5">Invoice #</th>
                        <th class="px-4 py-2.5">Customer</th>
                        <th class="px-4 py-2.5 text-right">Items</th>
                        <th class="px-4 py-2.5 text-right">Total</th>
                        <th class="px-4 py-2.5 text-right">Time</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($recentOrders as $ord)
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/50 transition">
                            <td class="px-4 py-3 font-mono font-bold text-slate-900 dark:text-white">{{ $ord->order_number }}</td>
                            <td class="px-4 py-3 text-xs">{{ $ord->customer->name ?? 'Walk-in Guest' }}</td>
                            <td class="px-4 py-3 text-right font-mono text-xs">{{ $ord->items_count ?? $ord->orderItems->count() }}</td>
                            <td class="px-4 py-3 text-right font-mono font-bold text-emerald-600">৳{{ number_format($ord->total_amount, 2) }}</td>
                            <td class="px-4 py-3 text-right text-xs text-slate-400">{{ \Carbon\Carbon::parse($ord->created_at)->format('h:i A') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-slate-400 text-xs">No orders recorded in this shift yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
