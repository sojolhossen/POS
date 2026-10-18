@extends('layouts.app')

@section('title', __('messages.daily_cash_register') . ' - Control')
@section('header', __('messages.daily_cash_register'))

@section('content')
<div class="space-y-6" x-data="{
    activeTab: 'remittances',
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

    <!-- HERO ACTIVE REGISTER CARD -->
    @if($todayRegister)
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm p-6 space-y-5">
            <!-- Top Status Row -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 dark:border-slate-800 pb-4">
                <div class="flex items-center gap-3">
                    <span class="px-2.5 py-1 rounded-full text-xs font-bold uppercase tracking-wider
                        {{ $todayRegister->isOpen() ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300' : 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300' }}">
                        ● {{ $todayRegister->status }}
                    </span>
                    <div>
                        <div class="text-xs font-bold text-slate-900 dark:text-white">
                            {{ $todayRegister->store->name }} — {{ $todayRegister->business_date->format('l, d M Y') }}
                        </div>
                        <div class="text-[11px] text-slate-400">
                            {{ __('messages.opened_by') }}: {{ $todayRegister->opener->name ?? 'Staff' }} ({{ $todayRegister->opened_at->format('h:i A') }})
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-2 flex-wrap">
                    @if($todayRegister->isOpen())
                        <!-- Quick Action 1: Record Drawer Expense -->
                        <button type="button" @click="showExpenseModal = true"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-amber-500 hover:bg-amber-600 text-white font-bold text-xs shadow-sm transition">
                            <span>💸 {{ __('messages.record_drawer_expense') }}</span>
                        </button>

                        <!-- Quick Action 2: Transfer Cash to Owner -->
                        <button type="button" @click="showTransferModal = true"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs shadow-sm transition">
                            <span>🏦 {{ __('messages.remit_cash_to_owner') }}</span>
                        </button>

                        <!-- Quick Action 3: Toggle Evening Close Form -->
                        <button type="button" @click="showClosingForm = !showClosingForm"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-slate-300 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-200 font-bold text-xs transition">
                            <span>🔒 {{ __('messages.close_drawer_btn') }}</span>
                        </button>
                    @elseif(auth()->user()->isStoreManager() || auth()->user()->isSuperAdmin())
                        <!-- Reopen Form -->
                        <form method="POST" action="{{ route('daily-registers.reopen', $todayRegister) }}" class="flex items-center gap-2">
                            @csrf
                            <input type="text" name="reopen_reason" required placeholder="{{ __('messages.reopen_reason_placeholder') }}"
                                   class="text-xs rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white px-3 py-1.5 w-52">
                            <button type="submit" class="px-3 py-1.5 rounded-xl bg-amber-600 hover:bg-amber-700 text-white font-bold text-xs shadow-sm transition whitespace-nowrap">
                                {{ __('messages.reopen_day') }}
                            </button>
                        </form>
                    @endif
                </div>
            </div>

            <!-- Focal Cash In Drawer Display -->
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 py-2">
                <div>
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">
                        {{ __('messages.current_drawer_cash') }}
                    </span>
                    <div class="text-3xl sm:text-4xl font-black font-mono tracking-tight text-indigo-600 dark:text-indigo-400 mt-1">
                        ৳{{ number_format($todayRegister->expected_closing_cash, 2) }}
                    </div>
                </div>

                <!-- Minimal 5-Part Cash Breakdown Strip -->
                <div class="grid grid-cols-2 sm:grid-cols-5 gap-2 text-xs font-mono w-full md:w-auto">
                    <div class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-800 text-center">
                        <span class="text-[10px] text-slate-400 block">{{ __('messages.morning_float') }}</span>
                        <strong class="text-slate-800 dark:text-slate-200 font-bold">৳{{ number_format($todayRegister->opening_cash, 2) }}</strong>
                    </div>
                    <div class="p-2.5 rounded-xl bg-emerald-50/60 dark:bg-emerald-950/30 border border-emerald-100 dark:border-emerald-900/50 text-center">
                        <span class="text-[10px] text-emerald-600 dark:text-emerald-400 block">{{ __('messages.cash_sales') }}</span>
                        <strong class="text-emerald-600 dark:text-emerald-400 font-bold">+৳{{ number_format($todayRegister->cash_sales, 2) }}</strong>
                    </div>
                    <div class="p-2.5 rounded-xl bg-rose-50/60 dark:bg-rose-950/30 border border-rose-100 dark:border-rose-900/50 text-center">
                        <span class="text-[10px] text-rose-600 dark:text-rose-400 block">{{ __('messages.cash_refunds') }}</span>
                        <strong class="text-rose-600 dark:text-rose-400 font-bold">-৳{{ number_format($todayRegister->cash_refunds, 2) }}</strong>
                    </div>
                    <div class="p-2.5 rounded-xl bg-amber-50/60 dark:bg-amber-950/30 border border-amber-100 dark:border-amber-900/50 text-center">
                        <span class="text-[10px] text-amber-600 dark:text-amber-400 block">{{ __('messages.cash_expenses') }}</span>
                        <strong class="text-amber-600 dark:text-amber-400 font-bold">-৳{{ number_format($todayRegister->cash_expenses, 2) }}</strong>
                    </div>
                    <div class="p-2.5 rounded-xl bg-indigo-50/60 dark:bg-indigo-950/30 border border-indigo-100 dark:border-indigo-900/50 text-center col-span-2 sm:col-span-1">
                        <span class="text-[10px] text-indigo-600 dark:text-indigo-400 block">{{ __('messages.cash_remitted') }}</span>
                        <strong class="text-indigo-600 dark:text-indigo-400 font-bold">-৳{{ number_format($todayRegister->cash_transfers, 2) }}</strong>
                    </div>
                </div>
            </div>

            <!-- INLINE EVENING CLOSE FORM (COLLAPSIBLE / EXPANDABLE) -->
            @if($todayRegister->isOpen())
                <div x-show="showClosingForm" x-cloak
                     class="pt-4 border-t border-slate-100 dark:border-slate-800 bg-slate-50/60 dark:bg-slate-800/40 -mx-6 -mb-6 p-6 rounded-b-2xl space-y-3">
                    <div class="flex items-center justify-between">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-800 dark:text-slate-200">
                            {{ __('messages.close_drawer_btn') }}
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
                <div class="pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-xs text-slate-500">
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
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="p-6 text-center text-slate-400 font-sans">
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
