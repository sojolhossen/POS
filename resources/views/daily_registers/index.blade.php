@extends('layouts.app')

@section('title', __('messages.daily_cash_register') . ' - Drawer & Remittance Control')
@section('header', __('messages.daily_cash_register'))

@section('content')
<div class="space-y-6" x-data="{ showExpenseModal: false, showTransferModal: false, showRejectModal: false, rejectActionUrl: '', rejectTransferNumber: '', rejectTransferAmount: '' }">
    <!-- TOP HEADER & CONTROLS -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-black text-slate-900 dark:text-white flex items-center gap-2">
                <span>{{ __('messages.daily_cash_register') }}</span>
                <span class="text-xs px-2.5 py-0.5 rounded-full bg-indigo-100 text-indigo-700 dark:bg-indigo-900/60 dark:text-indigo-300 font-bold">
                    Drawer Continuity & Remittance
                </span>
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                Automated opening float carryover, live sales accumulation, petty cash expense tracking, and verified remittance to store owner.
            </p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('pos.index') }}" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-slate-800 dark:bg-slate-700 hover:bg-slate-900 text-white font-bold text-xs shadow-md transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                <span>Go to POS Checkout</span>
            </a>
        </div>
    </div>

    <!-- STORE SELECTOR TABS (IF MULTIPLE STORES ACCESSIBLE) -->
    @if(isset($accessibleStores) && $accessibleStores->count() > 1)
        <div class="flex items-center gap-2 overflow-x-auto pb-1 border-b border-slate-200 dark:border-slate-800 text-xs">
            <span class="text-slate-400 font-bold uppercase tracking-wider text-[10px] whitespace-nowrap mr-1">Store:</span>
            @foreach($accessibleStores as $st)
                <a href="{{ route('daily-registers.index', ['store_id' => $st->id]) }}"
                   class="px-3 py-1.5 rounded-lg font-bold transition whitespace-nowrap {{ ($currentStore && $currentStore->id === $st->id) ? 'bg-indigo-600 text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700' }}">
                    {{ $st->name }}
                </a>
            @endforeach
        </div>
    @endif

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
                        @if($todayRegister->opening_discrepancy_amount != 0)
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300" title="Opening float differed from previous closing">
                                Float Variance: {{ $todayRegister->opening_discrepancy_amount > 0 ? '+' : '' }}৳{{ number_format($todayRegister->opening_discrepancy_amount, 2) }}
                            </span>
                        @endif
                    </div>
                    <p class="text-xs text-slate-500 mt-1">
                        Store: <strong>{{ $todayRegister->store->name }}</strong> • Opened by {{ $todayRegister->opener->name ?? 'Staff' }} at {{ $todayRegister->opened_at->format('h:i A') }}
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    @if($todayRegister->isOpen())
                        <!-- QUICK ACTION 1: RECORD DRAWER EXPENSE -->
                        <button type="button" @click="showExpenseModal = true"
                                class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-amber-500 hover:bg-amber-600 text-white font-bold text-xs shadow-sm transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                            <span>💸 Record Drawer Expense</span>
                        </button>

                        <!-- QUICK ACTION 2: TRANSFER CASH TO OWNER -->
                        <button type="button" @click="showTransferModal = true"
                                class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs shadow-sm transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                            <span>🏦 Remit Cash to Owner / Bank</span>
                        </button>
                    @elseif(auth()->user()->isStoreManager() || auth()->user()->isSuperAdmin())
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
            </div>

            <!-- Complete Cash Flow Breakdown (5 Pillars) -->
            <div class="grid grid-cols-2 sm:grid-cols-6 gap-3 text-xs font-mono">
                <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/40 border border-slate-100 dark:border-slate-800">
                    <span class="text-[10px] uppercase font-bold text-slate-400">1. Opening Float</span>
                    <div class="text-lg font-black text-slate-900 dark:text-white mt-1">
                        ৳{{ number_format($todayRegister->opening_cash, 2) }}
                    </div>
                </div>

                <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/40 border border-slate-100 dark:border-slate-800">
                    <span class="text-[10px] uppercase font-bold text-slate-400">2. Cash Sales (+)</span>
                    <div class="text-lg font-black text-emerald-600 dark:text-emerald-400 mt-1">
                        +৳{{ number_format($todayRegister->cash_sales, 2) }}
                    </div>
                </div>

                <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/40 border border-slate-100 dark:border-slate-800">
                    <span class="text-[10px] uppercase font-bold text-slate-400">3. Cash Refunds (-)</span>
                    <div class="text-lg font-black text-rose-600 dark:text-rose-400 mt-1">
                        -৳{{ number_format($todayRegister->cash_refunds, 2) }}
                    </div>
                </div>

                <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/40 border border-slate-100 dark:border-slate-800">
                    <span class="text-[10px] uppercase font-bold text-amber-600 dark:text-amber-400">4. Expenses (-)</span>
                    <div class="text-lg font-black text-amber-600 dark:text-amber-400 mt-1">
                        -৳{{ number_format($todayRegister->cash_expenses, 2) }}
                    </div>
                </div>

                <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/40 border border-slate-100 dark:border-slate-800">
                    <span class="text-[10px] uppercase font-bold text-indigo-600 dark:text-indigo-400">5. Remitted (-)</span>
                    <div class="text-lg font-black text-indigo-600 dark:text-indigo-400 mt-1">
                        -৳{{ number_format($todayRegister->cash_transfers, 2) }}
                    </div>
                </div>

                <div class="p-3.5 rounded-xl bg-indigo-50/80 dark:bg-indigo-950/60 border border-indigo-200 dark:border-indigo-800">
                    <span class="text-[10px] uppercase font-bold text-indigo-700 dark:text-indigo-300">Live Drawer Cash</span>
                    <div class="text-lg font-black text-indigo-800 dark:text-indigo-200 mt-1">
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
        <!-- OPEN REGISTER CARD (AUTOMATED PREVIOUS-DAY CONTINUITY) -->
        <div class="bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 rounded-2xl p-6 text-white shadow-xl border border-slate-800 space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-400 text-slate-950 uppercase tracking-wider">
                        Action Required: Morning Register Opening
                    </span>
                    <h2 class="text-lg font-black mt-2">Open Daily Cash Register for {{ now()->format('l, d F Y') }}</h2>
                    <p class="text-xs text-slate-300 mt-1 max-w-xl">
                        Store: <strong class="text-white">{{ $currentStore->name ?? 'Retail Store' }}</strong>
                    </p>
                </div>

                @if($suggestedOpening && $suggestedOpening['has_previous'])
                    <!-- PREVIOUS DAY CLOSING BALANCE PILL -->
                    <div class="bg-white/10 backdrop-blur-md rounded-xl p-3 border border-white/20 text-right">
                        <span class="text-[10px] uppercase font-bold text-indigo-300 block">Previous Closing Balance ({{ $suggestedOpening['previous_date'] }}):</span>
                        <span class="text-xl font-black text-emerald-400 font-mono">
                            ৳{{ number_format($suggestedOpening['suggested_amount'], 2) }}
                        </span>
                        <p class="text-[10px] text-slate-300 mt-0.5">Automated continuity carryover</p>
                    </div>
                @else
                    <!-- FIRST-TIME OPENING PILL -->
                    <div class="bg-white/10 backdrop-blur-md rounded-xl p-3 border border-white/20 text-right">
                        <span class="text-[10px] uppercase font-bold text-amber-300 block">Store Initial Setup:</span>
                        <span class="text-sm font-bold text-white">First-time Store Opening Float</span>
                    </div>
                @endif
            </div>

            <form method="POST" action="{{ route('daily-registers.open') }}" class="space-y-4 pt-2">
                @csrf
                <input type="hidden" name="store_id" value="{{ $currentStore->id ?? 1 }}">

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 items-end">
                    <div>
                        <label class="block text-xs font-bold text-indigo-300 mb-1">
                            Opening Drawer Cash Float (৳) <span class="text-rose-400">*</span>
                        </label>
                        <input type="number" step="0.01" min="0" name="opening_cash" required
                               value="{{ old('opening_cash', ($suggestedOpening && $suggestedOpening['has_previous']) ? number_format($suggestedOpening['suggested_amount'], 2, '.', '') : '3000.00') }}"
                               class="w-full text-xs rounded-xl border-white/20 bg-white/10 text-white placeholder-slate-400 px-3.5 py-2.5 font-mono font-bold focus:ring-2 focus:ring-emerald-400 focus:outline-none">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-indigo-300 mb-1">
                            Variance Reason (If Opening Float ≠ Previous Closing)
                        </label>
                        <input type="text" name="opening_discrepancy_reason" placeholder="e.g. Added ৳1000 petty cash float by owner"
                               class="w-full text-xs rounded-xl border-white/20 bg-white/10 text-white placeholder-slate-400 px-3.5 py-2.5 text-xs focus:ring-2 focus:ring-emerald-400 focus:outline-none">
                    </div>

                    <button type="submit" class="w-full px-6 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-md transition whitespace-nowrap">
                        Confirm Opening Float & Start Business Day
                    </button>
                </div>
            </form>
        </div>
    @endif

    <!-- CASH REMITTANCES TO STORE OWNER / BANK (ADMIN VERIFICATION QUEUE) -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
        <div class="p-4 border-b border-slate-100 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h2 class="text-sm font-black text-slate-900 dark:text-white flex items-center gap-2">
                    <span>🏦 Cash Remittances to Store Owner / Bank</span>
                    <span class="text-[10px] px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300 font-bold font-mono">
                        Verification & Fraud Guard
                    </span>
                </h2>
                <p class="text-xs text-slate-500 mt-0.5">
                    Transferred cash is immediately deducted from drawer. If rejected by Super Admin, funds are automatically restored back to store balance.
                </p>
            </div>
            @if($todayRegister && $todayRegister->isOpen())
                <button type="button" @click="showTransferModal = true"
                        class="px-3.5 py-1.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs shadow-sm transition">
                    + New Cash Transfer
                </button>
            @endif
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-800/60 border-b border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-300 font-bold">
                        <th class="p-3">Transfer #</th>
                        <th class="p-3">Date & Time</th>
                        <th class="p-3">Store</th>
                        <th class="p-3 text-right">Amount</th>
                        <th class="p-3">Method / Destination</th>
                        <th class="p-3">Transferred By</th>
                        <th class="p-3">Reference / Slip</th>
                        <th class="p-3 text-center">Status</th>
                        <th class="p-3 text-right">Admin Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800 font-mono">
                    @forelse($transfers as $trf)
                        <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition">
                            <td class="p-3 font-bold text-indigo-600 dark:text-indigo-400">
                                {{ $trf->transfer_number }}
                            </td>
                            <td class="p-3 text-slate-500 font-sans text-[11px]">
                                {{ $trf->transferred_at ? $trf->transferred_at->format('d M Y, h:i A') : '—' }}
                            </td>
                            <td class="p-3 font-sans font-medium text-slate-700 dark:text-slate-300">
                                {{ $trf->store->name }}
                            </td>
                            <td class="p-3 text-right font-black text-slate-900 dark:text-white text-sm">
                                ৳{{ number_format($trf->amount, 2) }}
                            </td>
                            <td class="p-3 font-sans">
                                <span class="capitalize font-bold text-slate-700 dark:text-slate-200">{{ str_replace('_', ' ', $trf->transfer_method) }}</span>
                                @if($trf->financialAccount)
                                    <div class="text-[10px] text-slate-400">To: {{ $trf->financialAccount->name }}</div>
                                @elseif($trf->recipient_name)
                                    <div class="text-[10px] text-slate-400">To: {{ $trf->recipient_name }}</div>
                                @endif
                            </td>
                            <td class="p-3 font-sans text-slate-600 dark:text-slate-300">
                                {{ $trf->transferredBy->name ?? 'Manager' }}
                            </td>
                            <td class="p-3 font-sans text-slate-500 text-[11px]">
                                <div>{{ $trf->reference_number ?? '—' }}</div>
                                @if($trf->notes)
                                    <div class="text-[10px] text-slate-400 truncate max-w-xs">{{ $trf->notes }}</div>
                                @endif
                                @if($trf->attachment_path)
                                    <a href="{{ asset('storage/' . $trf->attachment_path) }}" target="_blank" class="text-indigo-600 dark:text-indigo-400 font-bold text-[10px] underline">View Slip</a>
                                @endif
                            </td>
                            <td class="p-3 text-center font-sans">
                                @if($trf->status === 'approved')
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300">
                                        Approved
                                    </span>
                                    <div class="text-[9px] text-slate-400 mt-0.5">by {{ $trf->reviewedBy->name ?? 'Admin' }}</div>
                                @elseif($trf->status === 'rejected')
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-300">
                                        Rejected & Re-credited
                                    </span>
                                    @if($trf->rejection_reason)
                                        <div class="text-[9px] text-rose-500 mt-0.5 max-w-xs truncate" title="{{ $trf->rejection_reason }}">{{ $trf->rejection_reason }}</div>
                                    @endif
                                @else
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300 animate-pulse">
                                        Pending Admin Verification
                                    </span>
                                @endif
                            </td>
                            <td class="p-3 text-right font-sans">
                                @if($trf->status === 'pending' && auth()->user()->isSuperAdmin())
                                    <div class="flex items-center justify-end gap-1.5">
                                        <form method="POST" action="{{ route('daily-registers.transfers.approve', $trf) }}" onsubmit="return confirm('Approve this cash transfer of ৳{{ number_format($trf->amount, 2) }}?');">
                                            @csrf
                                            <button type="submit" class="px-2.5 py-1 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-[11px] shadow-sm transition">
                                                Approve
                                            </button>
                                        </form>

                                        <button type="button"
                                                @click="rejectActionUrl = '{{ route('daily-registers.transfers.reject', $trf) }}'; rejectTransferNumber = '{{ $trf->transfer_number }}'; rejectTransferAmount = '{{ number_format($trf->amount, 2) }}'; showRejectModal = true;"
                                                class="px-2.5 py-1 rounded-lg bg-rose-600 hover:bg-rose-700 text-white font-bold text-[11px] shadow-sm transition">
                                            Reject
                                        </button>
                                    </div>
                                @elseif($trf->status === 'pending')
                                    <span class="text-[11px] text-slate-400 italic">Awaiting Admin</span>
                                @else
                                    <span class="text-[11px] text-slate-400">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="p-6 text-center text-slate-400 font-sans">
                                No cash remittances logged yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($transfers->hasPages())
            <div class="p-3 border-t border-slate-200 dark:border-slate-800">
                {{ $transfers->links() }}
            </div>
        @endif
    </div>

    <!-- HISTORICAL CASH REGISTERS LEDGER -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
        <div class="p-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-200">
                Daily Cash Register Closing Audit Ledger
            </h2>
            <span class="text-xs text-slate-400 font-mono">{{ $registers->total() }} Total Register Records</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-800/60 border-b border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-300 font-bold">
                        <th class="p-3">Business Date</th>
                        <th class="p-3">Store</th>
                        <th class="p-3 text-right">Opening Float</th>
                        <th class="p-3 text-right text-emerald-600">Sales (+)</th>
                        <th class="p-3 text-right text-rose-600">Refunds (-)</th>
                        <th class="p-3 text-right text-amber-600">Expenses (-)</th>
                        <th class="p-3 text-right text-indigo-600">Remitted (-)</th>
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
                                    {{ $reg->isOpen() ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300' : 'bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-300' }}">
                                    {{ $reg->status }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="p-6 text-center text-slate-400 font-sans">
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

    <!-- MODAL 1: RECORD DRAWER EXPENSE -->
    <div x-show="showExpenseModal" x-cloak
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white dark:bg-slate-900 rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-slate-200 dark:border-slate-800 space-y-4"
             @click.away="showExpenseModal = false">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                <h3 class="text-sm font-black text-slate-900 dark:text-white flex items-center gap-2">
                    <span>💸 Record Drawer Petty Cash Expense</span>
                </h3>
                <button type="button" @click="showExpenseModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-white">✕</button>
            </div>

            <form method="POST" action="{{ route('daily-registers.record-expense') }}" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <input type="hidden" name="store_id" value="{{ $currentStore->id ?? 1 }}">

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Expense Category <span class="text-rose-500">*</span></label>
                    <select name="expense_category_id" required class="w-full text-xs rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white px-3 py-2.5">
                        <option value="">Select Category...</option>
                        @foreach($expenseCategories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Expense Amount (৳) <span class="text-rose-500">*</span></label>
                        <input type="number" step="0.01" min="0.01" max="{{ $todayRegister ? $todayRegister->expected_closing_cash : 999999 }}" name="amount" required placeholder="0.00"
                               class="w-full text-xs rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white px-3 py-2.5 font-mono font-bold">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Expense Title / Item <span class="text-rose-500">*</span></label>
                        <input type="text" name="title" required placeholder="e.g. Staff tea & snacks"
                               class="w-full text-xs rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white px-3 py-2.5">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Description / Voucher Note</label>
                    <textarea name="description" rows="2" placeholder="Optional details..."
                              class="w-full text-xs rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white px-3 py-2"></textarea>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Attach Receipt / Voucher (Optional)</label>
                    <input type="file" name="receipt" accept="image/*,application/pdf"
                           class="w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200 dark:file:bg-slate-800 dark:file:text-slate-300">
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" @click="showExpenseModal = false" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800">Cancel</button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-amber-600 hover:bg-amber-700 text-white font-bold text-xs shadow-md transition">
                        Deduct from Drawer & Record Expense
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 2: TRANSFER CASH TO OWNER / BANK -->
    <div x-show="showTransferModal" x-cloak
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white dark:bg-slate-900 rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-slate-200 dark:border-slate-800 space-y-4"
             @click.away="showTransferModal = false">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                <h3 class="text-sm font-black text-slate-900 dark:text-white flex items-center gap-2">
                    <span>🏦 Remit / Transfer Cash to Owner or Bank</span>
                </h3>
                <button type="button" @click="showTransferModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-white">✕</button>
            </div>

            <form method="POST" action="{{ route('daily-registers.transfer-cash') }}" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <input type="hidden" name="store_id" value="{{ $currentStore->id ?? 1 }}">

                <div class="p-3 rounded-xl bg-indigo-50 dark:bg-indigo-950/40 border border-indigo-100 dark:border-indigo-900 text-xs">
                    <span class="text-indigo-600 dark:text-indigo-300 font-bold block">Available Cash Drawer Balance:</span>
                    <span class="text-lg font-black text-indigo-700 dark:text-indigo-200 font-mono">
                        ৳{{ number_format($todayRegister ? $todayRegister->expected_closing_cash : 0, 2) }}
                    </span>
                    <p class="text-[10px] text-slate-500 mt-0.5">This amount will be immediately deducted from the store balance upon transfer.</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Transfer Amount (৳) <span class="text-rose-500">*</span></label>
                        <input type="number" step="0.01" min="0.01" max="{{ $todayRegister ? $todayRegister->expected_closing_cash : 999999 }}" name="amount" required placeholder="0.00"
                               class="w-full text-xs rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white px-3 py-2.5 font-mono font-bold">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Transfer Method <span class="text-rose-500">*</span></label>
                        <select name="transfer_method" required class="w-full text-xs rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white px-3 py-2.5">
                            <option value="cash_to_owner">Cash Handover to Owner</option>
                            <option value="bank_deposit">Bank Deposit</option>
                            <option value="bkash">bKash</option>
                            <option value="nagad">Nagad</option>
                            <option value="rocket">Rocket</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Destination Financial Account</label>
                        <select name="financial_account_id" class="w-full text-xs rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white px-3 py-2.5">
                            <option value="">None / Handover directly</option>
                            @foreach($financialAccounts as $acc)
                                <option value="{{ $acc->id }}">{{ $acc->name }} ({{ ucfirst($acc->account_type) }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Recipient Person / Account</label>
                        <input type="text" name="recipient_name" placeholder="e.g. Mr. Tanvir (Store Owner)"
                               class="w-full text-xs rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white px-3 py-2.5">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Slip / Ref / TrxID</label>
                        <input type="text" name="reference_number" placeholder="e.g. Deposit Slip #12345"
                               class="w-full text-xs rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white px-3 py-2.5">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Attach Deposit Slip (Photo)</label>
                        <input type="file" name="attachment" accept="image/*,application/pdf"
                               class="w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200 dark:file:bg-slate-800 dark:file:text-slate-300">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Notes / Remarks</label>
                    <textarea name="notes" rows="2" placeholder="Optional notes for Super Admin..."
                              class="w-full text-xs rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white px-3 py-2"></textarea>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" @click="showTransferModal = false" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800">Cancel</button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs shadow-md transition">
                        Submit Transfer & Deduct Drawer Cash
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 3: SUPER ADMIN REJECT TRANSFER (RE-CREDITS MONEY) -->
    <div x-show="showRejectModal" x-cloak
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white dark:bg-slate-900 rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-200 dark:border-slate-800 space-y-4"
             @click.away="showRejectModal = false">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                <h3 class="text-sm font-black text-rose-600 dark:text-rose-400 flex items-center gap-2">
                    <span>Reject Cash Transfer & Refund Drawer</span>
                </h3>
                <button type="button" @click="showRejectModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-white">✕</button>
            </div>

            <div class="p-3 rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-100 dark:border-rose-900 text-xs text-rose-800 dark:text-rose-300">
                <p>Transfer Ref: <strong class="font-mono" x-text="rejectTransferNumber"></strong></p>
                <p>Amount: <strong class="font-mono text-sm" x-text="'৳' + rejectTransferAmount"></strong></p>
                <p class="mt-1 font-semibold">
                    ⚠️ By rejecting, this cash will be immediately refunded back to the store cash register balance!
                </p>
            </div>

            <form :action="rejectActionUrl" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Mandatory Rejection Reason <span class="text-rose-500">*</span></label>
                    <textarea name="rejection_reason" required rows="3" placeholder="e.g. Cash was not received by owner, or deposit slip is unverified..."
                              class="w-full text-xs rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white px-3 py-2"></textarea>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" @click="showRejectModal = false" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800">Cancel</button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs shadow-md transition">
                        Confirm Rejection & Refund Drawer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
