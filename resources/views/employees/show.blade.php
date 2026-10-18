@extends('layouts.app')

@section('title', 'Employee Profile - ' . $employee->name)

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">{{ $employee->name }}</h1>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300">
                    {{ ucfirst($employee->status) }}
                </span>
            </div>
            <p class="text-sm font-mono text-slate-500">{{ $employee->employee_code }} • {{ $employee->designation ?? $employee->role }} • {{ $employee->store->name ?? 'All Stores' }}</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('employees.index') }}" class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700">
                &larr; Back to Employees
            </a>
            @if(auth()->user()->isSuperAdmin() || auth()->user()->isAccountant())
                <a href="{{ route('payroll.index') }}" class="px-4 py-2 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700">
                    Process Payroll
                </a>
            @endif
        </div>
    </div>

    <!-- Overview Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-slate-900 p-5 rounded-xl border border-slate-200 dark:border-slate-800">
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Base Salary</span>
            <div class="text-2xl font-bold font-mono text-slate-900 dark:text-white mt-1">
                @if(auth()->user()->isSuperAdmin() || auth()->user()->isAccountant())
                    ৳{{ number_format($employee->monthly_salary, 2) }}
                @else
                    <span class="text-sm text-slate-400 font-sans italic">Restricted</span>
                @endif
            </div>
            <span class="text-xs text-slate-400">Per month</span>
        </div>
        <div class="bg-white dark:bg-slate-900 p-5 rounded-xl border border-slate-200 dark:border-slate-800">
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Joining Date</span>
            <div class="text-lg font-semibold text-slate-900 dark:text-white mt-1">
                {{ \Carbon\Carbon::parse($employee->joining_date)->format('d M, Y') }}
            </div>
            <span class="text-xs text-slate-400">{{ \Carbon\Carbon::parse($employee->joining_date)->diffForHumans() }}</span>
        </div>
        <div class="bg-white dark:bg-slate-900 p-5 rounded-xl border border-slate-200 dark:border-slate-800">
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Phone</span>
            <div class="text-lg font-semibold text-slate-900 dark:text-white mt-1">
                {{ $employee->phone }}
            </div>
            <span class="text-xs text-slate-400">{{ $employee->email ?? 'No email' }}</span>
        </div>
        <div class="bg-white dark:bg-slate-900 p-5 rounded-xl border border-slate-200 dark:border-slate-800">
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Disbursement Info</span>
            <div class="text-sm font-semibold text-slate-900 dark:text-white mt-1 truncate">
                {{ $employee->bank_details ?? 'Not configured' }}
            </div>
            <span class="text-xs text-slate-400">Account details</span>
        </div>
    </div>

    <!-- Attendance and Payroll History -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Recent Attendance -->
        <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 p-6 space-y-4">
            <h2 class="text-lg font-bold text-slate-900 dark:text-white">Recent Attendance Logs</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-600 dark:text-slate-300">
                    <thead class="text-xs uppercase bg-slate-50 dark:bg-slate-800/60 text-slate-400 border-b border-slate-200 dark:border-slate-800">
                        <tr>
                            <th class="py-2.5 px-3">Date</th>
                            <th class="py-2.5 px-3">Status</th>
                            <th class="py-2.5 px-3">Check-In / Out</th>
                            <th class="py-2.5 px-3">Recorded By</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse($employee->attendances()->latest('attendance_date')->take(10)->get() as $att)
                            <tr>
                                <td class="py-2.5 px-3 font-medium text-slate-900 dark:text-white">{{ $att->attendance_date }}</td>
                                <td class="py-2.5 px-3">
                                    <span class="inline-flex px-2 py-0.5 rounded text-xs font-semibold
                                        {{ $att->status === 'present' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-300' :
                                           ($att->status === 'absent' ? 'bg-rose-100 text-rose-800 dark:bg-rose-950/50 dark:text-rose-300' :
                                           'bg-amber-100 text-amber-800 dark:bg-amber-950/50 dark:text-amber-300') }}">
                                        {{ ucfirst($att->status) }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-3 text-xs font-mono">
                                    {{ $att->check_in ? \Carbon\Carbon::parse($att->check_in)->format('h:i A') : '--' }} -
                                    {{ $att->check_out ? \Carbon\Carbon::parse($att->check_out)->format('h:i A') : '--' }}
                                </td>
                                <td class="py-2.5 px-3 text-xs text-slate-400">{{ $att->recordedBy->name ?? 'System' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-6 text-center text-slate-400 text-xs">No attendance recorded yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Salary Payments -->
        <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 p-6 space-y-4">
            <h2 class="text-lg font-bold text-slate-900 dark:text-white">Salary Payment Records</h2>
            @if(auth()->user()->isSuperAdmin() || auth()->user()->isAccountant())
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-slate-600 dark:text-slate-300">
                        <thead class="text-xs uppercase bg-slate-50 dark:bg-slate-800/60 text-slate-400 border-b border-slate-200 dark:border-slate-800">
                            <tr>
                                <th class="py-2.5 px-3">Month</th>
                                <th class="py-2.5 px-3">Paid Amount</th>
                                <th class="py-2.5 px-3">Paid Date</th>
                                <th class="py-2.5 px-3">Method</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @forelse($employee->salaryPayments()->latest('paid_at')->take(10)->get() as $pay)
                                <tr>
                                    <td class="py-2.5 px-3 font-semibold text-slate-900 dark:text-white">{{ $pay->month_year }}</td>
                                    <td class="py-2.5 px-3 font-mono font-bold text-emerald-600 dark:text-emerald-400">৳{{ number_format($pay->net_paid_amount, 2) }}</td>
                                    <td class="py-2.5 px-3 text-xs">{{ \Carbon\Carbon::parse($pay->paid_at)->format('d M, Y') }}</td>
                                    <td class="py-2.5 px-3 text-xs uppercase">{{ $pay->payment_method }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="py-6 text-center text-slate-400 text-xs">No salary payments on record.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @else
                <div class="p-6 text-center text-slate-400 text-sm italic">
                    You do not have permission to view confidential payroll disbursements for this employee.
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
