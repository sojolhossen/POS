@extends('layouts.app')

@section('title', __('messages.daily_cash_register') . ' - Control')
@section('header', __('messages.daily_cash_register'))

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-black text-slate-900 dark:text-white flex items-center gap-2">
                <span>{{ __('messages.daily_cash_register') }}</span>
                <span class="text-xs px-2.5 py-0.5 rounded-full bg-indigo-100 text-indigo-700 dark:bg-indigo-900/60 dark:text-indigo-300 font-bold">
                    Mandatory Daily Reconciliation
                </span>
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                Store-level physical cash reconciliation: Mandatory morning opening, auto-tracked cash movements, and evening closing lock.
            </p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('pos.index') }}" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-slate-800 dark:bg-slate-700 hover:bg-slate-900 text-white font-bold text-xs shadow-md transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                <span>Go to POS Checkout</span>
            </a>
        </div>
    </div>

    <!-- TODAY'S ACTIVE CASH REGISTER CARD -->
    @if($todayRegister)
        <div class="bg-white dark:bg-slate-900 rounded-2xl p-6 border border-slate-200 dark:border-slate-800 shadow-sm space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 dark:border-slate-800 pb-4">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-mono font-bold text-slate-400">BUSINESS DAY:</span>
                        <span class="text-sm font-black text-slate-900 dark:text-white font-mono">{{ $todayRegister->business_date->format('l, d F Y') }}</span>
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase
                            {{ $todayRegister->isOpen() ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300' : 'bg-slate-200 text-slate-800 dark:bg-slate-800 dark:text-slate-300' }}">
                            {{ $todayRegister->status }}
                        </span>
                    </div>
                    <p class="text-xs text-slate-500 mt-1">
                        Store: <strong>{{ $todayRegister->store->name }}</strong> • Opened by {{ $todayRegister->opener->name ?? 'Staff' }} at {{ $todayRegister->opened_at->format('h:i A') }}
                    </p>
                </div>

                @if(!$todayRegister->isOpen() && (auth()->user()->isStoreManager() || auth()->user()->isSuperAdmin()))
                    <!-- REOPEN FORM (SUPER ADMIN OR STORE MANAGER) -->
                    <form method="POST" action="{{ route('daily-registers.reopen', $todayRegister) }}" class="flex items-center gap-2">
                        @csrf
                        <input type="text" name="reopen_reason" required placeholder="Mandatory reason to reopen..."
                               class="text-xs rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white px-3 py-2 w-56">
                        <button type="submit" class="px-3.5 py-2 rounded-lg bg-amber-600 hover:bg-amber-700 text-white font-bold text-xs whitespace-nowrap shadow-sm">
                            Reopen Day
                        </button>
                    </form>
                @endif
            </div>

            <!-- Cash Flow Audit Grid -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-xs font-mono">
                <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-800/40 border border-slate-100 dark:border-slate-800">
                    <span class="text-[10px] uppercase font-bold text-slate-400">1. Opening Float</span>
                    <div class="text-xl font-black text-slate-900 dark:text-white mt-1">
                        ৳{{ number_format($todayRegister->opening_cash, 2) }}
                    </div>
                </div>

                <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-800/40 border border-slate-100 dark:border-slate-800">
                    <span class="text-[10px] uppercase font-bold text-slate-400">2. Cash Sales (+)</span>
                    <div class="text-xl font-black text-emerald-600 dark:text-emerald-400 mt-1">
                        +৳{{ number_format($todayRegister->cash_sales, 2) }}
                    </div>
                </div>

                <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-800/40 border border-slate-100 dark:border-slate-800">
                    <span class="text-[10px] uppercase font-bold text-slate-400">3. Cash Refunds (-)</span>
                    <div class="text-xl font-black text-rose-600 dark:text-rose-400 mt-1">
                        -৳{{ number_format($todayRegister->cash_refunds, 2) }}
                    </div>
                </div>

                <div class="p-4 rounded-xl bg-indigo-50/60 dark:bg-indigo-950/40 border border-indigo-100 dark:border-indigo-900">
                    <span class="text-[10px] uppercase font-bold text-indigo-600 dark:text-indigo-300">Expected Closing Cash</span>
                    <div class="text-xl font-black text-indigo-700 dark:text-indigo-300 mt-1">
                        ৳{{ number_format($todayRegister->expected_closing_cash, 2) }}
                    </div>
                </div>
            </div>

            <!-- CLOSING FORM (IF REGISTER IS OPEN) -->
            @if($todayRegister->isOpen())
                <div class="pt-4 border-t border-slate-100 dark:border-slate-800">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-800 dark:text-slate-200 mb-2">
                        Perform Evening Cash Count & Lock Register
                    </h3>
                    <form method="POST" action="{{ route('daily-registers.close', $todayRegister) }}" class="grid grid-cols-1 sm:grid-cols-3 gap-4 items-end">
                        @csrf
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">
                                Actual Counted Physical Cash in Drawer (৳) <span class="text-rose-500">*</span>
                            </label>
                            <input type="number" step="0.01" min="0" name="closing_cash_counted" required
                                   value="{{ old('closing_cash_counted', $todayRegister->expected_closing_cash) }}"
                                   class="w-full text-xs rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white px-3 py-2 font-mono font-bold">
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">
                                Discrepancy Reason (Mandatory if Drawer ≠ Expected)
                            </label>
                            <input type="text" name="discrepancy_reason" placeholder="e.g. ৳50 short due to small change shortage"
                                   class="w-full text-xs rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white px-3 py-2">
                        </div>

                        <button type="submit" onclick="return confirm('Close and lock cash register for today? This finalizes the business day.');"
                                class="px-5 py-2.5 rounded-lg bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs shadow-md transition">
                            Close & Lock Business Day
                        </button>
                    </form>
                </div>
            @else
                <!-- REGISTER CLOSED SUMMARY -->
                <div class="pt-4 border-t border-slate-100 dark:border-slate-800 grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs font-mono">
                    <div>
                        <span class="text-slate-400">Actual Counted:</span>
                        <strong class="text-slate-900 dark:text-white font-bold ml-1">৳{{ number_format($todayRegister->closing_cash_counted, 2) }}</strong>
                    </div>
                    <div>
                        <span class="text-slate-400">Discrepancy:</span>
                        <strong class="ml-1 {{ $todayRegister->cash_discrepancy == 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                            ৳{{ number_format($todayRegister->cash_discrepancy, 2) }}
                        </strong>
                    </div>
                    <div>
                        <span class="text-slate-400">Closed By:</span>
                        <strong class="text-slate-900 dark:text-white ml-1">{{ $todayRegister->closer->name ?? 'Staff' }}</strong>
                        @if($todayRegister->closed_at)
                            <span class="text-slate-400 text-[11px]">({{ $todayRegister->closed_at->format('h:i A') }})</span>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    @else
        <!-- OPEN REGISTER FORM (MANDATORY MORNING OPENING) -->
        <div class="bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 rounded-2xl p-6 text-white shadow-xl border border-slate-800 space-y-4">
            <div>
                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-400 text-slate-950">
                    ACTION REQUIRED
                </span>
                <h2 class="text-lg font-black mt-2">Open Daily Cash Register for {{ now()->format('l, d F Y') }}</h2>
                <p class="text-xs text-slate-300 mt-1 max-w-xl">
                    Every retail store must record its morning cash drawer float before POS sales can be conducted.
                </p>
            </div>

            <form method="POST" action="{{ route('daily-registers.open') }}" class="flex flex-col sm:flex-row items-end gap-3 pt-2">
                @csrf
                <input type="hidden" name="store_id" value="{{ $currentStore->id ?? 1 }}">

                <div class="w-full sm:w-64">
                    <label class="block text-xs font-bold text-indigo-300 mb-1">
                        Opening Drawer Cash Float (৳) <span class="text-rose-400">*</span>
                    </label>
                    <input type="number" step="0.01" min="0" name="opening_cash" required value="{{ old('opening_cash', '2000.00') }}"
                           class="w-full text-xs rounded-xl border-white/20 bg-white/10 text-white placeholder-slate-400 px-3.5 py-2.5 font-mono font-bold focus:ring-2 focus:ring-emerald-400 focus:outline-none">
                </div>

                <button type="submit" class="w-full sm:w-auto px-6 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-md transition whitespace-nowrap">
                    Confirm Opening Float & Start Business Day
                </button>
            </form>
        </div>
    @endif

    <!-- HISTORICAL CASH REGISTERS LEDGER -->
    <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
        <div class="p-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-200">
                Cash Register Closing Audit History
            </h2>
            <span class="text-xs text-slate-400 font-mono">{{ $registers->total() }} Total Registers</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-800/60 border-b border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-300 font-bold">
                        <th class="p-3">Business Date</th>
                        <th class="p-3">Store</th>
                        <th class="p-3 text-right">Opening Cash</th>
                        <th class="p-3 text-right text-emerald-600">Cash Sales</th>
                        <th class="p-3 text-right text-rose-600">Cash Refunds</th>
                        <th class="p-3 text-right font-bold">Expected Closing</th>
                        <th class="p-3 text-right">Counted Cash</th>
                        <th class="p-3 text-right">Discrepancy</th>
                        <th class="p-3 text-center">Status</th>
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
                            <td class="p-3 text-right font-bold text-indigo-600 dark:text-indigo-400">
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
                                    {{ $reg->isOpen() ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300' : 'bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-300' }}">
                                    {{ $reg->status }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="p-6 text-center text-slate-400 font-sans">
                                No historical registers recorded yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($registers->hasPages())
            <div class="p-3 border-t border-slate-200 dark:border-slate-800">
                {{ $registers->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
