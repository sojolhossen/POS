@extends('layouts.app')

@section('title', 'Add Employee')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">New Employee Record</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">Add a store employee, assign designated store and configure monthly salary.</p>
        </div>
        <a href="{{ route('employees.index') }}" class="inline-flex items-center px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 dark:bg-slate-800 dark:border-slate-700 dark:text-slate-200">
            &larr; Back to Employees
        </a>
    </div>

    @if ($errors->any())
        <div class="p-4 rounded-lg bg-rose-50 text-rose-800 border border-rose-200 dark:bg-rose-950/40 dark:border-rose-900/50 dark:text-rose-200">
            <ul class="list-disc pl-5 space-y-1 text-sm">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('employees.store') }}" method="POST" class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm p-6 space-y-6">
        @csrf

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="name" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1">Full Name *</label>
                <input type="text" name="name" id="name" value="{{ old('name') }}" required placeholder="e.g. Tanvir Ahmed" class="w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white text-sm focus:ring-emerald-500 focus:border-emerald-500">
            </div>

            <div>
                <label for="employee_code" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1">Employee Code *</label>
                <input type="text" name="employee_code" id="employee_code" value="{{ old('employee_code', 'EMP-' . strtoupper(Str::random(6))) }}" required class="w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white text-sm font-mono focus:ring-emerald-500 focus:border-emerald-500">
            </div>

            <div>
                <label for="store_id" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1">Assigned Store *</label>
                <select name="store_id" id="store_id" required class="w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white text-sm focus:ring-emerald-500 focus:border-emerald-500">
                    @foreach($stores as $store)
                        <option value="{{ $store->id }}" {{ old('store_id', auth()->user()->store_id) == $store->id ? 'selected' : '' }}>
                            {{ $store->name }} ({{ $store->code }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="role" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1">Role *</label>
                <select name="role" id="role" required class="w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white text-sm focus:ring-emerald-500 focus:border-emerald-500">
                    <option value="sales_associate" {{ old('role') === 'sales_associate' ? 'selected' : '' }}>Sales Associate</option>
                    <option value="cashier" {{ old('role') === 'cashier' ? 'selected' : '' }}>Cashier</option>
                    <option value="store_manager" {{ old('role') === 'store_manager' ? 'selected' : '' }}>Store Manager</option>
                    <option value="warehouse_staff" {{ old('role') === 'warehouse_staff' ? 'selected' : '' }}>Warehouse Staff</option>
                    <option value="cleaner" {{ old('role') === 'cleaner' ? 'selected' : '' }}>Support / Cleaner</option>
                </select>
            </div>

            <div>
                <label for="designation" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1">Designation Title</label>
                <input type="text" name="designation" id="designation" value="{{ old('designation') }}" placeholder="e.g. Senior Floor Associate" class="w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white text-sm focus:ring-emerald-500 focus:border-emerald-500">
            </div>

            <div>
                <label for="phone" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1">Phone Number *</label>
                <input type="text" name="phone" id="phone" value="{{ old('phone') }}" required placeholder="017XXXXXXXX" class="w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white text-sm focus:ring-emerald-500 focus:border-emerald-500">
            </div>

            <div>
                <label for="email" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1">Email Address</label>
                <input type="email" name="email" id="email" value="{{ old('email') }}" placeholder="employee@store.com" class="w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white text-sm focus:ring-emerald-500 focus:border-emerald-500">
            </div>

            <div>
                <label for="joining_date" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1">Joining Date *</label>
                <input type="date" name="joining_date" id="joining_date" value="{{ old('joining_date', date('Y-m-d')) }}" required class="w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white text-sm focus:ring-emerald-500 focus:border-emerald-500">
            </div>

            <div>
                <label for="base_salary" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1">Monthly Base Salary (BDT ৳) *</label>
                <input type="number" step="0.01" min="0" name="base_salary" id="base_salary" value="{{ old('base_salary', '0.00') }}" required class="w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white text-sm font-mono focus:ring-emerald-500 focus:border-emerald-500">
            </div>

            <div>
                <label for="bank_details" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1">Bank / bKash / Nagad Disbursement Info</label>
                <input type="text" name="bank_details" id="bank_details" value="{{ old('bank_details') }}" placeholder="e.g. bKash Personal 017XXXXXXXX" class="w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white text-sm focus:ring-emerald-500 focus:border-emerald-500">
            </div>
        </div>

        <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-200 dark:border-slate-800">
            <a href="{{ route('employees.index') }}" class="px-5 py-2.5 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700">Cancel</a>
            <button type="submit" class="px-5 py-2.5 text-sm font-semibold text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 shadow-sm focus:ring-2 focus:ring-emerald-500">Save Employee</button>
        </div>
    </form>
</div>
@endsection
