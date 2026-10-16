@extends('layouts.app')

@section('title', 'Expense Management')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white dark:bg-slate-800 p-6 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">Expense Management</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Track store overheads, utilities, salaries, and operational costs with approval controls</p>
        </div>
        <div>
            <a href="{{ route('expenses.create') }}" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-medium text-sm transition shadow-sm flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Record Expense
            </a>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="bg-white dark:bg-slate-800 p-4 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700">
        <form method="GET" action="{{ route('expenses.index') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-4">
            @if(auth()->user()->isSuperAdmin() || auth()->user()->isAccountant())
            <div>
                <select name="store_id" class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-3 py-2 text-sm text-slate-900 dark:text-white">
                    <option value="">All Stores</option>
                    @foreach($stores as $st)
                    <option value="{{ $st->id }}" {{ request('store_id') == $st->id ? 'selected' : '' }}>{{ $st->name }}</option>
                    @endforeach
                </select>
            </div>
            @endif
            <div>
                <select name="category_id" class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-3 py-2 text-sm text-slate-900 dark:text-white">
                    <option value="">All Categories</option>
                    @foreach($categories as $cat)
                    <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <select name="status" class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-3 py-2 text-sm text-slate-900 dark:text-white">
                    <option value="">All Statuses</option>
                    <option value="pending_approval" {{ request('status') === 'pending_approval' ? 'selected' : '' }}>Pending Approval</option>
                    <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
                    <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>Paid</option>
                    <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                </select>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 text-slate-800 dark:text-white rounded-xl text-sm font-medium w-full">Filter</button>
                <a href="{{ route('expenses.index') }}" class="px-3 py-2 text-slate-500 hover:text-slate-700 text-sm flex items-center">Reset</a>
            </div>
        </form>
    </div>

    <!-- Expenses Table -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600 dark:text-slate-300">
                <thead class="bg-slate-50 dark:bg-slate-900/40 text-xs uppercase tracking-wider text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-700">
                    <tr>
                        <th class="px-6 py-4">Expense ID</th>
                        <th class="px-6 py-4">Store</th>
                        <th class="px-6 py-4">Category & Title</th>
                        <th class="px-6 py-4">Date</th>
                        <th class="px-6 py-4 text-right">Amount</th>
                        <th class="px-6 py-4">Payment Method</th>
                        <th class="px-6 py-4 text-center">Status</th>
                        <th class="px-6 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                    @forelse($expenses as $exp)
                    <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/50 transition">
                        <td class="px-6 py-4 font-mono text-xs font-bold text-slate-900 dark:text-white">{{ $exp->expense_number }}</td>
                        <td class="px-6 py-4 text-xs font-semibold text-slate-700 dark:text-slate-300">{{ $exp->store->name }}</td>
                        <td class="px-6 py-4">
                            <span class="text-xs px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 font-medium">{{ $exp->category->name }}</span>
                            <div class="font-bold text-slate-900 dark:text-white mt-1">{{ $exp->title }}</div>
                            @if($exp->description)<div class="text-xs text-slate-400 truncate max-w-xs">{{ $exp->description }}</div>@endif
                        </td>
                        <td class="px-6 py-4 text-xs text-slate-500">{{ $exp->expense_date->format('d M Y') }}</td>
                        <td class="px-6 py-4 text-right font-black text-slate-900 dark:text-white">৳{{ number_format($exp->amount, 2) }}</td>
                        <td class="px-6 py-4 text-xs capitalize text-slate-600 dark:text-slate-400">{{ str_replace('_', ' ', $exp->payment_method) }}</td>
                        <td class="px-6 py-4 text-center">
                            <span class="px-2.5 py-1 text-xs font-semibold rounded-full
                                @if($exp->status === 'paid') bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400
                                @elseif($exp->status === 'approved') bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400
                                @elseif($exp->status === 'pending_approval') bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400
                                @else bg-rose-100 text-rose-800 dark:bg-rose-900/30 dark:text-rose-400 @endif">
                                {{ str_replace('_', ' ', $exp->status) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                @if($exp->status === 'pending_approval')
                                    <form action="{{ route('expenses.approve', $exp) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="px-3 py-1 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-medium transition shadow-sm">
                                            Approve
                                        </button>
                                    </form>
                                    <button type="button" onclick="openRejectModal('{{ $exp->id }}', '{{ $exp->expense_number }}')" class="px-3 py-1 bg-rose-600 hover:bg-rose-700 text-white rounded-lg text-xs font-medium transition shadow-sm">
                                        Reject
                                    </button>
                                @elseif($exp->status === 'approved')
                                    <form action="{{ route('expenses.pay', $exp) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="px-3 py-1 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-medium transition shadow-sm">
                                            Execute Payout
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center text-slate-400">No expenses found matching the criteria.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-slate-200 dark:border-slate-700">
            {{ $expenses->links() }}
        </div>
    </div>
</div>

<!-- Rejection Modal -->
<div id="rejectModal" class="hidden fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white dark:bg-slate-800 rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-200 dark:border-slate-700">
        <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-2">Reject Expense <span id="rejectExpNumber" class="font-mono text-indigo-600"></span></h3>
        <p class="text-xs text-slate-500 mb-4">A documented reason is mandatory when rejecting expenses.</p>
        <form id="rejectForm" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 mb-1">Rejection Reason</label>
                <textarea name="rejection_reason" required rows="3" placeholder="Provide reason for rejecting..." class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-3 py-2 text-sm text-slate-900 dark:text-white"></textarea>
            </div>
            <div class="flex justify-end gap-3 pt-2">
                <button type="button" onclick="document.getElementById('rejectModal').classList.add('hidden')" class="px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-xl text-sm font-medium text-slate-700 dark:text-slate-300">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-sm font-medium shadow-sm">Confirm Rejection</button>
            </div>
        </form>
    </div>
</div>

<script>
function openRejectModal(id, number) {
    document.getElementById('rejectExpNumber').textContent = number;
    document.getElementById('rejectForm').action = '/expenses/' + id + '/reject';
    document.getElementById('rejectModal').classList.remove('hidden');
}
</script>
@endsection
