@extends('layouts.app')

@section('title', 'Edit Store — ' . $store->name)
@section('header', 'Edit Store')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">

    <div class="flex items-center justify-between">
        <a href="{{ route('stores.index') }}" class="text-xs font-bold text-slate-500 hover:text-indigo-600 flex items-center gap-1">
            ← Back to Stores
        </a>
    </div>

    <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 sm:p-8 border border-slate-200/80 dark:border-slate-800 shadow-sm">
        <h2 class="text-lg font-bold text-slate-900 dark:text-white mb-6">Edit Store / Warehouse: {{ $store->name }}</h2>

        <form action="{{ route('stores.update', $store) }}" method="POST" class="space-y-5">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">Store / Warehouse Name *</label>
                    <input type="text" name="name" value="{{ old('name', $store->name) }}" required
                           class="w-full text-xs rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 py-2.5 px-3.5 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">Store Code *</label>
                    <input type="text" name="code" value="{{ old('code', $store->code) }}" required
                           class="w-full text-xs font-mono font-bold rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 py-2.5 px-3.5 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">Assigned Store Manager</label>
                    <select name="manager_id" class="w-full text-xs rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 py-2.5 px-3.5">
                        <option value="">-- Select Store Manager --</option>
                        @foreach($managers as $manager)
                            <option value="{{ $manager->id }}" {{ old('manager_id', $store->manager_id) == $manager->id ? 'selected' : '' }}>
                                {{ $manager->name }} ({{ $manager->email }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">Contact Phone</label>
                    <input type="text" name="phone" value="{{ old('phone', $store->phone) }}"
                           class="w-full text-xs rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 py-2.5 px-3.5">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">Physical Address</label>
                <textarea name="address" rows="2"
                          class="w-full text-xs rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 py-2.5 px-3.5">{{ old('address', $store->address) }}</textarea>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">Opening Time</label>
                    <input type="time" name="opening_time" value="{{ old('opening_time', $store->opening_time ? substr($store->opening_time, 0, 5) : '') }}"
                           class="w-full text-xs rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 py-2.5 px-3.5">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">Closing Time</label>
                    <input type="time" name="closing_time" value="{{ old('closing_time', $store->closing_time ? substr($store->closing_time, 0, 5) : '') }}"
                           class="w-full text-xs rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 py-2.5 px-3.5">
                </div>
            </div>

            <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/60 dark:border-slate-700/60 space-y-3">
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="checkbox" name="is_warehouse" value="1" {{ old('is_warehouse', $store->is_warehouse) ? 'checked' : '' }}
                           class="rounded text-indigo-600 focus:ring-indigo-500 w-4 h-4">
                    <div>
                        <span class="text-xs font-bold text-slate-900 dark:text-white">Designate as Warehouse Facility</span>
                        <p class="text-[11px] text-slate-500">Warehouses hold central stock before retail distribution.</p>
                    </div>
                </label>

                <label class="flex items-center gap-3 cursor-pointer pt-2 border-t border-slate-200/40 dark:border-slate-700/40">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $store->is_active) ? 'checked' : '' }}
                           class="rounded text-indigo-600 focus:ring-indigo-500 w-4 h-4">
                    <div>
                        <span class="text-xs font-bold text-slate-900 dark:text-white">Active Location</span>
                        <p class="text-[11px] text-slate-500">Deactivated locations cannot conduct sales or receive stock.</p>
                    </div>
                </label>
            </div>

            <div class="flex items-center justify-end gap-3 pt-4">
                <a href="{{ route('stores.index') }}" class="py-2.5 px-4 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100">
                    Cancel
                </a>
                <button type="submit" class="py-2.5 px-6 rounded-xl font-bold text-xs text-white bg-indigo-600 hover:bg-indigo-700 transition shadow-lg shadow-indigo-600/30">
                    Update Store Record
                </button>
            </div>
        </form>
    </div>

</div>
@endsection
