@extends('layouts.app')

@section('title', __('messages.stock_adjustments'))
@section('header', __('messages.stock_adjustments'))

@section('content')
<div class="space-y-6">

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold text-slate-900 dark:text-white">{{ __('messages.stock_adjustments') }}</h2>
            <p class="text-xs text-slate-500 mt-0.5">Formal Inventory Discrepancy Adjustments (Super Admin Approval Required)</p>
        </div>

        <a href="{{ route('stock-adjustments.create') }}" 
           class="inline-flex items-center gap-2 py-2.5 px-4 rounded-xl font-bold text-xs text-white bg-indigo-600 hover:bg-indigo-700 transition shadow-lg shadow-indigo-600/30">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            <span>+ Propose Stock Adjustment</span>
        </a>
    </div>

    <!-- Adjustments Table -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm overflow-hidden">
        
        <div class="p-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
            <form method="GET" action="{{ route('stock-adjustments.index') }}" class="flex items-center gap-2">
                <select name="status" onchange="this.form.submit()" class="text-xs rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 py-2 px-3">
                    <option value="">All Statuses</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending Approval</option>
                    <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
                    <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                </select>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-slate-200 dark:border-slate-800 text-slate-400 uppercase text-[10px] tracking-wider font-bold">
                        <th class="py-3.5 px-4">Date</th>
                        <th class="py-3.5 px-4">Store Location</th>
                        <th class="py-3.5 px-4">Product Variant</th>
                        <th class="py-3.5 px-4 text-right">Old Qty</th>
                        <th class="py-3.5 px-4 text-right">Proposed Qty</th>
                        <th class="py-3.5 px-4 text-right">Adjustment Diff</th>
                        <th class="py-3.5 px-4">Mandatory Reason</th>
                        <th class="py-3.5 px-4">Status</th>
                        <th class="py-3.5 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800 font-medium">
                    @forelse($adjustments as $adj)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition">
                            <td class="py-3.5 px-4 font-mono text-[11px] text-slate-500 whitespace-nowrap">
                                @posDate($adj->created_at)
                            </td>
                            <td class="py-3.5 px-4 font-bold text-slate-900 dark:text-white">
                                {{ $adj->store->name }}
                            </td>
                            <td class="py-3.5 px-4">
                                <div class="font-bold text-slate-900 dark:text-white">{{ $adj->variant->product->name }}</div>
                                <div class="text-[11px] text-slate-500 font-mono">
                                    {{ $adj->variant->color }} - {{ $adj->variant->size }} • {{ $adj->variant->barcode }}
                                </div>
                            </td>
                            <td class="py-3.5 px-4 text-right font-mono text-slate-500 font-bold">
                                {{ $adj->old_quantity }}
                            </td>
                            <td class="py-3.5 px-4 text-right font-mono font-black text-slate-900 dark:text-white">
                                {{ $adj->proposed_quantity }}
                            </td>
                            <td class="py-3.5 px-4 text-right font-mono font-black {{ $adj->adjustment_quantity > 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                                {{ $adj->adjustment_quantity > 0 ? '+' . $adj->adjustment_quantity : $adj->adjustment_quantity }}
                            </td>
                            <td class="py-3.5 px-4 text-slate-700 dark:text-slate-300 max-w-xs">
                                <div>{{ $adj->reason }}</div>
                                <div class="text-[10px] text-slate-400">By: {{ $adj->requester->name }}</div>
                            </td>
                            <td class="py-3.5 px-4">
                                @if($adj->status->value === 'pending')
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300 animate-pulse">
                                        PENDING APPROVAL
                                    </span>
                                @elseif($adj->status->value === 'approved')
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">
                                        APPROVED
                                    </span>
                                    <div class="text-[10px] text-slate-400 mt-0.5">By {{ $adj->approver?->name }}</div>
                                @else
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300">
                                        REJECTED
                                    </span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 text-right">
                                @if($adj->status->value === 'pending' && auth()->user()->isSuperAdmin())
                                    <div class="flex items-center justify-end gap-1.5">
                                        <form action="{{ route('stock-adjustments.approve', $adj) }}" method="POST" onsubmit="return confirm('Approve this stock adjustment and update actual ledger balance?')">
                                            @csrf
                                            <button type="submit" class="py-1 px-2.5 rounded-lg bg-emerald-600 text-white font-bold text-[11px] hover:bg-emerald-700 shadow-sm">
                                                Approve
                                            </button>
                                        </form>

                                        <form action="{{ route('stock-adjustments.reject', $adj) }}" method="POST" onsubmit="return confirm('Reject this adjustment?')">
                                            @csrf
                                            <input type="hidden" name="rejection_reason" value="Rejected by Super Admin after review">
                                            <button type="submit" class="py-1 px-2.5 rounded-lg bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold text-[11px] hover:bg-rose-100 hover:text-rose-600">
                                                Reject
                                            </button>
                                        </form>
                                    </div>
                                @else
                                    <span class="text-slate-400 text-[11px]">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="py-8 text-center text-slate-400">No stock adjustment requests found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t border-slate-100 dark:border-slate-800">
            {{ $adjustments->links() }}
        </div>
    </div>

</div>
@endsection
