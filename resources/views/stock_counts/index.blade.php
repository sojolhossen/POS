@extends('layouts.app')

@section('title', __('messages.stock_counts'))
@section('header', __('messages.stock_counts'))

@section('content')
<div class="space-y-6">

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold text-slate-900 dark:text-white">{{ __('messages.stock_counts') }}</h2>
            <p class="text-xs text-slate-500 mt-0.5">Barcode-Assisted Physical Inventory Audits & Reconciliation</p>
        </div>

        <a href="{{ route('stock-counts.create') }}" 
           class="inline-flex items-center gap-2 py-2.5 px-4 rounded-xl font-bold text-xs text-white bg-indigo-600 hover:bg-indigo-700 transition shadow-lg shadow-indigo-600/30">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            <span>+ Start Stock Count Session</span>
        </a>
    </div>

    <!-- Stock Counts Table -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-slate-200 dark:border-slate-800 text-slate-400 uppercase text-[10px] tracking-wider font-bold">
                        <th class="py-3.5 px-4">Session Ref #</th>
                        <th class="py-3.5 px-4">Store Location</th>
                        <th class="py-3.5 px-4">Started By</th>
                        <th class="py-3.5 px-4">Status</th>
                        <th class="py-3.5 px-4">Started Date</th>
                        <th class="py-3.5 px-4">Completed Date</th>
                        <th class="py-3.5 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800 font-medium">
                    @forelse($counts as $count)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition">
                            <td class="py-3.5 px-4 font-mono font-bold text-indigo-600 dark:text-indigo-400">
                                {{ $count->reference_number }}
                            </td>
                            <td class="py-3.5 px-4 font-bold text-slate-900 dark:text-white">
                                {{ $count->store->name }}
                            </td>
                            <td class="py-3.5 px-4 text-slate-700 dark:text-slate-300">
                                {{ $count->creator->name }}
                            </td>
                            <td class="py-3.5 px-4">
                                @if($count->status === 'in_progress')
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300 animate-pulse">
                                        IN PROGRESS
                                    </span>
                                @elseif($count->status === 'completed')
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">
                                        COMPLETED
                                    </span>
                                @else
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-slate-100 text-slate-600">
                                        CANCELLED
                                    </span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 font-mono text-[11px] text-slate-500">
                                @posDate($count->created_at)
                            </td>
                            <td class="py-3.5 px-4 font-mono text-[11px] text-slate-500">
                                {{ $count->completed_at ? \App\Helpers\FormattingHelper::date($count->completed_at) : '—' }}
                            </td>
                            <td class="py-3.5 px-4 text-right">
                                <a href="{{ route('stock-counts.show', $count) }}" class="py-1 px-3 rounded-lg bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 font-bold hover:bg-indigo-100">
                                    {{ $count->status === 'in_progress' ? 'Open Workstation' : 'View Reconciliation' }}
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-slate-400">No physical stock count sessions recorded.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t border-slate-100 dark:border-slate-800">
            {{ $counts->links() }}
        </div>
    </div>

</div>
@endsection
