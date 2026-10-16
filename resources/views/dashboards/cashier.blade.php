@extends('layouts.app')

@section('title', 'Cashier Terminal')

@section('content')
<div class="space-y-4">
    <!-- Header (Cloudflare Style) -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-slate-200 dark:border-slate-800 pb-2">
        <div class="flex items-center gap-2">
            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
            <h1 class="text-base font-bold text-slate-900 dark:text-white tracking-tight">Cashier POS Terminal Hub</h1>
            <span class="text-[10px] font-mono px-2 py-0.5 rounded border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-600 dark:text-slate-300">
                Shift Status: {{ $activeSession ? 'Active' : 'Closed' }}
            </span>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('pos.index') }}" class="inline-flex items-center gap-1.5 px-4 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs rounded-md shadow-2xs transition">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                <span>Launch POS Terminal</span>
            </a>
        </div>
    </div>

    <!-- Active Register Session Status (Cloudflare Card) -->
    <div class="p-3.5 rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-[#0f172a] shadow-2xs flex flex-col md:flex-row md:items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-lg bg-emerald-50 dark:bg-emerald-950/50 border border-emerald-200 dark:border-emerald-800 text-emerald-600 dark:text-emerald-400 flex items-center justify-center font-bold">
                ৳
            </div>
            <div>
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Cash Drawer Session</span>
                @if($activeSession)
                    <div class="flex items-center gap-2 mt-0.5">
                        <span class="text-xl font-bold font-mono text-emerald-600 dark:text-emerald-400">
                            ৳{{ number_format($activeSession->cash_in_hand_expected, 2) }}
                        </span>
                        <span class="px-2 py-0.2 rounded-full text-[10px] font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-300">
                            Active &amp; Open
                        </span>
                    </div>
                    <p class="text-[11px] text-slate-500">Opened at {{ \Carbon\Carbon::parse($activeSession->opened_at)->format('h:i A') }} with ৳{{ number_format($activeSession->opening_cash ?? $activeSession->opening_amount ?? 0, 2) }} opening float.</p>
                @else
                    <div class="text-sm font-bold text-amber-600 mt-0.5">Register Closed / Not Initialized</div>
                    <p class="text-[11px] text-slate-500">Launch POS to enter opening cash float before ringing up sales.</p>
                @endif
            </div>
        </div>
        <div class="flex items-center gap-2">
            @if($activeSession)
                <form action="{{ route('daily-registers.close', $activeSession) }}" method="POST" onsubmit="return confirm('Initiate end-of-day register closing and physical cash count?')">
                    @csrf
                    <input type="hidden" name="actual_cash_counted" value="{{ $activeSession->cash_in_hand_expected }}">
                    <button type="submit" class="px-3 py-1 bg-rose-600 hover:bg-rose-700 text-white font-medium text-xs rounded-md shadow-2xs transition">
                        Close Register for Today
                    </button>
                </form>
            @endif
        </div>
    </div>

    <!-- Shift Totals (Cloudflare Stat Cards) -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        <div class="p-3.5 rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-[#0f172a] shadow-2xs">
            <span class="text-[11px] font-medium text-slate-500 dark:text-slate-400 block">Today's Invoices</span>
            <div class="flex items-baseline gap-2 mt-1">
                <span class="text-xl font-bold font-sans tracking-tight text-slate-900 dark:text-white">{{ $todayOrdersCount }} Orders</span>
                <span class="text-xs font-semibold text-emerald-600 dark:text-emerald-400">&nearr; Active</span>
            </div>
            <span class="text-[10px] text-slate-400 block mt-1">Completed POS transactions</span>
        </div>
        <div class="p-3.5 rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-[#0f172a] shadow-2xs">
            <span class="text-[11px] font-medium text-slate-500 dark:text-slate-400 block">Today's Total Sales</span>
            <div class="flex items-baseline gap-2 mt-1">
                <span class="text-xl font-bold font-sans tracking-tight text-emerald-600 dark:text-emerald-400">৳{{ number_format($todaySalesTotal, 2) }}</span>
                <span class="text-xs font-semibold text-emerald-600 dark:text-emerald-400">&nearr; Net Collected</span>
            </div>
            <span class="text-[10px] text-slate-400 block mt-1">Cash, Mobile Money &amp; Card</span>
        </div>
    </div>

    <!-- Recent Shift Invoices (Clean Cloudflare Table) -->
    <div class="rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-[#0f172a] shadow-2xs p-4 space-y-3">
        <div class="flex items-center justify-between pb-2 border-b border-slate-100 dark:border-slate-800">
            <h3 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider">Recent Shift Orders</h3>
            <a href="{{ route('returns.index') }}" class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline">Lookup / Return &rarr;</a>
        </div>
        <div class="divide-y divide-slate-100 dark:divide-slate-800 text-xs">
            @forelse($recentOrders as $order)
                <div class="py-2.5 flex items-center justify-between">
                    <div>
                        <div class="font-bold font-mono text-slate-900 dark:text-white">{{ $order->invoice_number }}</div>
                        <div class="text-[10px] text-slate-400">Customer: {{ $order->customer_name ?? 'Walk-in' }} &bull; {{ $order->created_at->format('h:i A') }}</div>
                    </div>
                    <div class="text-right">
                        <div class="font-mono font-bold text-slate-900 dark:text-white">৳{{ number_format($order->total_payable, 2) }}</div>
                        <span class="px-1.5 py-0.2 rounded text-[9px] font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-300">Paid</span>
                    </div>
                </div>
            @empty
                <div class="py-8 text-center text-slate-400 text-xs">No orders processed yet during this shift.</div>
            @endforelse
        </div>
    </div>
</div>
@endsection
