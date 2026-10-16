@extends('layouts.app')

@section('title', __('messages.supplier_returns') . ' - Management')
@section('header', __('messages.supplier_returns'))

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-black text-slate-900 dark:text-white flex items-center gap-2">
                <span>{{ __('messages.supplier_returns') }}</span>
                <span class="text-xs px-2.5 py-0.5 rounded-full bg-indigo-100 text-indigo-700 dark:bg-indigo-900/60 dark:text-indigo-300 font-bold">
                    Vendor Credit Notes
                </span>
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                Return damaged, defective, or excess garments back to manufacturers with credit note or refund adjustments.
            </p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('supplier-returns.create') }}" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs shadow-md shadow-indigo-600/20 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span>New Supplier Return</span>
            </a>
        </div>
    </div>

    <!-- Returns Table -->
    <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-800/60 border-b border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-300 font-bold">
                        <th class="p-3">Return Debit Note #</th>
                        <th class="p-3">Vendor / Supplier</th>
                        <th class="p-3">Dispatched Location</th>
                        <th class="p-3">Date</th>
                        <th class="p-3">Settlement Rail</th>
                        <th class="p-3 text-right">Credit / Refund Value</th>
                        <th class="p-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($returns as $ret)
                        <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition">
                            <td class="p-3 font-mono font-bold text-indigo-600 dark:text-indigo-400">
                                <a href="{{ route('supplier-returns.show', $ret) }}" class="hover:underline">
                                    {{ $ret->return_number }}
                                </a>
                            </td>
                            <td class="p-3 font-semibold text-slate-900 dark:text-white">
                                {{ $ret->supplier->name }}
                            </td>
                            <td class="p-3 text-slate-600 dark:text-slate-300">
                                {{ $ret->store->name }}
                            </td>
                            <td class="p-3 text-slate-500">
                                {{ $ret->created_at->format('d M Y, h:i A') }}
                            </td>
                            <td class="p-3 uppercase font-mono text-[11px] text-slate-600 dark:text-slate-300">
                                {{ str_replace('_', ' ', $ret->settlement_type) }}
                            </td>
                            <td class="p-3 text-right font-mono font-bold text-slate-900 dark:text-white">
                                ৳{{ number_format($ret->total_credit_amount, 2) }}
                            </td>
                            <td class="p-3 text-right">
                                <a href="{{ route('supplier-returns.show', $ret) }}" class="px-2.5 py-1 rounded bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-[11px] font-semibold">
                                    Debit Note →
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-6 text-center text-slate-400">
                                No supplier returns recorded yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($returns->hasPages())
            <div class="p-3 border-t border-slate-200 dark:border-slate-800">
                {{ $returns->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
