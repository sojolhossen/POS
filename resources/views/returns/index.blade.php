@extends('layouts.app')

@section('title', __('messages.returns_exchanges') . ' - Retail History')
@section('header', __('messages.returns_exchanges'))

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-black text-slate-900 dark:text-white flex items-center gap-2">
                <span>{{ __('messages.returns_exchanges') }}</span>
                <span class="text-xs px-2.5 py-0.5 rounded-full bg-indigo-100 text-indigo-700 dark:bg-indigo-900/60 dark:text-indigo-300 font-bold">
                    7-Day Return Policy
                </span>
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                Process customer garment returns, exchanges, refunds, and item quality classification (Sellable, Damaged, Defective).
            </p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('returns.create') }}" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs shadow-md shadow-indigo-600/20 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span>Process Return / Exchange</span>
            </a>
        </div>
    </div>

    <!-- Returns History Table -->
    <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-800/60 border-b border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-300 font-bold">
                        <th class="p-3">Return Number</th>
                        <th class="p-3">Original Invoice</th>
                        <th class="p-3">Store</th>
                        <th class="p-3">Date</th>
                        <th class="p-3 text-center">Type</th>
                        <th class="p-3">Method</th>
                        <th class="p-3 text-right">Refund / Settlement Amount</th>
                        <th class="p-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($returns as $ret)
                        <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition">
                            <td class="p-3 font-mono font-bold text-indigo-600 dark:text-indigo-400">
                                <a href="{{ route('returns.receipt', $ret) }}" class="hover:underline">
                                    {{ $ret->return_number }}
                                </a>
                            </td>
                            <td class="p-3 font-mono text-slate-600 dark:text-slate-300">
                                <a href="{{ route('pos.receipt', $ret->originalSale) }}" class="text-indigo-600 hover:underline">
                                    {{ $ret->originalSale->invoice_number }}
                                </a>
                            </td>
                            <td class="p-3 text-slate-800 dark:text-slate-200 font-medium">
                                {{ $ret->store->name }}
                            </td>
                            <td class="p-3 text-slate-500">
                                {{ $ret->created_at->format('d M Y, h:i A') }}
                            </td>
                            <td class="p-3 text-center">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase
                                    {{ $ret->return_type === 'exchange' ? 'bg-purple-100 text-purple-800 dark:bg-purple-950/60 dark:text-purple-300' : 'bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-300' }}">
                                    {{ $ret->return_type }}
                                </span>
                            </td>
                            <td class="p-3 uppercase font-mono text-[11px] text-slate-600 dark:text-slate-300">
                                {{ $ret->settlement_method }}
                            </td>
                            <td class="p-3 text-right font-mono font-bold text-rose-600 dark:text-rose-400">
                                ৳{{ number_format($ret->total_refund_amount, 2) }}
                            </td>
                            <td class="p-3 text-right">
                                <a href="{{ route('returns.receipt', $ret) }}" class="px-2.5 py-1 rounded bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-[11px] font-semibold">
                                    Voucher
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="p-6 text-center text-slate-400">
                                No returns or exchanges recorded.
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
