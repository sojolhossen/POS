@extends('layouts.app')

@section('title', 'Staff Attendance')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">Store Staff Attendance</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">Daily attendance logging, store-level verification, and shift presence audit trail.</p>
        </div>
        <div class="flex items-center gap-3">
            <form method="GET" action="{{ route('attendance.index') }}" class="flex items-center gap-2">
                @if(auth()->user()->isSuperAdmin() || auth()->user()->isAccountant() || $stores->count() > 1)
                    <select name="store_id" onchange="this.form.submit()" class="rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 text-xs text-slate-700 dark:text-slate-200">
                        @foreach($stores as $st)
                            <option value="{{ $st->id }}" {{ $storeId == $st->id ? 'selected' : '' }}>{{ $st->name }}</option>
                        @endforeach
                    </select>
                @endif
                <input type="date" name="date" value="{{ $date }}" onchange="this.form.submit()" class="rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 text-xs text-slate-700 dark:text-slate-200">
            </form>
        </div>
    </div>

    @if (session('success'))
        <div class="p-4 rounded-lg bg-emerald-50 text-emerald-800 border border-emerald-200 dark:bg-emerald-950/40 dark:border-emerald-900/50 dark:text-emerald-200">
            {{ session('success') }}
        </div>
    @endif

    <!-- Attendance Logging Card -->
    @if(auth()->user()->isSuperAdmin() || auth()->user()->isStoreManager())
        <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 p-6 shadow-sm space-y-4">
            <h2 class="text-base font-bold text-slate-900 dark:text-white">Record Daily Attendance ({{ \Carbon\Carbon::parse($date)->format('d M, Y') }})</h2>
            <form action="{{ route('attendance.store') }}" method="POST" class="grid grid-cols-1 md:grid-cols-6 gap-4 items-end">
                @csrf
                <input type="hidden" name="store_id" value="{{ $storeId }}">
                <input type="hidden" name="attendance_date" value="{{ $date }}">

                <div class="md:col-span-2">
                    <label for="employee_id" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Employee *</label>
                    <select name="employee_id" id="employee_id" required class="w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white text-xs">
                        <option value="">Select Employee</option>
                        @foreach($employees as $emp)
                            <option value="{{ $emp->id }}">{{ $emp->name }} ({{ $emp->employee_code }} - {{ $emp->designation ?? $emp->role }})</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="status" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Status *</label>
                    <select name="status" id="status" required class="w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white text-xs">
                        <option value="present">Present</option>
                        <option value="late">Late</option>
                        <option value="half_day">Half Day</option>
                        <option value="leave">Approved Leave</option>
                        <option value="absent">Absent</option>
                    </select>
                </div>

                <div>
                    <label for="check_in" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Check-In</label>
                    <input type="time" name="check_in" id="check_in" value="09:00" class="w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white text-xs">
                </div>

                <div>
                    <label for="check_out" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Check-Out</label>
                    <input type="time" name="check_out" id="check_out" value="18:00" class="w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white text-xs">
                </div>

                <div>
                    <button type="submit" class="w-full px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-medium text-xs rounded-lg shadow-sm">
                        Record
                    </button>
                </div>
            </form>
        </div>
    @endif

    <!-- Attendance Log Table -->
    <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600 dark:text-slate-300">
                <thead class="bg-slate-50 dark:bg-slate-800/60 text-xs uppercase font-semibold text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
                    <tr>
                        <th class="px-6 py-3">Date</th>
                        <th class="px-6 py-3">Employee</th>
                        <th class="px-6 py-3">Status</th>
                        <th class="px-6 py-3">Check In / Out</th>
                        <th class="px-6 py-3">Logged By</th>
                        <th class="px-6 py-3">Notes</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                    @forelse($attendances as $att)
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/50 transition">
                            <td class="px-6 py-4 font-mono text-xs font-semibold text-slate-900 dark:text-white">
                                {{ $att->attendance_date }}
                            </td>
                            <td class="px-6 py-4">
                                <div class="font-semibold text-slate-900 dark:text-white">{{ $att->employee->name ?? 'Deleted' }}</div>
                                <div class="text-xs text-slate-400 font-mono">{{ $att->employee->employee_code ?? '' }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold
                                    {{ $att->status === 'present' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-300' :
                                       ($att->status === 'absent' ? 'bg-rose-100 text-rose-800 dark:bg-rose-950/50 dark:text-rose-300' :
                                       'bg-amber-100 text-amber-800 dark:bg-amber-950/50 dark:text-amber-300') }}">
                                    {{ ucfirst($att->status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 font-mono text-xs">
                                {{ $att->check_in ? \Carbon\Carbon::parse($att->check_in)->format('h:i A') : '--' }} -
                                {{ $att->check_out ? \Carbon\Carbon::parse($att->check_out)->format('h:i A') : '--' }}
                            </td>
                            <td class="px-6 py-4 text-xs text-slate-400">
                                {{ $att->recordedBy->name ?? 'System' }}
                            </td>
                            <td class="px-6 py-4 text-xs text-slate-500">
                                {{ $att->notes ?? '--' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-slate-500">
                                No attendance records found for this date.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if(method_exists($attendances, 'hasPages') && $attendances->hasPages())
            <div class="p-4 border-t border-slate-200 dark:border-slate-800">
                {{ $attendances->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
