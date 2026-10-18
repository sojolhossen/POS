@extends('layouts.app')

@section('title', 'স্টোর ম্যানেজার ড্যাশবোর্ড - ' . ($store->name ?? 'Store Hub'))
@section('header', 'ম্যানেজার ড্যাশবোর্ড (Store Manager)')

@section('content')
<div class="space-y-6">
    <!-- TOP WELCOME BAR & STORE SWITCHER -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm">
        <div class="space-y-1">
            <div class="flex items-center gap-2 flex-wrap">
                <span class="inline-flex items-center justify-center w-8 h-8 rounded-xl bg-indigo-100 text-indigo-700 dark:bg-indigo-950/70 dark:text-indigo-300 font-bold text-sm">
                    🏬
                </span>
                <h1 class="text-lg font-black text-slate-900 dark:text-white tracking-tight">
                    {{ $store->name }}
                </h1>
                <span class="text-xs font-mono font-bold px-2.5 py-0.5 rounded-lg bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                    কোড: {{ $store->code }}
                </span>
            </div>
            <p class="text-xs text-slate-500 dark:text-slate-400">
                👋 স্বাগতম, <strong>{{ auth()->user()->name }}</strong>! আজকের তারিখ: <strong class="text-slate-700 dark:text-slate-200">{{ now()->translatedFormat('l, d F Y') }}</strong>
            </p>
        </div>

        <!-- STORE SWITCHER PILLS (IF MULTIPLE STORES ASSIGNED TO THIS MANAGER) -->
        <div class="flex items-center gap-2 flex-wrap">
            @if(isset($assignedStores) && $assignedStores->count() > 1)
                <div class="flex items-center gap-1.5 bg-slate-100 dark:bg-slate-800/80 p-1.5 rounded-xl border border-slate-200 dark:border-slate-700">
                    <span class="text-[10px] uppercase font-bold text-slate-400 px-2">ব্রাঞ্চ পরিবর্তন:</span>
                    @foreach($assignedStores as $st)
                        <a href="{{ route('dashboard', ['store_id' => $st->id]) }}"
                           class="px-3 py-1.5 rounded-lg text-xs font-bold transition whitespace-nowrap {{ ($store && $store->id === $st->id) ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-600 dark:text-slate-300 hover:bg-white dark:hover:bg-slate-700' }}">
                            {{ $st->name }}
                        </a>
                    @endforeach
                </div>
            @endif

            <!-- POS BUTTON -->
            <a href="{{ route('pos.index') }}"
               class="inline-flex items-center gap-2 px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-md transition whitespace-nowrap">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                <span>🛒 নতুন বিক্রি / POS কাউন্টার</span>
            </a>
        </div>
    </div>

    <!-- DAILY CASH REGISTER STATUS BANNER (VERY CLEAR & PROMINENT) -->
    @if(!$cashRegister || !$cashRegister->isOpen())
        <div class="bg-gradient-to-r from-amber-500 via-orange-500 to-amber-600 rounded-2xl p-5 text-white shadow-md flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex items-start gap-3">
                <span class="w-10 h-10 rounded-xl bg-white/20 backdrop-blur-sm flex items-center justify-center text-xl shrink-0">
                    ⚠️
                </span>
                <div>
                    <h2 class="text-sm font-black uppercase tracking-wider text-amber-100">
                        সতর্কতা: আজকের ক্যাশ রেজিস্টার এখনও খোলা হয়নি!
                    </h2>
                    <p class="text-xs text-white/90 mt-1 max-w-xl">
                        দোকানে ক্যাশ বিক্রি শুরু করার আগে সকালের ক্যাশ ড্রয়ার রেজিস্টার ওপেন করতে হবে।
                        @if($suggestedOpening && $suggestedOpening['has_previous'])
                            পূর্বের দিনের ক্লোজিং ব্যালেন্স: <strong class="font-mono text-amber-100 underline">৳{{ number_format($suggestedOpening['suggested_amount'], 2) }}</strong> (অটোমেটিক জমা থাকবে)।
                        @endif
                    </p>
                </div>
            </div>
            <a href="{{ route('daily-registers.index', ['store_id' => $store->id]) }}"
               class="px-5 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs shadow-lg transition whitespace-nowrap text-center">
                👉 সকালের ক্যাশ রেজিস্টার খুলুন
            </a>
        </div>
    @else
        <div class="bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/80 rounded-2xl p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
            <div class="flex items-center gap-3">
                <span class="w-8 h-8 rounded-xl bg-emerald-600 text-white flex items-center justify-center font-bold text-sm">
                    ✓
                </span>
                <div>
                    <div class="flex items-center gap-2">
                        <span class="font-black text-emerald-900 dark:text-emerald-200">আজকের ক্যাশ রেজিস্টার সক্রিয় ও চালু আছে (Active & Open)</span>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-200 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-300 uppercase">
                            Open
                        </span>
                    </div>
                    <p class="text-slate-500 dark:text-slate-400 mt-0.5">
                        সকালে খুলেছেন: <strong>{{ $cashRegister->opener->name ?? 'Staff' }}</strong> • সময়: {{ $cashRegister->opened_at->format('h:i A') }} • ওপেনিং ক্যাশ: ৳{{ number_format($cashRegister->opening_cash, 2) }}
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('daily-registers.index', ['store_id' => $store->id]) }}"
                   class="px-3.5 py-1.5 rounded-lg bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 font-bold text-xs hover:bg-slate-50 transition">
                    ক্যাশ ড্রয়ারের সম্পূর্ণ হিসাব দেখুন &rarr;
                </a>
            </div>
        </div>
    @endif

    <!-- 4 MAIN METRIC CARDS (EASY BILINGUAL TITLES) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- CARD 1: LIVE CASH IN DRAWER -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl p-5 border border-slate-200 dark:border-slate-800 shadow-sm relative overflow-hidden group hover:border-indigo-300 dark:hover:border-indigo-700 transition">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 dark:text-slate-400">১. ক্যাশ বাক্সে বর্তমান টাকা</span>
                <span class="w-8 h-8 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-bold text-sm">
                    ৳
                </span>
            </div>
            <div class="mt-2">
                <span class="text-2xl font-black font-mono tracking-tight text-indigo-600 dark:text-indigo-400">
                    ৳{{ number_format($cashRegister ? $cashRegister->cash_in_hand_expected : ($suggestedOpening['suggested_amount'] ?? 0), 2) }}
                </span>
            </div>
            <div class="mt-2 pt-2 border-t border-slate-100 dark:border-slate-800 text-[11px] text-slate-500 space-y-0.5">
                <div class="flex justify-between">
                    <span>আজকের বিক্রি জমা:</span>
                    <strong class="text-emerald-600">+৳{{ number_format($cashRegister ? $cashRegister->cash_sales : 0, 2) }}</strong>
                </div>
                <div class="flex justify-between">
                    <span>মালিককে দেওয়া হয়েছে:</span>
                    <strong class="text-rose-600">-৳{{ number_format($todayTransfersTotal, 2) }}</strong>
                </div>
            </div>
            <div class="mt-3">
                <a href="{{ route('daily-registers.index', ['store_id' => $store->id]) }}" class="text-xs font-bold text-indigo-600 dark:text-indigo-400 hover:underline flex items-center gap-1">
                    <span>মালিককে টাকা পাঠান / ক্যাশ ড্রয়ার</span> &rarr;
                </a>
            </div>
        </div>

        <!-- CARD 2: TODAY'S TOTAL SALES -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl p-5 border border-slate-200 dark:border-slate-800 shadow-sm relative overflow-hidden group hover:border-emerald-300 dark:hover:border-emerald-700 transition">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 dark:text-slate-400">২. আজকের মোট বিক্রি</span>
                <span class="w-8 h-8 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center font-bold text-sm">
                    🛒
                </span>
            </div>
            <div class="mt-2">
                <span class="text-2xl font-black font-mono tracking-tight text-emerald-600 dark:text-emerald-400">
                    ৳{{ number_format($todaySalesTotal, 2) }}
                </span>
            </div>
            <div class="mt-2 pt-2 border-t border-slate-100 dark:border-slate-800 text-[11px] text-slate-500 space-y-0.5">
                <div class="flex justify-between">
                    <span>মোট মেমো / অর্ডার:</span>
                    <strong class="text-slate-800 dark:text-slate-200">{{ $todayOrdersCount }} টি</strong>
                </div>
                <div class="flex justify-between">
                    <span>গড় অর্ডার ভ্যালু:</span>
                    <strong class="text-slate-800 dark:text-slate-200 font-mono">৳{{ number_format($avgOrderValue, 2) }}</strong>
                </div>
            </div>
            <div class="mt-3">
                <a href="{{ route('pos.sales.history') }}" class="text-xs font-bold text-emerald-600 dark:text-emerald-400 hover:underline flex items-center gap-1">
                    <span>বিক্রির বিস্তারিত মেমো তালিকা</span> &rarr;
                </a>
            </div>
        </div>

        <!-- CARD 3: TODAY'S EXPENSES -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl p-5 border border-slate-200 dark:border-slate-800 shadow-sm relative overflow-hidden group hover:border-amber-300 dark:hover:border-amber-700 transition">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 dark:text-slate-400">৩. আজকের দোকান খরচ</span>
                <span class="w-8 h-8 rounded-xl bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 flex items-center justify-center font-bold text-sm">
                    💸
                </span>
            </div>
            <div class="mt-2">
                <span class="text-2xl font-black font-mono tracking-tight text-amber-600 dark:text-amber-400">
                    ৳{{ number_format($todayExpensesTotal, 2) }}
                </span>
            </div>
            <div class="mt-2 pt-2 border-t border-slate-100 dark:border-slate-800 text-[11px] text-slate-500 space-y-0.5">
                <div class="flex justify-between">
                    <span>চা-নাস্তা, কনভেয়েন্স ও খুচরা:</span>
                    <strong class="text-slate-700 dark:text-slate-300 font-mono">৳{{ number_format($todayExpensesTotal, 2) }}</strong>
                </div>
                <div class="flex justify-between">
                    <span>চলতি মাসের মোট খরচ:</span>
                    <strong class="text-slate-700 dark:text-slate-300 font-mono">৳{{ number_format($financials['expenses'] ?? 0, 2) }}</strong>
                </div>
            </div>
            <div class="mt-3">
                <a href="{{ route('expenses.create') }}" class="text-xs font-bold text-amber-600 dark:text-amber-400 hover:underline flex items-center gap-1">
                    <span>+ নতুন খরচ লিখুন</span> &rarr;
                </a>
            </div>
        </div>

        <!-- CARD 4: STAFF & STOCK HEALTH -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl p-5 border border-slate-200 dark:border-slate-800 shadow-sm relative overflow-hidden group hover:border-blue-300 dark:hover:border-blue-700 transition">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 dark:text-slate-400">৪. স্টাফ ও স্টক অবস্থা</span>
                <span class="w-8 h-8 rounded-xl bg-blue-50 dark:bg-blue-950/60 text-blue-600 dark:text-blue-400 flex items-center justify-center font-bold text-sm">
                    👥
                </span>
            </div>
            <div class="mt-2">
                <span class="text-xl font-black text-slate-900 dark:text-white">
                    {{ $presentStaffCount }}/{{ $storeEmployeesCount }} জন স্টাফ
                </span>
            </div>
            <div class="mt-2 pt-2 border-t border-slate-100 dark:border-slate-800 text-[11px] text-slate-500 space-y-0.5">
                <div class="flex justify-between">
                    <span>আজকের স্টাফ হাজিরা:</span>
                    <strong class="text-emerald-600">{{ $presentStaffCount }} জন উপস্থিত</strong>
                </div>
                <div class="flex justify-between">
                    <span>স্টক কমে গেছে:</span>
                    <strong class="{{ $lowStockItems->count() > 0 ? 'text-rose-600' : 'text-emerald-600' }}">{{ $lowStockItems->count() }} টি পণ্য</strong>
                </div>
            </div>
            <div class="mt-3">
                <a href="{{ route('attendance.index') }}" class="text-xs font-bold text-blue-600 dark:text-blue-400 hover:underline flex items-center gap-1">
                    <span>স্টাফ হাজিরা খাতা দেখুন</span> &rarr;
                </a>
            </div>
        </div>
    </div>

    <!-- 6 QUICK ACTION TILES (EASY 1-CLICK ACCESS WITH SIMPLE LANGUAGE) -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl p-5 border border-slate-200 dark:border-slate-800 shadow-sm space-y-3">
        <h2 class="text-xs font-black uppercase tracking-wider text-slate-500 dark:text-slate-400 flex items-center gap-2">
            <span>⚡ নিয়মিত কাজের সহজ অপশন সমূহ (Quick Actions)</span>
        </h2>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
            <!-- 1. POS Terminal -->
            <a href="{{ route('pos.index') }}"
               class="p-4 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50/60 dark:bg-slate-800/40 hover:bg-emerald-50 hover:border-emerald-200 dark:hover:bg-emerald-950/30 dark:hover:border-emerald-800 transition group text-center flex flex-col items-center justify-center">
                <span class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-700 dark:bg-emerald-900/60 dark:text-emerald-300 flex items-center justify-center text-lg mb-2 group-hover:scale-110 transition">
                    🛒
                </span>
                <span class="text-xs font-black text-slate-800 dark:text-slate-200 block">নতুন বিক্রি</span>
                <span class="text-[10px] text-slate-400 mt-0.5">POS Billing</span>
            </a>

            <!-- 2. Daily Cash Register -->
            <a href="{{ route('daily-registers.index', ['store_id' => $store->id]) }}"
               class="p-4 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50/60 dark:bg-slate-800/40 hover:bg-indigo-50 hover:border-indigo-200 dark:hover:bg-indigo-950/30 dark:hover:border-indigo-800 transition group text-center flex flex-col items-center justify-center">
                <span class="w-10 h-10 rounded-xl bg-indigo-100 text-indigo-700 dark:bg-indigo-900/60 dark:text-indigo-300 flex items-center justify-center text-lg mb-2 group-hover:scale-110 transition">
                    💵
                </span>
                <span class="text-xs font-black text-slate-800 dark:text-slate-200 block">ক্যাশ ড্রয়ার</span>
                <span class="text-[10px] text-slate-400 mt-0.5">Register & Close</span>
            </a>

            <!-- 3. Remit to Owner -->
            <a href="{{ route('daily-registers.index', ['store_id' => $store->id]) }}"
               class="p-4 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50/60 dark:bg-slate-800/40 hover:bg-blue-50 hover:border-blue-200 dark:hover:bg-blue-950/30 dark:hover:border-blue-800 transition group text-center flex flex-col items-center justify-center">
                <span class="w-10 h-10 rounded-xl bg-blue-100 text-blue-700 dark:bg-blue-900/60 dark:text-blue-300 flex items-center justify-center text-lg mb-2 group-hover:scale-110 transition">
                    🏦
                </span>
                <span class="text-xs font-black text-slate-800 dark:text-slate-200 block">মালিককে টাকা পাঠানো</span>
                <span class="text-[10px] text-slate-400 mt-0.5">Send Cash to Owner</span>
            </a>

            <!-- 4. Add Expense -->
            <a href="{{ route('expenses.create') }}"
               class="p-4 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50/60 dark:bg-slate-800/40 hover:bg-amber-50 hover:border-amber-200 dark:hover:bg-amber-950/30 dark:hover:border-amber-800 transition group text-center flex flex-col items-center justify-center">
                <span class="w-10 h-10 rounded-xl bg-amber-100 text-amber-700 dark:bg-amber-900/60 dark:text-amber-300 flex items-center justify-center text-lg mb-2 group-hover:scale-110 transition">
                    💸
                </span>
                <span class="text-xs font-black text-slate-800 dark:text-slate-200 block">দোকানের খরচ লিখুন</span>
                <span class="text-[10px] text-slate-400 mt-0.5">Record Expense</span>
            </a>

            <!-- 5. Stock Transfer Request -->
            <a href="{{ route('transfers.create') }}"
               class="p-4 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50/60 dark:bg-slate-800/40 hover:bg-purple-50 hover:border-purple-200 dark:hover:bg-purple-950/30 dark:hover:border-purple-800 transition group text-center flex flex-col items-center justify-center">
                <span class="w-10 h-10 rounded-xl bg-purple-100 text-purple-700 dark:bg-purple-900/60 dark:text-purple-300 flex items-center justify-center text-lg mb-2 group-hover:scale-110 transition">
                    📦
                </span>
                <span class="text-xs font-black text-slate-800 dark:text-slate-200 block">নতুন মালের রিকোয়েস্ট</span>
                <span class="text-[10px] text-slate-400 mt-0.5">Ask Warehouse</span>
            </a>

            <!-- 6. Attendance -->
            <a href="{{ route('attendance.index') }}"
               class="p-4 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50/60 dark:bg-slate-800/40 hover:bg-rose-50 hover:border-rose-200 dark:hover:bg-rose-950/30 dark:hover:border-rose-800 transition group text-center flex flex-col items-center justify-center">
                <span class="w-10 h-10 rounded-xl bg-rose-100 text-rose-700 dark:bg-rose-900/60 dark:text-rose-300 flex items-center justify-center text-lg mb-2 group-hover:scale-110 transition">
                    📋
                </span>
                <span class="text-xs font-black text-slate-800 dark:text-slate-200 block">স্টাফদের হাজিরা খাতা</span>
                <span class="text-[10px] text-slate-400 mt-0.5">Staff Attendance</span>
            </a>
        </div>
    </div>

    <!-- MAIN TWO COLUMN WORKSTATION -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- LEFT COLUMN: TODAY'S RECENT SALES (7 COLS) -->
        <div class="lg:col-span-7 bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden flex flex-col justify-between">
            <div>
                <div class="p-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                    <div>
                        <h2 class="text-xs font-black uppercase tracking-wider text-slate-800 dark:text-slate-200 flex items-center gap-2">
                            <span>🧾 আজকের সাম্প্রতিক বিক্রি সমূহ (Recent Sales Today)</span>
                        </h2>
                        <p class="text-[11px] text-slate-400 mt-0.5">আজকে কাস্টমারদের দেওয়া ইনভয়েস ও মেমোর তালিকা</p>
                    </div>
                    <a href="{{ route('pos.sales.history') }}" class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline font-bold">
                        সবগুলো দেখুন &rarr;
                    </a>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-slate-50 dark:bg-slate-800/60 border-b border-slate-200 dark:border-slate-800 text-slate-500 font-bold">
                                <th class="p-3">ইনভয়েস #</th>
                                <th class="p-3">সময়</th>
                                <th class="p-3">কাস্টমার</th>
                                <th class="p-3 text-right">মোট টাকা</th>
                                <th class="p-3 text-center">রিসিপ্ট</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @forelse($recentSales as $sale)
                                <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition">
                                    <td class="p-3 font-mono font-bold text-slate-900 dark:text-white">
                                        {{ $sale->invoice_number }}
                                    </td>
                                    <td class="p-3 text-slate-500 font-mono text-[11px]">
                                        {{ $sale->created_at->format('h:i A') }}
                                    </td>
                                    <td class="p-3 text-slate-700 dark:text-slate-300">
                                        {{ $sale->customer->name ?? 'সাধারণ কাস্টমার (Walk-in)' }}
                                    </td>
                                    <td class="p-3 text-right font-black font-mono text-emerald-600 dark:text-emerald-400">
                                        ৳{{ number_format($sale->total_payable, 2) }}
                                    </td>
                                    <td class="p-3 text-center">
                                        <a href="{{ route('pos.receipt', $sale) }}" target="_blank"
                                           class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-[10px] font-bold inline-flex items-center gap-1 transition">
                                            <span>🖨️ প্রিন্ট</span>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="p-8 text-center text-slate-400">
                                        <div class="text-2xl mb-1">🛍️</div>
                                        <div>আজ এখনও কোনো বিক্রি মেমো তৈরি হয়নি।</div>
                                        <a href="{{ route('pos.index') }}" class="mt-2 inline-block text-xs font-bold text-emerald-600 hover:underline">
                                            + নতুন বিক্রি শুরু করতে POS কাউন্টারে যান
                                        </a>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($recentSales->count() > 0)
                <div class="p-3 border-t border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/30 text-right">
                    <a href="{{ route('pos.sales.history') }}" class="text-xs font-bold text-slate-600 dark:text-slate-300 hover:text-indigo-600 transition">
                        আজকের বিক্রির বিস্তারিত হিসাব দেখুন &rarr;
                    </a>
                </div>
            @endif
        </div>

        <!-- RIGHT COLUMN: 2 ESSENTIAL TRACKERS (5 COLS) -->
        <div class="lg:col-span-5 space-y-6">
            <!-- 1. INCOMING STOCK TRANSFERS -->
            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm p-5 space-y-3">
                <div class="flex items-center justify-between pb-2 border-b border-slate-100 dark:border-slate-800">
                    <div>
                        <h2 class="text-xs font-black uppercase tracking-wider text-slate-800 dark:text-slate-200 flex items-center gap-1.5">
                            <span>🚚 ওয়্যারহাউস থেকে আসার পথে থাকা মাল</span>
                        </h2>
                        <span class="text-[10px] text-slate-400">নতুন স্টক পাঠানো হয়েছে কি না দেখুন</span>
                    </div>
                    <a href="{{ route('transfers.index') }}" class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline font-bold">
                        সব চালান &rarr;
                    </a>
                </div>

                <div class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($pendingTransfers as $tr)
                        <div class="py-2.5 flex items-center justify-between text-xs">
                            <div>
                                <span class="font-mono font-bold text-slate-900 dark:text-white">{{ $tr->transfer_number }}</span>
                                <div class="text-[10px] text-slate-400">কোথা থেকে: {{ $tr->sourceStore->name ?? 'সেন্ট্রাল ওয়্যারহাউস' }}</div>
                            </div>
                            <div class="text-right flex items-center gap-2">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase
                                    {{ $tr->status === 'in_transit' ? 'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300 animate-pulse' : 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300' }}">
                                    {{ $tr->status === 'in_transit' ? 'পথে আছে' : ucfirst($tr->status) }}
                                </span>
                                @if($tr->status === 'in_transit')
                                    <a href="{{ route('transfers.receive.view', $tr) }}"
                                       class="px-2 py-1 rounded bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-[10px] transition">
                                        রিসিভ করুন
                                    </a>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="py-5 text-center text-xs text-slate-400">
                            আপাতত কোনো মাল আসার অপেক্ষায় নেই।
                        </div>
                    @endforelse
                </div>

                <div class="pt-2">
                    <a href="{{ route('transfers.create') }}" class="w-full inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-bold transition">
                        <span>+ ওয়্যারহাউসের কাছে নতুন মালের রিকোয়েস্ট পাঠান</span>
                    </a>
                </div>
            </div>

            <!-- 2. LOW STOCK WARNINGS -->
            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm p-5 space-y-3">
                <div class="flex items-center justify-between pb-2 border-b border-slate-100 dark:border-slate-800">
                    <div>
                        <h2 class="text-xs font-black uppercase tracking-wider text-rose-600 dark:text-rose-400 flex items-center gap-1.5">
                            <span>⚠️ যেসব মালের স্টক শেষ বা কমে গেছে</span>
                        </h2>
                        <span class="text-[10px] text-slate-400">দ্রুত ওয়্যারহাউস থেকে অর্ডার করুন</span>
                    </div>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-300">
                        {{ $lowStockItems->count() }} টি আইটেম
                    </span>
                </div>

                <div class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($lowStockItems as $st)
                        <div class="py-2.5 flex items-center justify-between text-xs">
                            <div class="pr-2">
                                <span class="font-bold text-slate-800 dark:text-slate-200 block truncate max-w-xs">
                                    {{ $st->productVariant->product->name ?? 'গার্মেন্টস আইটেম' }}
                                </span>
                                <span class="text-[10px] text-slate-400 font-mono">
                                    SKU: {{ $st->productVariant->sku ?? '' }} • সাইজ: {{ $st->productVariant->size ?? '-' }}
                                </span>
                            </div>
                            <span class="font-mono font-black text-rose-600 text-xs whitespace-nowrap bg-rose-50 dark:bg-rose-950/40 px-2 py-0.5 rounded">
                                {{ $st->quantity }} পিস বাকি
                            </span>
                        </div>
                    @empty
                        <div class="py-5 text-center text-xs text-slate-400">
                            🎉 আলহামদুলিল্লাহ, সব মালামালের পর্যাপ্ত স্টক রয়েছে।
                        </div>
                    @endforelse
                </div>

                @if($lowStockItems->count() > 0)
                    <div class="pt-2">
                        <a href="{{ route('transfers.create') }}" class="w-full inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold shadow-sm transition">
                            <span>কম থাকা মালগুলোর জন্য স্টক রিকোয়েস্ট দিন</span>
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
