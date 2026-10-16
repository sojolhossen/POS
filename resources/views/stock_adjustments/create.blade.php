@extends('layouts.app')

@section('title', 'Propose Stock Adjustment')
@section('header', 'Propose Stock Adjustment')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">

    <div class="flex items-center justify-between">
        <a href="{{ route('stock-adjustments.index') }}" class="text-xs font-bold text-slate-500 hover:text-indigo-600 flex items-center gap-1">
            ← Back to Adjustments
        </a>
    </div>

    <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 sm:p-8 border border-slate-200/80 dark:border-slate-800 shadow-sm">
        <h2 class="text-lg font-bold text-slate-900 dark:text-white mb-2">Submit Stock Adjustment Request</h2>
        <p class="text-xs text-slate-500 mb-6">Actual inventory will not be altered until approved by the Super Admin.</p>

        <form action="{{ route('stock-adjustments.store') }}" method="POST" class="space-y-5">
            @csrf

            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">Store Location *</label>
                <select name="store_id" required class="w-full text-xs rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 py-2.5 px-3.5 font-bold">
                    @foreach($stores as $st)
                        <option value="{{ $st->id }}" {{ old('store_id') == $st->id ? 'selected' : '' }}>
                            {{ $st->name }} ({{ $st->code }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">Product Style Variant *</label>
                <select name="product_variant_id" required class="w-full text-xs rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 py-2.5 px-3.5">
                    <option value="">-- Select Variant --</option>
                    @foreach($variants as $variant)
                        <option value="{{ $variant->id }}" {{ old('product_variant_id') == $variant->id ? 'selected' : '' }}>
                            {{ $variant->product->name }} — {{ $variant->color }} / {{ $variant->size }} ({{ $variant->barcode }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">Proposed Physical Quantity *</label>
                <input type="number" name="proposed_quantity" required min="0" value="{{ old('proposed_quantity') }}" placeholder="e.g. 18"
                       class="w-full text-xs font-mono font-bold rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 py-2.5 px-3.5">
                <p class="text-[10px] text-slate-400 mt-1">Enter the actual verified counted quantity present on shelf or rack.</p>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">Mandatory Justification / Discrepancy Reason *</label>
                <textarea name="reason" rows="3" required placeholder="State exact cause: e.g. Factory shipment shrinkage, display damage, or physical audit correction"
                          class="w-full text-xs rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 py-2.5 px-3.5">{{ old('reason') }}</textarea>
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
                <a href="{{ route('stock-adjustments.index') }}" class="py-2.5 px-4 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100">
                    Cancel
                </a>
                <button type="submit" class="py-2.5 px-6 rounded-xl font-bold text-xs text-white bg-indigo-600 hover:bg-indigo-700 transition shadow-lg shadow-indigo-600/30">
                    Submit for Super Admin Approval
                </button>
            </div>
        </form>
    </div>

</div>
@endsection
