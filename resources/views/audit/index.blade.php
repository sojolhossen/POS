@extends('layouts.app')

@section('title', __('messages.audit_logs'))
@section('header', __('messages.audit_logs'))

@section('content')
<div class="space-y-6">

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold text-slate-900 dark:text-white">{{ __('messages.audit_logs') }}</h2>
            <p class="text-xs text-slate-500 mt-0.5">Immutable Activity & Security Audit Trail</p>
        </div>
    </div>

    <!-- Audit Filter & Table -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm overflow-hidden">
        
        <div class="p-4 border-b border-slate-100 dark:border-slate-800">
            <form method="GET" action="{{ route('audit.index') }}" class="flex flex-wrap items-center gap-3">
                <div>
                    <select name="user_id" onchange="this.form.submit()" class="text-xs rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 py-2 px-3">
                        <option value="">All Actors / Users</option>
                        @foreach($users as $u)
                            <option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected' : '' }}>
                                {{ $u->name }} ({{ $u->role->label() }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="flex-1 min-w-[200px]">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search action, reason, IP..."
                           class="w-full text-xs rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 py-2 px-3">
                </div>

                <button type="submit" class="py-2 px-3.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-xs font-semibold hover:bg-slate-200">
                    Filter
                </button>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-slate-200 dark:border-slate-800 text-slate-400 uppercase text-[10px] tracking-wider font-bold">
                        <th class="py-3.5 px-4">Timestamp</th>
                        <th class="py-3.5 px-4">Action Event</th>
                        <th class="py-3.5 px-4">Actor</th>
                        <th class="py-3.5 px-4">Target Record</th>
                        <th class="py-3.5 px-4">Reason / Details</th>
                        <th class="py-3.5 px-4">IP Address</th>
                        <th class="py-3.5 px-4 text-right">Data Changes</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800 font-medium">
                    @forelse($logs as $log)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition">
                            <td class="py-3.5 px-4 font-mono text-[11px] text-slate-500 whitespace-nowrap">
                                @posDate($log->created_at) {{ $log->created_at->format('H:i:s') }}
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="px-2 py-0.5 rounded-md font-mono font-bold text-[10px] bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300">
                                    {{ $log->action }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4">
                                <div class="font-bold text-slate-900 dark:text-white">{{ $log->user ? $log->user->name : 'System / Guest' }}</div>
                                <div class="text-[10px] text-slate-400">{{ $log->user?->email }}</div>
                            </td>
                            <td class="py-3.5 px-4 font-mono text-slate-600 dark:text-slate-400 text-[11px]">
                                @if($log->auditable_type)
                                    {{ class_basename($log->auditable_type) }} #{{ $log->auditable_id }}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="py-3.5 px-4 text-slate-700 dark:text-slate-300 max-w-xs truncate">
                                {{ $log->reason ?? '—' }}
                            </td>
                            <td class="py-3.5 px-4 font-mono text-slate-500 text-[11px]">
                                {{ $log->ip_address }}
                            </td>
                            <td class="py-3.5 px-4 text-right">
                                @if($log->old_values || $log->new_values)
                                    <details class="text-[10px] text-slate-500 cursor-pointer">
                                        <summary class="font-bold text-indigo-600 hover:underline">View Values</summary>
                                        <div class="mt-1 p-2 bg-slate-100 dark:bg-slate-800 rounded-lg text-left font-mono whitespace-pre-wrap max-w-xs overflow-x-auto text-[10px]">
@if($log->old_values)Old: {{ json_encode($log->old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}
@endif
@if($log->new_values)New: {{ json_encode($log->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}@endif
                                        </div>
                                    </details>
                                @else
                                    <span class="text-slate-400 text-[11px]">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-slate-400">No audit logs recorded.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t border-slate-100 dark:border-slate-800">
            {{ $logs->links() }}
        </div>
    </div>

</div>
@endsection
