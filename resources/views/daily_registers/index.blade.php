@extends('layouts.app')

@section('title', __('messages.daily_cash_register') . ' - Control')
@section('header', __('messages.daily_cash_register'))

@section('content')
<div class="space-y-6" x-data="{
    activeTab: 'remittances',
    showDetails: false,
    showClosingForm: false,
    showExpenseModal: false,
    showTransferModal: false,
    showRejectModal: false,
    rejectActionUrl: '',
    rejectTransferNumber: '',
    rejectTransferAmount: '',
    showVarianceNote: false
}">
    <!-- TOP HEADER -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="w-8 h-8 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-bold text-sm">
                    💵
                </span>
                <h1 class="text-lg font-black text-slate-900 dark:text-white tracking-tight">
                    {{ __('messages.daily_cash_register') }}
                </h1>
            </div>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                {{ __('messages.daily_registers_subtitle') }}
            </p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('pos.index') }}"
               class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-slate-800 dark:bg-slate-700 hover:bg-slate-900 text-white font-bold text-xs shadow-sm transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                <span>{{ __('messages.pos_checkout') }}</span>
            </a>
        </div>
    </div>

    <!-- STORE SELECTOR PILLS (IF MULTIPLE STORES ACCESSIBLE) -->
    @if(isset($accessibleStores) && $accessibleStores->count() > 1)
        <div class="flex items-center gap-2 overflow-x-auto pb-1 text-xs">
            <span class="text-slate-400 font-bold uppercase tracking-wider text-[10px] whitespace-nowrap">{{ __('messages.stores') }}:</span>
            @foreach($accessibleStores as $st)
                <a href="{{ route('daily-registers.index', ['store_id' => $st->id]) }}"
                   class="px-3 py-1.5 rounded-xl font-bold transition whitespace-nowrap {{ ($currentStore && $currentStore->id === $st->id) ? 'bg-indigo-600 text-white shadow-sm' : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700 hover:bg-slate-50' }}">
                    {{ $st->name }}
                </a>
            @endforeach
        </div>
    @endif

    <!-- UNCLOSED PREVIOUS REGISTER WARNING & RECOVERY CARD (OPTION 1 IMPLEMENTATION) -->
    @if(isset($unclosedPreviousRegister) && $unclosedPreviousRegister)
        <div class="bg-gradient-to-r from-amber-500/10 via-orange-500/10 to-amber-500/10 dark:from-amber-950/40 dark:via-orange-950/30 dark:to-amber-950/40 rounded-2xl border-2 border-amber-400 dark:border-amber-600 p-5 shadow-sm space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <span class="w-12 h-12 rounded-2xl bg-amber-500 text-white flex items-center justify-center text-2xl shrink-0 font-bold shadow-md shadow-amber-500/30 animate-pulse">
                        ⚠️
                    </span>
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="text-sm font-black text-amber-900 dark:text-amber-100 uppercase tracking-wide">
                                {{ __('messages.unclosed_register_detected') }}
                            </h2>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-rose-500 text-white animate-bounce">
                                ACTION REQUIRED
                            </span>
                        </div>
                        <p class="text-xs text-amber-800 dark:text-amber-200 mt-1">
                            {{ $unclosedPreviousRegister->store->name }} এর <strong>{{ $unclosedPreviousRegister->business_date->format('d M Y') }}</strong> তারিখের ক্যাশ ড্রয়ার বন্ধ (Close) করা হয়নি। ড্রয়ারে আনুমানিক প্রত্যাশিত ক্যাশ: <strong class="font-mono text-sm text-slate-900 dark:text-white font-black">৳{{ number_format($unclosedPreviousRegister->expected_closing_cash, 2) }}</strong>।
                        </p>
                    </div>
                </div>
            </div>

            <!-- 2 QUICK RECOVERY OPTIONS -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 pt-2 border-t border-amber-300/60 dark:border-amber-700/60">
                <!-- OPTION A: 1-Click Quick Close with Expected Cash -->
                <div class="p-3.5 rounded-xl bg-white dark:bg-slate-900 border border-amber-200 dark:border-amber-800 flex flex-col justify-between space-y-2">
                    <div>
                        <span class="text-[10px] font-bold text-amber-600 dark:text-amber-400 uppercase tracking-wider block">সহজ পদ্ধতি (১-ক্লিক সমাধান)</span>
                        <h3 class="text-xs font-bold text-slate-900 dark:text-white mt-0.5">প্রত্যাশিত ক্যাশ (৳{{ number_format($unclosedPreviousRegister->expected_closing_cash, 2) }}) দিয়ে ক্লোজ করুন</h3>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">
                            গতকাল দোকান বন্ধের সময় যদি ড্রয়ারের টাকা সুরক্ষিত রেখে যাওয়া হয়ে থাকে, তবে কোনো গরমিল ছাড়া ১-ক্লিকেই আগের দিনের ড্রয়ার ক্লোজ করে নিন।
                        </p>
                    </div>
                    <form method="POST" action="{{ route('daily-registers.close', $unclosedPreviousRegister) }}">
                        @csrf
                        <input type="hidden" name="closing_cash_counted" value="{{ $unclosedPreviousRegister->expected_closing_cash }}">
                        <input type="hidden" name="discrepancy_reason" value="Previous day quick close on next morning opening">
                        <button type="submit" onclick="return confirm('গতকালকের ড্রয়ার ৳{{ number_format($unclosedPreviousRegister->expected_closing_cash, 2) }} ব্যালেন্স দিয়ে ক্লোজ করবেন?');"
                                class="w-full py-2.5 px-3 rounded-xl bg-amber-600 hover:bg-amber-700 text-white font-bold text-xs shadow-sm transition flex items-center justify-center gap-1.5">
                            <span>⚡ ১-ক্লিকে গতকালের ড্রয়ার ক্লোজ করুন</span>
                        </button>
                    </form>
                </div>

                <!-- OPTION B: Count & Close (Manual Count) -->
                <div class="p-3.5 rounded-xl bg-white dark:bg-slate-900 border border-amber-200 dark:border-amber-800 flex flex-col justify-between space-y-2"
                     x-data="{ showManualClose: false }">
                    <div>
                        <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider block">ম্যানুয়াল পদ্ধতি</span>
                        <h3 class="text-xs font-bold text-slate-900 dark:text-white mt-0.5">ক্যাশ গুনে ড্রয়ার ক্লোজ করুন</h3>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">
                            গতকালকের ড্রয়ারের ক্যাশে কোনো কম/বেশি থাকলে প্রকৃত গোনা টাকা লিখে কারণসহ ড্রয়ার ক্লোজ করুন।
                        </p>
                    </div>
                    <div>
                        <button type="button" @click="showManualClose = !showManualClose"
                                class="w-full py-2 px-3 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 hover:bg-slate-100 text-slate-700 dark:text-slate-200 font-bold text-xs transition">
                            <span x-text="showManualClose ? 'ফর্ম লুকান' : 'ক্যাশ গুনে ক্লোজ ফর্ম খুলুন &darr;'">ক্যাশ গুনে ক্লোজ ফর্ম খুলুন &darr;</span>
                        </button>
                        
                        <form x-show="showManualClose" x-cloak method="POST" action="{{ route('daily-registers.close', $unclosedPreviousRegister) }}" class="mt-3 space-y-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                            @csrf
                            <div>
                                <label class="block text-[10px] font-bold text-slate-600 dark:text-slate-400 mb-0.5">গতকালকের প্রকৃত গোনা টাকা (৳):</label>
                                <input type="number" step="0.01" min="0" name="closing_cash_counted" required value="{{ $unclosedPreviousRegister->expected_closing_cash }}" class="w-full text-xs rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white px-2.5 py-1.5 font-mono font-bold">
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-600 dark:text-slate-400 mb-0.5">গরমিলের কারণ (যদি থাকে):</label>
                                <input type="text" name="discrepancy_reason" placeholder="যেমন: খুচরো শর্ট বা অন্যান্য..." class="w-full text-xs rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white px-2.5 py-1.5">
                            </div>
                            <button type="submit" onclick="return confirm('গতকালকের ড্রয়ার ক্লোজ করতে নিশ্চিত?');" class="w-full py-2 px-3 rounded-lg bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs transition">
                                সংরক্ষণ ও ক্লোজ করুন
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- HERO ACTIVE REGISTER COMPACT MINIMAL STRIP -->
    @if($todayRegister)
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm transition overflow-hidden">
            <!-- MAIN COMPACT STRIP -->
            <div class="p-4 sm:p-5 flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                <!-- Left: Live Balance & Store Status -->
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-2xl {{ $todayRegister->isOpen() ? 'bg-emerald-50 text-emerald-600 dark:bg-emerald-950/50 dark:text-emerald-400' : 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400' }} flex items-center justify-center text-xl shrink-0 font-bold border border-emerald-100 dark:border-emerald-900/40">
                        ৳
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                                {{ __('messages.current_drawer_cash') }}
                            </span>
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider
                                {{ $todayRegister->isOpen() ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300' : 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ $todayRegister->isOpen() ? 'bg-emerald-500 animate-pulse' : 'bg-slate-400' }}"></span>
                                {{ $todayRegister->status }}
                            </span>
                        </div>
                        <div class="flex items-baseline gap-2 mt-0.5">
                            <span class="text-2xl sm:text-3xl font-black font-mono tracking-tight text-slate-900 dark:text-white">
                                ৳{{ number_format($todayRegister->expected_closing_cash, 2) }}
                            </span>
                            <span class="text-xs text-slate-400 hidden sm:inline">
                                • {{ $todayRegister->store->name }} ({{ $todayRegister->business_date->format('d M') }})
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Right: Quick Action Buttons & Details Toggle -->
                <div class="flex items-center gap-2 flex-wrap">
                    @if($todayRegister->isOpen())
                        <!-- Details Toggle Button -->
                        <button type="button" @click="showDetails = !showDetails"
                                class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold text-xs transition">
                            <span>📊</span>
                            <span class="hidden sm:inline" x-text="showDetails ? '{{ __('messages.hide_breakdown') }}' : '{{ __('messages.view_breakdown') }}'">{{ __('messages.view_breakdown') }}</span>
                            <svg class="w-3.5 h-3.5 text-slate-400 transition-transform duration-200" :class="showDetails ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>

                        <!-- Quick Action: Expense -->
                        <button type="button" @click="showExpenseModal = true"
                                class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold text-xs transition">
                            <span>💸</span>
                            <span>{{ __('messages.record_drawer_expense') }}</span>
                        </button>

                        <!-- Quick Action: Remit -->
                        <button type="button" @click="showTransferModal = true"
                                class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold text-xs transition">
                            <span>🏦</span>
                            <span>{{ __('messages.remit_cash_to_owner') }}</span>
                        </button>

                        <!-- Quick Action: Close -->
                        <button type="button" @click="showClosingForm = !showClosingForm"
                                class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold text-xs transition">
                            <span>🔒</span>
                            <span>{{ __('messages.close_drawer_btn') }}</span>
                        </button>
                    @elseif(auth()->user()->isStoreManager() || auth()->user()->isSuperAdmin())
                        <!-- Reopen Form -->
                        <form method="POST" action="{{ route('daily-registers.reopen', $todayRegister) }}" class="flex items-center gap-2">
                            @csrf
                            <input type="text" name="reopen_reason" required placeholder="{{ __('messages.reopen_reason_placeholder') }}"
                                   class="text-xs rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white px-3 py-1.5 w-48">
                            <button type="submit" class="px-3.5 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold text-xs shadow-sm transition whitespace-nowrap">
                                {{ __('messages.reopen_day') }}
                            </button>
                        </form>
                    @endif
                </div>
            </div>

            <!-- COLLAPSIBLE BREAKDOWN SUMMARY (CLEAN INLINE ACCORDION) -->
            <div x-show="showDetails" x-cloak
                 class="px-5 py-3.5 bg-slate-50/70 dark:bg-slate-800/40 border-t border-slate-100 dark:border-slate-800 text-xs">
                <div class="flex items-center justify-between flex-wrap gap-3 font-mono">
                    <div class="flex items-center gap-1.5 text-slate-600 dark:text-slate-300">
                        <span class="text-slate-400 text-[11px] font-sans">{{ __('messages.morning_float') }}:</span>
                        <strong class="font-bold">৳{{ number_format($todayRegister->opening_cash, 2) }}</strong>
                    </div>
                    <span class="text-slate-300 dark:text-slate-700 hidden sm:inline">+</span>
                    <div class="flex items-center gap-1.5 text-emerald-600 dark:text-emerald-400">
                        <span class="text-emerald-600/70 dark:text-emerald-400/70 text-[11px] font-sans">{{ __('messages.cash_sales') }}:</span>
                        <strong class="font-bold">+৳{{ number_format($todayRegister->cash_sales, 2) }}</strong>
                    </div>
                    <span class="text-slate-300 dark:text-slate-700 hidden sm:inline">-</span>
                    <div class="flex items-center gap-1.5 text-rose-600 dark:text-rose-400">
                        <span class="text-rose-600/70 dark:text-rose-400/70 text-[11px] font-sans">{{ __('messages.cash_refunds') }}:</span>
                        <strong class="font-bold">-৳{{ number_format($todayRegister->cash_refunds, 2) }}</strong>
                    </div>
                    <span class="text-slate-300 dark:text-slate-700 hidden sm:inline">-</span>
                    <div class="flex items-center gap-1.5 text-amber-600 dark:text-amber-400">
                        <span class="text-amber-600/70 dark:text-amber-400/70 text-[11px] font-sans">{{ __('messages.cash_expenses') }}:</span>
                        <strong class="font-bold">-৳{{ number_format($todayRegister->cash_expenses, 2) }}</strong>
                    </div>
                    <span class="text-slate-300 dark:text-slate-700 hidden sm:inline">-</span>
                    <div class="flex items-center gap-1.5 text-indigo-600 dark:text-indigo-400">
                        <span class="text-indigo-600/70 dark:text-indigo-400/70 text-[11px] font-sans">{{ __('messages.cash_remitted') }}:</span>
                        <strong class="font-bold">-৳{{ number_format($todayRegister->cash_transfers, 2) }}</strong>
                    </div>
                </div>
                <div class="mt-2 pt-2 border-t border-slate-200/50 dark:border-slate-700/50 flex items-center justify-between text-[11px] text-slate-400">
                    <span>{{ __('messages.opened_by') }}: <strong>{{ $todayRegister->opener->name ?? 'Staff' }}</strong> ({{ $todayRegister->opened_at->format('h:i A') }})</span>
                    <span>স্টোর: <strong>{{ $todayRegister->store->name }}</strong></span>
                </div>
            </div>

            <!-- COLLAPSIBLE EVENING CLOSE FORM -->
            @if($todayRegister->isOpen())
                <div x-show="showClosingForm" x-cloak
                     class="p-5 border-t border-slate-100 dark:border-slate-800 bg-rose-50/20 dark:bg-rose-950/10 space-y-3">
                    <div class="flex items-center justify-between">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-800 dark:text-slate-200 flex items-center gap-1.5">
                            <span>🔒</span> {{ __('messages.close_drawer_btn') }}
                        </h3>
                        <button type="button" @click="showClosingForm = false" class="text-xs text-slate-400 hover:text-slate-600">✕ {{ __('messages.cancel') }}</button>
                    </div>

                    <form method="POST" action="{{ route('daily-registers.close', $todayRegister) }}" class="grid grid-cols-1 sm:grid-cols-3 gap-3 items-end">
                        @csrf
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">
                                {{ __('messages.actual_counted_cash') }} (৳) <span class="text-rose-500">*</span>
                            </label>
                            <input type="number" step="0.01" min="0" name="closing_cash_counted" required
                                   value="{{ old('closing_cash_counted', $todayRegister->expected_closing_cash) }}"
                                   class="w-full text-xs rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white px-3 py-2 font-mono font-bold">
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">
                                {{ __('messages.discrepancy_reason_optional') }}
                            </label>
                            <input type="text" name="discrepancy_reason" placeholder="e.g. ৳50 short due to coins shortage"
                                   class="w-full text-xs rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white px-3 py-2">
                        </div>

                        <button type="submit" onclick="return confirm('{{ __('messages.close_and_lock') }}?');"
                                class="px-5 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs shadow-md transition whitespace-nowrap">
                            {{ __('messages.close_and_lock') }}
                        </button>
                    </form>
                </div>
            @else
                <!-- REGISTER CLOSED SUMMARY -->
                <div class="px-5 py-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-xs text-slate-500 bg-slate-50/50 dark:bg-slate-800/30">
                    <div>
                        {{ __('messages.actual_counted_cash') }}:
                        <strong class="text-slate-900 dark:text-white font-mono ml-1">৳{{ number_format($todayRegister->closing_cash_counted, 2) }}</strong>
                    </div>
                    <div>
                        {{ __('messages.discrepancy') }}:
                        <strong class="ml-1 font-mono {{ $todayRegister->cash_discrepancy == 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                            ৳{{ number_format($todayRegister->cash_discrepancy, 2) }}
                        </strong>
                    </div>
                    <div>
                        {{ __('messages.status') }}: <span class="uppercase font-bold text-slate-700 dark:text-slate-300">{{ $todayRegister->status }}</span>
                    </div>
                </div>
            @endif
        </div>
    @else
        <!-- MINIMAL MORNING OPENING CARD -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm p-6 space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <span class="w-10 h-10 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-bold text-lg">
                        🌅
                    </span>
                    <div>
                        <h2 class="text-sm font-black text-slate-900 dark:text-white">
                            {{ __('messages.open_register_now') }} — {{ $currentStore->name ?? 'Retail Store' }}
                        </h2>
                        <p class="text-xs text-slate-500">
                            {{ now()->format('l, d F Y') }}
                        </p>
                    </div>
                </div>

                @if($suggestedOpening && $suggestedOpening['has_previous'])
                    <div class="px-3 py-1.5 rounded-xl bg-emerald-50 dark:bg-emerald-950/50 border border-emerald-200 dark:border-emerald-800 text-xs">
                        <span class="text-slate-500 text-[10px] block">{{ __('messages.previous_closing_balance') }} ({{ $suggestedOpening['previous_date'] }}):</span>
                        <strong class="text-emerald-600 dark:text-emerald-400 font-mono text-sm">৳{{ number_format($suggestedOpening['suggested_amount'], 2) }}</strong>
                    </div>
                @endif
            </div>

            <form method="POST" action="{{ route('daily-registers.open') }}" class="space-y-3 pt-2 border-t border-slate-100 dark:border-slate-800">
                @csrf
                <input type="hidden" name="store_id" value="{{ $currentStore->id ?? 1 }}">

                <div class="flex flex-col sm:flex-row items-end gap-3">
                    <div class="w-full sm:w-72">
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            {{ __('messages.opening_cash') }} (৳) <span class="text-rose-500">*</span>
                        </label>
                        <input type="number" step="0.01" min="0" name="opening_cash" required
                               value="{{ old('opening_cash', ($suggestedOpening && $suggestedOpening['has_previous']) ? number_format($suggestedOpening['suggested_amount'], 2, '.', '') : '3000.00') }}"
                               class="w-full text-xs rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white px-3.5 py-2.5 font-mono font-bold focus:ring-2 focus:ring-indigo-500">
                    </div>

                    <button type="submit" class="w-full sm:w-auto px-6 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs shadow-md transition whitespace-nowrap">
                        {{ __('messages.open_drawer_btn') }}
                    </button>
                </div>

                <!-- Optional variance reason toggle -->
                <div>
                    <button type="button" @click="showVarianceNote = !showVarianceNote" class="text-[11px] text-indigo-600 dark:text-indigo-400 hover:underline">
                        + {{ __('messages.discrepancy_reason_optional') }}
                    </button>
                    <div x-show="showVarianceNote" x-cloak class="mt-2 max-w-md">
                        <input type="text" name="opening_discrepancy_reason" placeholder="e.g. Added ৳1000 petty float by owner..."
                               class="w-full text-xs rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white px-3 py-2">
                    </div>
                </div>
            </form>
        </div>
    @endif

    <!-- BOTTOM TABBED SEGMENT (MINIMAL SWITCHER) -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
        <!-- Tab Navigation Bar -->
        <div class="p-3 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between gap-3 flex-wrap">
            <div class="flex items-center gap-1.5 bg-slate-100 dark:bg-slate-800 p-1 rounded-xl">
                <button type="button" @click="activeTab = 'remittances'"
                        :class="activeTab === 'remittances' ? 'bg-white dark:bg-slate-700 text-slate-900 dark:text-white shadow-xs font-bold' : 'text-slate-500 hover:text-slate-900 dark:hover:text-white font-medium'"
                        class="px-3.5 py-1.5 rounded-lg text-xs transition flex items-center gap-2">
                    <span>🏦 {{ __('messages.remittances_tab') }}</span>
                    @php $pendingCount = $transfers->where('status', 'pending')->count(); @endphp
                    @if($pendingCount > 0)
                        <span class="px-1.5 py-0.2 rounded-full text-[10px] font-bold bg-amber-400 text-slate-950 font-mono">
                            {{ $pendingCount }}
                        </span>
                    @endif
                </button>

                <button type="button" @click="activeTab = 'history'"
                        :class="activeTab === 'history' ? 'bg-white dark:bg-slate-700 text-slate-900 dark:text-white shadow-xs font-bold' : 'text-slate-500 hover:text-slate-900 dark:hover:text-white font-medium'"
                        class="px-3.5 py-1.5 rounded-lg text-xs transition flex items-center gap-1.5">
                    <span>📋 {{ __('messages.history_tab') }}</span>
                    <span class="text-[10px] text-slate-400 font-mono">({{ $registers->total() }})</span>
                </button>
            </div>

            @if($todayRegister && $todayRegister->isOpen())
                <button type="button" @click="showTransferModal = true"
                        class="px-3 py-1.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs shadow-sm transition">
                    + {{ __('messages.remit_cash_to_owner') }}
                </button>
            @endif
        </div>

        <!-- TAB 1 CONTENT: REMITTANCES TO OWNER / BANK -->
        <div x-show="activeTab === 'remittances'" class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-800/60 border-b border-slate-200 dark:border-slate-800 text-slate-500 font-bold">
                        <th class="p-3">Ref #</th>
                        <th class="p-3">Date</th>
                        <th class="p-3">Store</th>
                        <th class="p-3 text-right">Amount</th>
                        <th class="p-3">Method / Destination</th>
                        <th class="p-3">By</th>
                        <th class="p-3 text-center">Status</th>
                        <th class="p-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800 font-mono">
                    @forelse($transfers as $trf)
                        <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition">
                            <td class="p-3 font-bold text-indigo-600 dark:text-indigo-400">
                                {{ $trf->transfer_number }}
                            </td>
                            <td class="p-3 text-slate-500 font-sans text-[11px]">
                                {{ $trf->transferred_at ? $trf->transferred_at->format('d M, h:i A') : '—' }}
                            </td>
                            <td class="p-3 font-sans font-medium text-slate-700 dark:text-slate-300">
                                {{ $trf->store->name }}
                            </td>
                            <td class="p-3 text-right font-black text-slate-900 dark:text-white">
                                ৳{{ number_format($trf->amount, 2) }}
                            </td>
                            <td class="p-3 font-sans">
                                <span class="capitalize font-semibold text-slate-800 dark:text-slate-200">{{ str_replace('_', ' ', $trf->transfer_method) }}</span>
                                @if($trf->financialAccount)
                                    <span class="text-[10px] text-slate-400 block">&rarr; {{ $trf->financialAccount->name }}</span>
                                @endif
                            </td>
                            <td class="p-3 font-sans text-slate-600 dark:text-slate-300">
                                {{ $trf->transferredBy->name ?? 'Staff' }}
                            </td>
                            <td class="p-3 text-center font-sans">
                                @if($trf->status === 'approved')
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300">
                                        Approved
                                    </span>
                                @elseif($trf->status === 'rejected')
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-300" title="{{ $trf->rejection_reason }}">
                                        Rejected & Refunded
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300 animate-pulse">
                                        Pending Admin
                                    </span>
                                @endif
                            </td>
                            <td class="p-3 text-right font-sans">
                                @if($trf->status === 'pending' && auth()->user()->isSuperAdmin())
                                    <div class="flex items-center justify-end gap-1.5">
                                        <form method="POST" action="{{ route('daily-registers.transfers.approve', $trf) }}" onsubmit="return confirm('Approve transfer of ৳{{ number_format($trf->amount, 2) }}?');">
                                            @csrf
                                            <button type="submit" class="px-2 py-1 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-[11px] transition">
                                                Approve
                                            </button>
                                        </form>
                                        <button type="button"
                                                @click="rejectActionUrl = '{{ route('daily-registers.transfers.reject', $trf) }}'; rejectTransferNumber = '{{ $trf->transfer_number }}'; rejectTransferAmount = '{{ number_format($trf->amount, 2) }}'; showRejectModal = true;"
                                                class="px-2 py-1 rounded-lg bg-rose-600 hover:bg-rose-700 text-white font-bold text-[11px] transition">
                                            Reject
                                        </button>
                                    </div>
                                @else
                                    <span class="text-slate-400 text-[11px]">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="p-6 text-center text-slate-400 font-sans">
                                No remittances recorded.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            @if($transfers->hasPages())
                <div class="p-3 border-t border-slate-100 dark:border-slate-800">
                    {{ $transfers->links() }}
                </div>
            @endif
        </div>

        <!-- TAB 2 CONTENT: CLOSING HISTORY LEDGER -->
        <div x-show="activeTab === 'history'" x-cloak class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-800/60 border-b border-slate-200 dark:border-slate-800 text-slate-500 font-bold">
                        <th class="p-3">Date</th>
                        <th class="p-3">Store</th>
                        <th class="p-3 text-right">{{ __('messages.morning_float') }}</th>
                        <th class="p-3 text-right text-emerald-600">{{ __('messages.cash_sales') }}</th>
                        <th class="p-3 text-right text-rose-600">{{ __('messages.cash_refunds') }}</th>
                        <th class="p-3 text-right text-amber-600">{{ __('messages.cash_expenses') }}</th>
                        <th class="p-3 text-right text-indigo-600">{{ __('messages.cash_remitted') }}</th>
                        <th class="p-3 text-right font-bold">Expected</th>
                        <th class="p-3 text-right">Counted</th>
                        <th class="p-3 text-right">Variance</th>
                        <th class="p-3 text-center">Status</th>
                        <th class="p-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800 font-mono">
                    @forelse($registers as $reg)
                        <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition">
                            <td class="p-3 font-bold text-slate-900 dark:text-white">
                                {{ $reg->business_date->format('d M Y') }}
                            </td>
                            <td class="p-3 font-sans font-medium text-slate-700 dark:text-slate-300">
                                {{ $reg->store->name }}
                            </td>
                            <td class="p-3 text-right text-slate-600 dark:text-slate-300">
                                ৳{{ number_format($reg->opening_cash, 2) }}
                            </td>
                            <td class="p-3 text-right text-emerald-600 dark:text-emerald-400">
                                +৳{{ number_format($reg->cash_sales, 2) }}
                            </td>
                            <td class="p-3 text-right text-rose-600 dark:text-rose-400">
                                -৳{{ number_format($reg->cash_refunds, 2) }}
                            </td>
                            <td class="p-3 text-right text-amber-600 dark:text-amber-400">
                                -৳{{ number_format($reg->cash_expenses, 2) }}
                            </td>
                            <td class="p-3 text-right text-indigo-600 dark:text-indigo-400">
                                -৳{{ number_format($reg->cash_transfers, 2) }}
                            </td>
                            <td class="p-3 text-right font-bold text-indigo-700 dark:text-indigo-300">
                                ৳{{ number_format($reg->expected_closing_cash, 2) }}
                            </td>
                            <td class="p-3 text-right text-slate-800 dark:text-slate-200 font-bold">
                                {{ $reg->closing_cash_counted !== null ? '৳' . number_format($reg->closing_cash_counted, 2) : '—' }}
                            </td>
                            <td class="p-3 text-right font-bold {{ $reg->cash_discrepancy < 0 ? 'text-rose-600' : ($reg->cash_discrepancy > 0 ? 'text-blue-600' : 'text-slate-400') }}">
                                {{ $reg->cash_discrepancy !== null ? ($reg->cash_discrepancy > 0 ? '+' : '') . '৳' . number_format($reg->cash_discrepancy, 2) : '—' }}
                            </td>
                            <td class="p-3 text-center font-sans">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase
                                    {{ $reg->isOpen() ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300' : 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300' }}">
                                    {{ $reg->status }}
                                </span>
                            </td>
                            <td class="p-3 text-right font-sans">
                                @if($reg->isOpen())
                                    <form method="POST" action="{{ route('daily-registers.close', $reg) }}" class="inline-block">
                                        @csrf
                                        <input type="hidden" name="closing_cash_counted" value="{{ $reg->expected_closing_cash }}">
                                        <input type="hidden" name="discrepancy_reason" value="Quick-closed from registers history ledger">
                                        <button type="submit" onclick="return confirm('{{ __('messages.close_and_lock') }} ({{ $reg->business_date->format('d M Y') }})?');"
                                                class="px-2.5 py-1 rounded-lg bg-amber-600 hover:bg-amber-700 text-white font-bold text-[10px] shadow-xs transition whitespace-nowrap">
                                            ⚡ Quick Close
                                        </button>
                                    </form>
                                @else
                                    <span class="text-slate-400 text-[11px]">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="12" class="p-6 text-center text-slate-400 font-sans">
                                No history records found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            @if($registers->hasPages())
                <div class="p-3 border-t border-slate-100 dark:border-slate-800">
                    {{ $registers->links() }}
                </div>
            @endif
        </div>
    </div>

    <!-- MODAL 1: RECORD DRAWER EXPENSE -->
    <div x-show="showExpenseModal" x-cloak
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white dark:bg-slate-900 rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-200 dark:border-slate-800 space-y-4"
             @click.away="showExpenseModal = false">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                <h3 class="text-sm font-black text-slate-900 dark:text-white flex items-center gap-2">
                    <span>💸 {{ __('messages.record_drawer_expense') }}</span>
                </h3>
                <button type="button" @click="showExpenseModal = false" class="text-slate-400 hover:text-slate-600">✕</button>
            </div>

            <form method="POST" action="{{ route('daily-registers.record-expense') }}" enctype="multipart/form-data" class="space-y-3">
                @csrf
                <input type="hidden" name="store_id" value="{{ $currentStore->id ?? 1 }}">

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.category') }} <span class="text-rose-500">*</span></label>
                    <select name="expense_category_id" required class="w-full text-xs rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white px-3 py-2.5">
                        <option value="">Select Category...</option>
                        @foreach($expenseCategories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.total_amount') }} (৳) <span class="text-rose-500">*</span></label>
                        <input type="number" step="0.01" min="0.01" max="{{ $todayRegister ? $todayRegister->expected_closing_cash : 999999 }}" name="amount" required placeholder="0.00"
                               class="w-full text-xs rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white px-3 py-2 font-mono font-bold">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Title <span class="text-rose-500">*</span></label>
                        <input type="text" name="title" required placeholder="e.g. Staff tea & snacks"
                               class="w-full text-xs rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white px-3 py-2">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Note (Optional)</label>
                    <input type="text" name="description" placeholder="Voucher notes..."
                           class="w-full text-xs rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white px-3 py-2">
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" @click="showExpenseModal = false" class="px-3.5 py-2 text-xs font-bold text-slate-500">{{ __('messages.cancel') }}</button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-amber-600 hover:bg-amber-700 text-white font-bold text-xs shadow-sm transition">
                        Deduct & Save
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 2: TRANSFER CASH TO OWNER / BANK -->
    <div x-show="showTransferModal" x-cloak
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white dark:bg-slate-900 rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-200 dark:border-slate-800 space-y-4"
             @click.away="showTransferModal = false">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                <h3 class="text-sm font-black text-slate-900 dark:text-white flex items-center gap-2">
                    <span>🏦 {{ __('messages.remit_cash_to_owner') }}</span>
                </h3>
                <button type="button" @click="showTransferModal = false" class="text-slate-400 hover:text-slate-600">✕</button>
            </div>

            <form method="POST" action="{{ route('daily-registers.transfer-cash') }}" enctype="multipart/form-data" class="space-y-3">
                @csrf
                <input type="hidden" name="store_id" value="{{ $currentStore->id ?? 1 }}">

                <div class="p-3 rounded-xl bg-indigo-50 dark:bg-indigo-950/40 border border-indigo-100 dark:border-indigo-900 text-xs">
                    <span class="text-indigo-600 dark:text-indigo-300 text-[11px] block">{{ __('messages.current_drawer_cash') }}:</span>
                    <strong class="text-lg font-black text-indigo-700 dark:text-indigo-300 font-mono">
                        ৳{{ number_format($todayRegister ? $todayRegister->expected_closing_cash : 0, 2) }}
                    </strong>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.total_amount') }} (৳) <span class="text-rose-500">*</span></label>
                        <input type="number" step="0.01" min="0.01" max="{{ $todayRegister ? $todayRegister->expected_closing_cash : 999999 }}" name="amount" required placeholder="0.00"
                               class="w-full text-xs rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white px-3 py-2 font-mono font-bold">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Method <span class="text-rose-500">*</span></label>
                        <select name="transfer_method" required class="w-full text-xs rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white px-3 py-2">
                            <option value="cash_to_owner">Cash to Owner</option>
                            <option value="bank_deposit">Bank Deposit</option>
                            <option value="bkash">bKash</option>
                            <option value="nagad">Nagad</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Destination Financial Account</label>
                    <select name="financial_account_id" class="w-full text-xs rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white px-3 py-2">
                        <option value="">None / Handover directly</option>
                        @foreach($financialAccounts as $acc)
                            <option value="{{ $acc->id }}">{{ $acc->name }} ({{ ucfirst($acc->account_type) }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Recipient / Slip #</label>
                        <input type="text" name="recipient_name" placeholder="e.g. Mr. Tanvir (Owner)"
                               class="w-full text-xs rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Slip Ref</label>
                        <input type="text" name="reference_number" placeholder="Slip #12345"
                               class="w-full text-xs rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white px-3 py-2">
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" @click="showTransferModal = false" class="px-3.5 py-2 text-xs font-bold text-slate-500">{{ __('messages.cancel') }}</button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs shadow-md transition">
                        Submit & Deduct Cash
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 3: SUPER ADMIN REJECT TRANSFER -->
    <div x-show="showRejectModal" x-cloak
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white dark:bg-slate-900 rounded-2xl max-w-sm w-full p-5 shadow-2xl border border-slate-200 dark:border-slate-800 space-y-3"
             @click.away="showRejectModal = false">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-2">
                <h3 class="text-sm font-black text-rose-600 dark:text-rose-400">
                    Reject Cash Transfer
                </h3>
                <button type="button" @click="showRejectModal = false" class="text-slate-400 hover:text-slate-600">✕</button>
            </div>

            <p class="text-xs text-slate-600 dark:text-slate-300">
                Rejecting transfer <strong class="font-mono text-slate-900 dark:text-white" x-text="rejectTransferNumber"></strong> (৳<span x-text="rejectTransferAmount"></span>) will <strong>immediately restore</strong> the cash back into the store drawer.
            </p>

            <form :action="rejectActionUrl" method="POST" class="space-y-3">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Rejection Reason <span class="text-rose-500">*</span></label>
                    <textarea name="rejection_reason" required rows="2" placeholder="e.g. Cash was not received by owner..."
                              class="w-full text-xs rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white px-3 py-2"></textarea>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" @click="showRejectModal = false" class="px-3 py-1.5 text-xs text-slate-500">{{ __('messages.cancel') }}</button>
                    <button type="submit" class="px-4 py-1.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs shadow-sm transition">
                        Confirm Rejection
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
