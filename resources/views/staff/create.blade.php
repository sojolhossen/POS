@extends('layouts.app')

@section('title', 'Create Staff Account')
@section('header', 'Create Staff Account')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">

    <div class="flex items-center justify-between">
        <a href="{{ route('staff.index') }}" class="text-xs font-bold text-slate-500 hover:text-indigo-600 flex items-center gap-1">
            ← Back to Staff Directory
        </a>
    </div>

    <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 sm:p-8 border border-slate-200/80 dark:border-slate-800 shadow-sm">
        <h2 class="text-lg font-bold text-slate-900 dark:text-white mb-6">Create New Staff User</h2>

        <form action="{{ route('staff.store') }}" method="POST" class="space-y-5">
            @csrf

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">Full Name *</label>
                    <input type="text" name="name" value="{{ old('name') }}" required placeholder="e.g. Shakib Al Hasan"
                           class="w-full text-xs rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 py-2.5 px-3.5 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">Email Address (Login Username) *</label>
                    <input type="email" name="email" value="{{ old('email') }}" required placeholder="shakib@clothingpos.com"
                           class="w-full text-xs rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 py-2.5 px-3.5 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">Role Permission Level *</label>
                    <select name="role" required class="w-full text-xs rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 py-2.5 px-3.5">
                        @foreach($roles as $role)
                            <option value="{{ $role->value }}" {{ old('role') == $role->value ? 'selected' : '' }}>
                                {{ $role->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">Assigned Store / Location</label>
                    <select name="store_id" class="w-full text-xs rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 py-2.5 px-3.5">
                        <option value="">-- None / All Stores (Super Admin) --</option>
                        @foreach($stores as $st)
                            <option value="{{ $st->id }}" {{ old('store_id') == $st->id ? 'selected' : '' }}>
                                {{ $st->name }} ({{ $st->code }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">Contact Phone</label>
                <input type="text" name="phone" value="{{ old('phone') }}" placeholder="+8801700000000"
                       class="w-full text-xs rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 py-2.5 px-3.5">
            </div>

            <!-- Password Policy Section -->
            <div class="p-4 rounded-2xl bg-indigo-50/50 dark:bg-indigo-950/30 border border-indigo-200/60 dark:border-indigo-800/40 space-y-4">
                <div>
                    <div class="text-xs font-bold text-indigo-900 dark:text-indigo-300">Server Password Security Policy</div>
                    <div class="text-[11px] text-slate-600 dark:text-slate-400 mt-0.5">Minimum 8 characters with letters, numbers, and special symbols (@, #, $, !, etc.).</div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">Initial Password *</label>
                        <input type="password" name="password" required
                               class="w-full text-xs rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 py-2.5 px-3.5">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">Confirm Password *</label>
                        <input type="password" name="password_confirmation" required
                               class="w-full text-xs rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 py-2.5 px-3.5">
                    </div>
                </div>
            </div>

            <label class="flex items-center gap-3 cursor-pointer pt-2">
                <input type="checkbox" name="is_active" value="1" checked
                       class="rounded text-indigo-600 focus:ring-indigo-500 w-4 h-4">
                <div>
                    <span class="text-xs font-bold text-slate-900 dark:text-white">Account Active</span>
                    <p class="text-[11px] text-slate-500">Active accounts may log into the application.</p>
                </div>
            </label>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
                <a href="{{ route('staff.index') }}" class="py-2.5 px-4 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100">
                    Cancel
                </a>
                <button type="submit" class="py-2.5 px-6 rounded-xl font-bold text-xs text-white bg-indigo-600 hover:bg-indigo-700 transition shadow-lg shadow-indigo-600/30">
                    Create Staff Account
                </button>
            </div>
        </form>
    </div>

</div>
@endsection
