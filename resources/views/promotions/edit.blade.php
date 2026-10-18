@extends('layouts.app')

@section('title', 'Edit Promotion - ' . $promotion->name)
@section('header', 'Edit Promotion Campaign')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-black text-slate-900 dark:text-white">Edit Promotion Campaign</h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                Update promotion terms, discount percentage, schedule, and product/category restrictions.
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

    <form method="POST" action="{{ route('promotions.update', $promotion) }}" class="bg-white dark:bg-slate-900 rounded-xl p-6 border border-slate-200 dark:border-slate-800 shadow-sm space-y-4">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                    Campaign Name <span class="text-rose-500">*</span>
                </label>
                <input type="text" name="name" value="{{ old('name', $promotion->name) }}" required
                       placeholder="e.g. Eid-ul-Fitr Festival Sale"
                       class="w-full text-xs rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white px-3 py-2">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                    Promo Code (Uppercase, No spaces) <span class="text-rose-500">*</span>
                </label>
                <input type="text" name="code" value="{{ old('code', $promotion->code) }}" required
                       placeholder="e.g. EID2026"
                       class="w-full text-xs rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white px-3 py-2 font-mono uppercase">
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                    Discount Rate (% Percentage OFF) <span class="text-rose-500">*</span>
                </label>
                <input type="number" step="0.5" min="0.5" max="90" name="discount_percentage" value="{{ old('discount_percentage', $promotion->discount_percentage) }}" required
                       class="w-full text-xs rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white px-3 py-2 font-mono font-bold">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                    Minimum Cart Spend (৳ BDT - Optional)
                </label>
                <input type="number" step="1" min="0" name="min_spend" value="{{ old('min_spend', $promotion->min_spend) }}"
                       placeholder="e.g. 1000 (0 for no minimum)"
                       class="w-full text-xs rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white px-3 py-2 font-mono">
            </div>
        </div>

        <!-- Scope & Restrictions: Specific Product OR Category OR Storewide -->
        <div class="p-4 rounded-xl bg-indigo-50/50 dark:bg-slate-800/60 border border-indigo-100 dark:border-slate-700/80 space-y-3">
            <div class="text-xs font-bold text-slate-900 dark:text-white flex items-center gap-1.5">
                <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                <span>Applicable Merchandise Scope (Product or Category)</span>
            </div>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Specific Product Selector -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                        Specific Product (Recommended)
                    </label>
                    <select name="product_id" class="w-full text-xs rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white px-3 py-2">
                        <option value="">-- No Product Restriction (All Products) --</option>
                        @foreach($products as $prod)
                            <option value="{{ $prod->id }}" {{ old('product_id', $promotion->product_id) == $prod->id ? 'selected' : '' }}>
                                {{ $prod->name }} (SKU: {{ $prod->sku }})
                            </option>
                        @endforeach
                    </select>
                    <p class="text-[11px] text-slate-500 mt-1">
                        If selected, during POS checkout this coupon will <strong>ONLY appear when this product is in the cart</strong>.
                    </p>
                </div>

                <!-- Category Restriction Selector -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                        Or Category Restriction
                    </label>
                    <select name="category_id" class="w-full text-xs rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white px-3 py-2">
                        <option value="">-- All Categories / Storewide --</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ old('category_id', $promotion->category_id) == $cat->id ? 'selected' : '' }}>
                                {{ $cat->name }}
                            </option>
                        @endforeach
                    </select>
                    <p class="text-[11px] text-slate-500 mt-1">
                        Applied if no specific product is selected above.
                    </p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                    Campaign Starts At <span class="text-rose-500">*</span>
                </label>
                <input type="datetime-local" name="starts_at" value="{{ old('starts_at', $promotion->starts_at ? $promotion->starts_at->format('Y-m-d\TH:i') : '') }}" required
                       class="w-full text-xs rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white px-3 py-2">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                    Campaign Ends At <span class="text-rose-500">*</span>
                </label>
                <input type="datetime-local" name="ends_at" value="{{ old('ends_at', $promotion->ends_at ? $promotion->ends_at->format('Y-m-d\TH:i') : '') }}" required
                       class="w-full text-xs rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white px-3 py-2">
            </div>
        </div>

        <div>
            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                Campaign Description / Terms
            </label>
            <textarea name="description" rows="2" placeholder="e.g. 15% discount on Panjabis and Kurtas for Eid season"
                      class="w-full text-xs rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white px-3 py-2">{{ old('description', $promotion->description) }}</textarea>
        </div>

        <div class="flex items-center gap-2">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1" id="is_active" {{ old('is_active', $promotion->is_active) ? 'checked' : '' }} class="rounded border-slate-300 text-indigo-600">
            <label for="is_active" class="text-xs font-bold text-slate-700 dark:text-slate-300">
                Promotion is Active
            </label>
        </div>

        <div class="pt-4 border-t border-slate-200 dark:border-slate-800 flex items-center justify-between">
            <form method="POST" action="{{ route('promotions.destroy', $promotion) }}" onsubmit="return confirm('Are you sure you want to delete this promotion?');" class="inline">
                @csrf
                @method('DELETE')
                <button type="submit" class="px-3.5 py-2 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-700 text-xs font-bold transition">
                    Delete Promotion
                </button>
            </form>

            <div class="flex items-center gap-3">
                <a href="{{ route('promotions.index') }}" class="px-4 py-2 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-semibold">
                    Cancel
                </a>
                <button type="submit" class="px-5 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold shadow-md shadow-indigo-600/30 transition">
                    Save Changes
                </button>
            </div>
        </div>
    </form>
</div>
@endsection
