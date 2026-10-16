@extends('layouts.app')

@section('title', 'Employees & Staff')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">Employee Management</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">Manage store associates, cashiers, store managers, and monthly base salary configurations.</p>
        </div>
        @if(in_array(auth()->user()->role, ['super_admin', 'store_manager']))
            <a href="{{ route('employees.create') }}" class="inline-flex items-center px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-medium text-sm rounded-lg shadow-sm">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Add Employee
            </a>
        @endif
    </div>

    @if (session('success'))
        <div class="p-4 rounded-lg bg-emerald-50 text-emerald-800 border border-emerald-200 dark:bg-emerald-950/40 dark:border-emerald-900/50 dark:text-emerald-200">
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600 dark:text-slate-300">
                <thead class="bg-slate-50 dark:bg-slate-800/60 text-xs uppercase font-semibold text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
                    <tr>
                        <th class="px-6 py-3">Code / Name</th>
                        <th class="px-6 py-3">Assigned Store</th>
                        <th class="px-6 py-3">Role / Designation</th>
                        <th class="px-6 py-3">Contact</th>
                        @if(in_array(auth()->user()->role, ['super_admin', 'accountant']))
                            <th class="px-6 py-3 text-right">Base Salary</th>
                        @endif
                        <th class="px-6 py-3">Status</th>
                        <th class="px-6 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                    @forelse($employees as $emp)
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/50 transition">
                            <td class="px-6 py-4">
                                <div class="font-semibold text-slate-900 dark:text-white">{{ $emp->name }}</div>
                                <div class="text-xs font-mono text-slate-500">{{ $emp->employee_code }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                    {{ $emp->store->name ?? 'All Stores' }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-xs font-semibold uppercase text-emerald-600 dark:text-emerald-400">{{ $emp->role }}</div>
                                <div class="text-xs text-slate-500">{{ $emp->designation ?? 'N/A' }}</div>
                            </td>
                            <td class="px-6 py-4 text-xs">
                                <div>{{ $emp->phone }}</div>
                                <div class="text-slate-400">{{ $emp->email ?? 'No email' }}</div>
                            </td>
                            @if(in_array(auth()->user()->role, ['super_admin', 'accountant']))
                                <td class="px-6 py-4 text-right font-mono font-bold text-slate-900 dark:text-white">
                                    ৳{{ number_format($emp->base_salary, 2) }}
                                </td>
                            @endif
                            <td class="px-6 py-4">
                                @if($emp->status === 'active')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300">Active</span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300">{{ ucfirst($emp->status) }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right text-xs space-x-2">
                                <a href="{{ route('employees.show', $emp) }}" class="text-emerald-600 hover:text-emerald-800 dark:text-emerald-400 font-medium">View Profile</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ in_array(auth()->user()->role, ['super_admin', 'accountant']) ? 7 : 6 }}" class="px-6 py-12 text-center text-slate-500">
                                No employees found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($employees->hasPages())
            <div class="p-4 border-t border-slate-200 dark:border-slate-800">
                {{ $employees->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
