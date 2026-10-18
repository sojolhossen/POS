@extends('layouts.app')

@section('title', __('messages.promotions') . ' - Retail Discount Engine')
@section('header', __('messages.promotions'))

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-black text-slate-900 dark:text-white flex items-center gap-2">
                <span>{{ __('messages.promotions') }}</span>
                <span class="text-xs px-2.5 py-0.5 rounded-full bg-indigo-100 text-indigo-700 dark:bg-indigo-900/60 dark:text-indigo-300 font-bold">
                    Super Admin Exclusive
                </span>
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                Configure scheduled festival discounts, percentage off rules, and time-bounded campaigns (Non-stacking policy).
            </p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('promotions.create') }}" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs shadow-md shadow-indigo-600/20 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span>New Promotion Rule</span>
            </a>
        </div>
    </div>

    <!-- Promotions Table -->
    <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-800/60 border-b border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-300 font-bold">
                        <th class="p-3">Campaign Name</th>
                        <th class="p-3">Promo Code</th>
                        <th class="p-3 text-right">Discount Rate</th>
                        <th class="p-3">Applies To</th>
                        <th class="p-3">Schedule Window</th>
                        <th class="p-3 text-center">Status</th>
                        <th class="p-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($promotions as $promo)
                        <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition">
                            <td class="p-3 font-semibold text-slate-900 dark:text-white">
                                {{ $promo->name }}
                                @if($promo->description)
                                    <div class="text-[11px] text-slate-400 font-normal">{{ $promo->description }}</div>
                                @endif
                            </td>
                            <td class="p-3 font-mono font-bold text-indigo-600 dark:text-indigo-400">
                                {{ $promo->code }}
                            </td>
                            <td class="p-3 text-right font-mono font-black text-emerald-600 dark:text-emerald-400">
                                {{ $promo->discount_percentage }}% OFF
                            </td>
                            <td class="p-3 text-slate-700 dark:text-slate-300">
                                @if($promo->product)
                                    <div class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md text-[11px] font-bold bg-indigo-50 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300 border border-indigo-200/60 dark:border-indigo-800/60">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                                        <span>Product: {{ $promo->product->name }}</span>
                                    </div>
                                    <div class="text-[10px] text-slate-400 font-mono mt-0.5">SKU: {{ $promo->product->sku }}</div>
                                @elseif($promo->category)
                                    <div class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[11px] font-semibold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200/60 dark:border-emerald-800/60">
                                        <span>Category: {{ $promo->category->name }}</span>
                                    </div>
                                @else
                                    <span class="text-slate-500 font-medium">Storewide (All Products)</span>
                                @endif
                                @if($promo->min_spend > 0)
                                    <div class="text-[10px] text-amber-600 dark:text-amber-400 mt-0.5">Min spend: ৳{{ number_format($promo->min_spend, 0) }}</div>
                                @endif
                            </td>
                            <td class="p-3 text-slate-500 font-mono text-[11px]">
                                {{ $promo->starts_at->format('d M Y') }} → {{ $promo->ends_at->format('d M Y') }}
                            </td>
                            <td class="p-3 text-center">
                                @if($promo->isCurrentlyActive())
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300">
                                        ● Live Active
                                    </span>
                                @elseif($promo->is_active && now()->lt($promo->starts_at))
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 text-blue-800 dark:bg-blue-950/60 dark:text-blue-300">
                                        Scheduled
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-300">
                                        Inactive / Expired
                                    </span>
                                @endif
                            </td>
                            <td class="p-3 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <a href="{{ route('promotions.edit', $promo) }}" class="px-2.5 py-1 rounded text-[11px] font-semibold bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 transition">
                                        Edit
                                    </a>
                                    <form method="POST" action="{{ route('promotions.toggle', $promo) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="px-2.5 py-1 rounded text-[11px] font-semibold transition
                                            {{ $promo->is_active ? 'bg-rose-50 text-rose-700 hover:bg-rose-100 dark:bg-rose-950/60' : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100 dark:bg-emerald-950/60' }}">
                                            {{ $promo->is_active ? 'Deactivate' : 'Activate' }}
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-6 text-center text-slate-400">
                                No promotion rules defined yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($promotions->hasPages())
            <div class="p-3 border-t border-slate-200 dark:border-slate-800">
                {{ $promotions->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
