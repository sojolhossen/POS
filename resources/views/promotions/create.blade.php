@extends('layouts.app')

@section('title', 'New Promotion Campaign - ' . __('messages.promotions'))
@section('header', 'New Promotion Campaign')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-black text-slate-900 dark:text-white">Create Scheduled Retail Promotion</h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                Define discount percentage, schedule start/end dates, and applicable merchandise category.
            </p>
        </div>
        <a href="{{ route('promotions.index') }}" class="px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 text-slate-600 dark:text-slate-300 text-xs font-semibold">
            ← Back to Promotions
        </a>
    </div>

    @if ($errors->any())
        <div class="p-4 rounded-xl bg-rose-50 dark:bg-rose-950/50 border border-rose-200 dark:border-rose-800 text-rose-700 dark:text-rose-300 text-xs">
            <div class="font-bold mb-1">Validation Errors:</div>
            <ul class="list-disc list-inside space-y-0.5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('promotions.store') }}" class="bg-white dark:bg-slate-900 rounded-xl p-6 border border-slate-200 dark:border-slate-800 shadow-sm space-y-4">
        @csrf

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                    Campaign Name <span class="text-rose-500">*</span>
                </label>
                <input type="text" name="name" value="{{ old('name') }}" required
                       placeholder="e.g. Eid-ul-Fitr Festival Sale"
                       class="w-full text-xs rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white px-3 py-2">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                    Promo Code (Uppercase, No spaces) <span class="text-rose-500">*</span>
                </label>
                <input type="text" name="code" value="{{ old('code') }}" required
                       placeholder="e.g. EID2026"
                       class="w-full text-xs rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white px-3 py-2 font-mono uppercase">
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                    Discount Rate (% Percentage OFF) <span class="text-rose-500">*</span>
                </label>
                <input type="number" step="0.5" min="0.5" max="90" name="discount_percentage" value="{{ old('discount_percentage', '15.0') }}" required
                       class="w-full text-xs rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white px-3 py-2 font-mono font-bold">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                    Category Restriction (Optional)
                </label>
                <select name="category_id" class="w-full text-xs rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white px-3 py-2">
                    <option value="">All Categories / Storewide</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ old('category_id') == $cat->id ? 'selected' : '' }}>
                            {{ $cat->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                    Campaign Starts At <span class="text-rose-500">*</span>
                </label>
                <input type="datetime-local" name="starts_at" value="{{ old('starts_at', now()->format('Y-m-d\TH:i')) }}" required
                       class="w-full text-xs rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white px-3 py-2">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                    Campaign Ends At <span class="text-rose-500">*</span>
                </label>
                <input type="datetime-local" name="ends_at" value="{{ old('ends_at', now()->addDays(14)->format('Y-m-d\TH:i')) }}" required
                       class="w-full text-xs rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white px-3 py-2">
            </div>
        </div>

        <div>
            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                Campaign Description / Terms
            </label>
            <textarea name="description" rows="2" placeholder="e.g. 15% discount on Panjabis and Kurtas for Eid season"
                      class="w-full text-xs rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white px-3 py-2">{{ old('description') }}</textarea>
        </div>

        <div class="flex items-center gap-2">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1" id="is_active" checked class="rounded border-slate-300 text-indigo-600">
            <label for="is_active" class="text-xs font-bold text-slate-700 dark:text-slate-300">
                Activate Promotion Immediately
            </label>
        </div>

        <div class="pt-4 border-t border-slate-200 dark:border-slate-800 flex items-center justify-end gap-3">
            <a href="{{ route('promotions.index') }}" class="px-4 py-2 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-semibold">
                Cancel
            </a>
            <button type="submit" class="px-5 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold shadow-md shadow-indigo-600/30 transition">
                Create Promotion
            </button>
        </div>
    </form>
</div>
@endsection
