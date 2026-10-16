@extends('layouts.app')

@section('title', 'Daily Cash Reconciliation Report')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">Daily Cash Reconciliation</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">Audit cash-in-hand expected vs actual counted, shortages, surpluses, and daily closing status.</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('reports.export', ['type' => 'cash', 'date' => $date, 'store_id' => $storeId]) }}" class="inline-flex items-center px-4 py-2 bg-slate-800 hover:bg-slate-700 text-white font-medium text-xs rounded-lg shadow-sm">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Export CSV
            </a>
        </div>
    </div>

    <!-- Filter -->
    <div class="bg-white dark:bg-slate-900 p-4 rounded-xl border border-slate-200 dark:border-slate-800">
        <form method="GET" action="{{ route('reports.cash') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
            @if(in_array(auth()->user()->role, ['super_admin', 'accountant']))
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Store Scope</label>
                    <select name="store_id" class="w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 text-xs text-slate-900 dark:text-white">
                        <option value="">All Stores</option>
                        @foreach($stores as $st)
                            <option value="{{ $st->id }}" {{ $storeId == $st->id ? 'selected' : '' }}>{{ $st->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Business Date</label>
                <input type="date" name="date" value="{{ $date }}" class="w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 text-xs text-slate-900 dark:text-white">
            </div>
            <div>
                <button type="submit" class="w-full px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-medium text-xs rounded-lg shadow-sm">
                    Reconcile
                </button>
            </div>
        </form>
    </div>

    <!-- Sessions Table -->
    <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600 dark:text-slate-300">
                <thead class="bg-slate-50 dark:bg-slate-800/60 text-xs uppercase font-semibold text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
                    <tr>
                        <th class="px-6 py-3">Store / Date</th>
                        <th class="px-6 py-3">Cashier</th>
                        <th class="px-6 py-3 text-right">Opening Cash</th>
                        <th class="px-6 py-3 text-right">Net Sales Cash</th>
                        <th class="px-6 py-3 text-right">Expected Cash</th>
                        <th class="px-6 py-3 text-right">Counted Cash</th>
                        <th class="px-6 py-3 text-right">Discrepancy</th>
                        <th class="px-6 py-3 text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                    @forelse($registers as $reg)
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/50 transition">
                            <td class="px-6 py-4">
                                <div class="font-semibold text-slate-900 dark:text-white">{{ $reg->store->name ?? 'Store' }}</div>
                                <div class="text-xs text-slate-400 font-mono">{{ $reg->business_date ? \Carbon\Carbon::parse($reg->business_date)->format('Y-m-d') : '--' }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="font-medium text-slate-900 dark:text-white">{{ $reg->opener->name ?? 'Unknown' }}</div>
                                <div class="text-xs text-slate-400">Closed by: {{ $reg->closer->name ?? 'N/A' }}</div>
                            </td>
                            <td class="px-6 py-4 text-right font-mono">৳{{ number_format($reg->opening_cash ?? 0, 2) }}</td>
                            <td class="px-6 py-4 text-right font-mono">৳{{ number_format($reg->cash_sales_amount ?? 0, 2) }}</td>
                            <td class="px-6 py-4 text-right font-mono font-medium">৳{{ number_format($reg->cash_in_hand_expected ?? 0, 2) }}</td>
                            <td class="px-6 py-4 text-right font-mono font-bold text-slate-900 dark:text-white">
                                {{ $reg->closing_cash_counted !== null ? '৳' . number_format($reg->closing_cash_counted, 2) : 'Pending' }}
                            </td>
                            <td class="px-6 py-4 text-right font-mono font-bold">
                                @if($reg->discrepancy_amount !== null)
                                    @if($reg->discrepancy_amount == 0)
                                        <span class="text-emerald-600 dark:text-emerald-400">৳0.00</span>
                                    @elseif($reg->discrepancy_amount < 0)
                                        <span class="text-rose-600">-৳{{ number_format(abs($reg->discrepancy_amount), 2) }}</span>
                                    @else
                                        <span class="text-amber-600">+৳{{ number_format($reg->discrepancy_amount, 2) }}</span>
                                    @endif
                                    @if($reg->discrepancy_reason)
                                        <div class="text-[10px] text-slate-400 truncate max-w-xs font-sans">{{ $reg->discrepancy_reason }}</div>
                                    @endif
                                @else
                                    <span class="text-slate-400">--</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-center">
                                @if($reg->status === 'closed')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-300">
                                        Closed
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-800 dark:bg-amber-950/50 dark:text-amber-300">
                                        Open
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center text-slate-500">
                                No cash register sessions found for the specified period.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($registers->hasPages())
            <div class="p-4 border-t border-slate-200 dark:border-slate-800">
                {{ $registers->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
