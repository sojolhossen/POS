@extends('layouts.app')

@section('title', 'Import Data Preview & Reconciliation')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">Dataset Validation & Preview</h1>
            <p class="text-sm font-mono text-slate-500">{{ $importJob->file_name }} • Entity: {{ strtoupper($importJob->type) }}</p>
        </div>
        <a href="{{ route('imports.index') }}" class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 dark:bg-slate-800 dark:border-slate-700 dark:text-slate-200">
            &larr; Back to Imports
        </a>
    </div>

    <!-- Summary Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-slate-900 p-5 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm">
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Rows In File</span>
            <div class="text-3xl font-bold font-mono text-slate-900 dark:text-white mt-1">{{ $importJob->total_rows }}</div>
        </div>
        <div class="bg-white dark:bg-slate-900 p-5 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm">
            <span class="text-xs font-semibold text-emerald-600 uppercase tracking-wider">Valid Rows Ready</span>
            <div class="text-3xl font-bold font-mono text-emerald-600 dark:text-emerald-400 mt-1">{{ $importJob->valid_rows }}</div>
        </div>
        <div class="bg-white dark:bg-slate-900 p-5 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm">
            <span class="text-xs font-semibold text-rose-600 uppercase tracking-wider">Rejected / Error Rows</span>
            <div class="text-3xl font-bold font-mono text-rose-600 dark:text-rose-400 mt-1">{{ $importJob->error_rows }}</div>
        </div>
        <div class="bg-white dark:bg-slate-900 p-5 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm">
            <span class="text-xs font-semibold text-indigo-600 uppercase tracking-wider">Current Status</span>
            <div class="text-2xl font-bold uppercase mt-1
                {{ $importJob->status === 'completed' ? 'text-emerald-600' : ($importJob->status === 'validated' ? 'text-amber-500' : 'text-slate-600') }}">
                {{ $importJob->status }}
            </div>
        </div>
    </div>

    <!-- Error Log (If errors present) -->
    @if(!empty($importJob->error_log))
        <div class="bg-rose-50 dark:bg-rose-950/30 rounded-xl border border-rose-200 dark:border-rose-900/50 p-6 space-y-3">
            <h3 class="text-sm font-bold text-rose-900 dark:text-rose-200 flex items-center gap-2">
                <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                Row-Level Validation Errors Detected ({{ count($importJob->error_log) }} items)
            </h3>
            <div class="max-h-60 overflow-y-auto space-y-1 text-xs font-mono text-rose-800 dark:text-rose-300">
                @foreach($importJob->error_log as $err)
                    <div class="p-2 bg-white/70 dark:bg-slate-900/50 rounded border border-rose-200/50 dark:border-rose-900/40">
                        <span class="font-bold">Row {{ $err['row'] ?? '?' }}:</span> {{ $err['error'] ?? 'Validation failure' }}
                        @if(!empty($err['data']))
                            <span class="text-slate-500 font-sans block text-[11px] mt-0.5">Data: {{ json_encode($err['data']) }}</span>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Sample Data Preview -->
    @if(!empty($importJob->sample_rows))
        <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden p-6 space-y-4">
            <h3 class="text-base font-bold text-slate-900 dark:text-white">Sample Data Rows (First 10 records)</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs font-mono text-slate-600 dark:text-slate-300">
                    <thead class="bg-slate-50 dark:bg-slate-800/60 uppercase font-semibold text-slate-500 border-b border-slate-200 dark:border-slate-800">
                        <tr>
                            @foreach(array_keys($importJob->sample_rows[0] ?? []) as $col)
                                <th class="px-4 py-2.5">{{ $col }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach($importJob->sample_rows as $row)
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/50">
                                @foreach($row as $val)
                                    <td class="px-4 py-2">{{ is_array($val) ? json_encode($val) : $val }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- Confirmation Action -->
    @if($importJob->status === 'validated' && $importJob->valid_rows > 0)
        <div class="p-6 rounded-xl bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-900/50 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h4 class="text-base font-bold text-emerald-900 dark:text-emerald-200">Ready to Commit {{ $importJob->valid_rows }} Valid Records</h4>
                <p class="text-xs text-emerald-700 dark:text-emerald-400">All valid rows will be committed to the production database atomically. Errors will be safely skipped.</p>
            </div>
            <form action="{{ route('imports.confirm', $importJob) }}" method="POST" onsubmit="return confirm('Confirm committing this dataset to production?')">
                @csrf
                <button type="submit" class="px-6 py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-sm rounded-lg shadow-lg">
                    Confirm & Execute Import &rarr;
                </button>
            </form>
        </div>
    @endif
</div>
@endsection
