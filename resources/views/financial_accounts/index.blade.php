@extends('layouts.app')

@section('title', 'Financial Accounts & Ledgers')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white dark:bg-slate-800 p-6 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">Chart of Accounts & Ledgers</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Cash, Bank, Mobile Banking (bKash/Nagad/Rocket), and Settlement Balances</p>
        </div>
        <div class="flex items-center gap-3">
            <button onclick="document.getElementById('transferModal').classList.remove('hidden')" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-medium text-sm transition shadow-sm flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                Inter-Account Transfer
            </button>
            <button onclick="document.getElementById('createAccountModal').classList.remove('hidden')" class="px-4 py-2 bg-slate-900 hover:bg-slate-800 dark:bg-white dark:hover:bg-slate-100 text-white dark:text-slate-900 rounded-xl font-medium text-sm transition shadow-sm flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                New Account
            </button>
        </div>
    </div>

    <!-- Accounts Cards Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        @foreach($accounts as $acc)
        <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 relative overflow-hidden">
            <div class="flex items-center justify-between mb-4">
                <span class="px-2.5 py-1 text-xs font-semibold rounded-lg uppercase tracking-wider
                    @if($acc->account_type === 'cash') bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400
                    @elseif(str_contains($acc->account_type, 'bkash')) bg-pink-100 text-pink-800 dark:bg-pink-900/30 dark:text-pink-400
                    @elseif(str_contains($acc->account_type, 'nagad')) bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400
                    @else bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400 @endif">
                    {{ str_replace('_', ' ', $acc->account_type) }}
                </span>
                <span class="text-xs text-slate-400">{{ $acc->store ? $acc->store->name : 'Central HQ' }}</span>
            </div>
            <h3 class="text-lg font-bold text-slate-900 dark:text-white">{{ $acc->name }}</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">{{ $acc->account_number ?: 'Physical Drawer / Wallet' }}</p>
            <div class="mt-4 pt-4 border-t border-slate-100 dark:border-slate-700/60 flex items-baseline justify-between">
                <span class="text-xs text-slate-500">Current Balance</span>
                <span class="text-2xl font-black text-slate-900 dark:text-white">৳{{ number_format($acc->current_balance, 2) }}</span>
            </div>
        </div>
        @endforeach
    </div>

    <!-- Recent Transactions Ledger -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="p-6 border-b border-slate-200 dark:border-slate-700 flex justify-between items-center">
            <h2 class="text-lg font-bold text-slate-900 dark:text-white">Recent Account Movements & Reconciliation</h2>
            <span class="text-xs text-slate-500">Page {{ $recentTransactions->currentPage() }} of {{ $recentTransactions->lastPage() }}</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600 dark:text-slate-300">
                <thead class="bg-slate-50 dark:bg-slate-900/40 text-xs uppercase tracking-wider text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-700">
                    <tr>
                        <th class="px-6 py-4">Date & Time</th>
                        <th class="px-6 py-4">Account</th>
                        <th class="px-6 py-4">Type</th>
                        <th class="px-6 py-4">Reference</th>
                        <th class="px-6 py-4 text-right">Amount</th>
                        <th class="px-6 py-4 text-right">Fee</th>
                        <th class="px-6 py-4 text-right">Balance After</th>
                        <th class="px-6 py-4 text-center">Status</th>
                        <th class="px-6 py-4 text-center">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                    @forelse($recentTransactions as $tx)
                    <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/50 transition">
                        <td class="px-6 py-4 text-xs font-mono text-slate-500">{{ $tx->created_at->format('d M Y, h:i A') }}</td>
                        <td class="px-6 py-4 font-semibold text-slate-900 dark:text-white">{{ $tx->account->name }}</td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-0.5 rounded text-xs font-medium uppercase
                                @if($tx->amount >= 0) bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400
                                @else bg-rose-100 text-rose-800 dark:bg-rose-900/30 dark:text-rose-400 @endif">
                                {{ str_replace('_', ' ', $tx->transaction_type) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-xs">
                            <span class="font-mono font-medium text-slate-700 dark:text-slate-300">{{ $tx->reference_number ?: '-' }}</span>
                            <div class="text-slate-400 truncate max-w-xs">{{ $tx->description }}</div>
                        </td>
                        <td class="px-6 py-4 text-right font-bold {{ $tx->amount >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                            {{ $tx->amount >= 0 ? '+' : '' }}৳{{ number_format($tx->amount, 2) }}
                        </td>
                        <td class="px-6 py-4 text-right text-xs text-slate-400">
                            {{ $tx->fee_amount > 0 ? '৳' . number_format($tx->fee_amount, 2) : '-' }}
                        </td>
                        <td class="px-6 py-4 text-right font-mono font-bold text-slate-900 dark:text-white">
                            ৳{{ number_format($tx->balance_after, 2) }}
                        </td>
                        <td class="px-6 py-4 text-center">
                            @if($tx->reconciliation_status === 'reconciled')
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                    Reconciled
                                </span>
                            @else
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">Unreconciled</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-center">
                            @if($tx->reconciliation_status !== 'reconciled')
                            <form action="{{ route('financial-accounts.reconcile', $tx) }}" method="POST">
                                @csrf
                                <button type="submit" class="px-3 py-1 bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 rounded-lg text-xs font-medium transition">
                                    Reconcile
                                </button>
                            </form>
                            @else
                                <span class="text-xs text-slate-400">✓</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="px-6 py-12 text-center text-slate-400">No account transactions recorded yet.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-slate-200 dark:border-slate-700">
            {{ $recentTransactions->links() }}
        </div>
    </div>
</div>

<!-- Inter-Account Transfer Modal -->
<div id="transferModal" class="hidden fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white dark:bg-slate-800 rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-200 dark:border-slate-700">
        <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-4">Inter-Account Fund Transfer</h3>
        <form action="{{ route('financial-accounts.transfer') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 mb-1">Source Account</label>
                <select name="source_account_id" required class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-3 py-2 text-sm text-slate-900 dark:text-white">
                    @foreach($accounts as $a)
                    <option value="{{ $a->id }}">{{ $a->name }} (Avail: ৳{{ number_format($a->current_balance, 2) }})</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 mb-1">Destination Account</label>
                <select name="destination_account_id" required class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-3 py-2 text-sm text-slate-900 dark:text-white">
                    @foreach($accounts as $a)
                    <option value="{{ $a->id }}">{{ $a->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 mb-1">Transfer Amount (৳)</label>
                    <input type="number" step="0.01" min="1" name="amount" required class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-3 py-2 text-sm text-slate-900 dark:text-white font-mono">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 mb-1">Transfer Fee (৳)</label>
                    <input type="number" step="0.01" min="0" name="fee_amount" value="0.00" class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-3 py-2 text-sm text-slate-900 dark:text-white font-mono">
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 mb-1">Notes / Description</label>
                <input type="text" name="notes" placeholder="e.g. bKash cashout to City Bank" class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-3 py-2 text-sm text-slate-900 dark:text-white">
            </div>
            <div class="flex justify-end gap-3 pt-3">
                <button type="button" onclick="document.getElementById('transferModal').classList.add('hidden')" class="px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-xl text-sm font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-medium shadow-sm">Confirm Transfer</button>
            </div>
        </form>
    </div>
</div>

<!-- Create Account Modal -->
<div id="createAccountModal" class="hidden fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white dark:bg-slate-800 rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-200 dark:border-slate-700">
        <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-4">Create New Financial Account</h3>
        <form action="{{ route('financial-accounts.store') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 mb-1">Account Name</label>
                <input type="text" name="name" required placeholder="e.g. City Bank Main Operations" class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-3 py-2 text-sm text-slate-900 dark:text-white">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 mb-1">Account Type</label>
                    <select name="account_type" required class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-3 py-2 text-sm text-slate-900 dark:text-white">
                        <option value="bank">Bank Account</option>
                        <option value="cash">Cash in Hand</option>
                        <option value="bkash">bKash Merchant</option>
                        <option value="nagad">Nagad Wallet</option>
                        <option value="rocket">Rocket Wallet</option>
                        <option value="card_gateway">Card Settlement</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 mb-1">Store Scope</label>
                    <select name="store_id" class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-3 py-2 text-sm text-slate-900 dark:text-white">
                        <option value="">Central Company (HQ)</option>
                        @foreach($stores as $st)
                        <option value="{{ $st->id }}">{{ $st->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 mb-1">Account Number / IBAN</label>
                <input type="text" name="account_number" placeholder="e.g. 1501203920192001" class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-3 py-2 text-sm text-slate-900 dark:text-white">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 mb-1">Opening Balance (৳)</label>
                <input type="number" step="0.01" min="0" name="opening_balance" value="0.00" required class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-3 py-2 text-sm text-slate-900 dark:text-white font-mono">
            </div>
            <div class="flex justify-end gap-3 pt-3">
                <button type="button" onclick="document.getElementById('createAccountModal').classList.add('hidden')" class="px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-xl text-sm font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-slate-900 dark:bg-white text-white dark:text-slate-900 rounded-xl text-sm font-medium shadow-sm">Save Account</button>
            </div>
        </form>
    </div>
</div>
@endsection
