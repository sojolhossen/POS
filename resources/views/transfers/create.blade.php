@extends('layouts.app')

@section('title', 'Request Stock Transfer - ' . __('messages.transfers'))
@section('header', 'Request Stock Transfer')

@section('content')
<div class="max-w-4xl mx-auto space-y-6" x-data="transferRequestForm()">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-black text-slate-900 dark:text-white">Create Stock Transfer Request</h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                Request garments from the Central Warehouse or sister retail store. Requires approval before dispatch.
            </p>
        </div>
        <a href="{{ route('transfers.index') }}" class="px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 text-slate-600 dark:text-slate-300 text-xs font-semibold">
            ← Back to Transfers
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

    <form method="POST" action="{{ route('transfers.store') }}" class="space-y-6">
        @csrf

        <div class="bg-white dark:bg-slate-900 rounded-xl p-6 border border-slate-200 dark:border-slate-800 shadow-sm space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                        Source Store (Supplying Location) <span class="text-rose-500">*</span>
                    </label>
                    <select name="source_store_id" required class="w-full text-xs rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white px-3 py-2 font-medium focus:ring-2 focus:ring-indigo-500">
                        @if($sourceStores->count() > 1)
                            <option value="">Select Warehouse / Supplying Location</option>
                        @endif
                        @forelse($sourceStores as $store)
                            <option value="{{ $store->id }}" {{ (old('source_store_id') == $store->id || $sourceStores->count() === 1) ? 'selected' : '' }}>
                                {{ $store->name }} ({{ $store->code }}) {{ $store->is_warehouse ? '— [Central Warehouse]' : '' }}
                            </option>
                        @empty
                            <option value="">No warehouse facility found</option>
                        @endforelse
                    </select>
                    <p class="text-[10px] text-slate-400 mt-1 flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 text-indigo-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        <span>Supplying source is strictly Central Warehouse. (Super Admin can re-route if stock is unavailable).</span>
                    </p>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                        Destination Store (Receiving Location) <span class="text-rose-500">*</span>
                    </label>
                    <select name="destination_store_id" required class="w-full text-xs rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white px-3 py-2 font-medium focus:ring-2 focus:ring-indigo-500">
                        @if($destinationStores->count() > 1)
                            <option value="">Select Assigned Destination Store</option>
                        @endif
                        @forelse($destinationStores as $store)
                            <option value="{{ $store->id }}" {{ (old('destination_store_id', auth()->user()->store_id) == $store->id || $destinationStores->count() === 1) ? 'selected' : '' }}>
                                {{ $store->name }} ({{ $store->code }})
                            </option>
                        @empty
                            <option value="">No assigned store found</option>
                        @endforelse
                    </select>
                    @if($destinationStores->isEmpty())
                        <p class="text-[11px] text-rose-500 font-semibold mt-1">⚠️ No store is currently assigned to your account. Please contact Super Admin.</p>
                    @else
                        <p class="text-[10px] text-slate-400 mt-1 flex items-center gap-1">
                            <svg class="w-3.5 h-3.5 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span>Only retail stores assigned to your manager profile are listed.</span>
                        </p>
                    @endif
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                    Transfer Purpose / Justification Notes
                </label>
                <textarea name="notes" rows="2" placeholder="e.g. Replenishment for upcoming Eid festival campaign"
                          class="w-full text-xs rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white px-3 py-2">{{ old('notes') }}</textarea>
            </div>
        </div>

        <!-- Line Items -->
        <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
            <div class="p-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                <div>
                    <h2 class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-200">
                        Requested Garments & Quantities
                    </h2>
                    <p class="text-[11px] text-slate-400">Add the size/color variants needed at the destination store.</p>
                </div>
                <button type="button" @click="addItem()" class="px-3 py-1.5 rounded-lg bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-950/70 text-indigo-700 dark:text-indigo-300 text-xs font-bold transition flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    <span>Add Item</span>
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="bg-slate-50 dark:bg-slate-800/60 text-slate-600 dark:text-slate-300 font-bold border-b border-slate-100 dark:border-slate-800">
                            <th class="p-3 w-10 text-center">#</th>
                            <th class="p-3 min-w-[280px]">Garment Variant (Size / Color / SKU)</th>
                            <th class="p-3 w-32 text-right">Requested Qty</th>
                            <th class="p-3 w-12 text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        <template x-for="(item, index) in items" :key="index">
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30">
                                <td class="p-3 text-center text-slate-400 font-mono" x-text="index + 1"></td>
                                <td class="p-3">
                                    <select :name="'items[' + index + '][product_variant_id]'" x-model="item.product_variant_id" required
                                            class="w-full text-xs rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white px-2.5 py-1.5">
                                        <option value="">Select Garment Variant</option>
                                        @foreach($variants as $v)
                                            <option value="{{ $v->id }}">
                                                {{ $v->product->name }} • {{ $v->color ?? 'Standard' }} / {{ $v->size ?? 'Free' }} [{{ $v->sku }}]
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="p-3 text-right">
                                    <input type="number" min="1" :name="'items[' + index + '][quantity]'" x-model.number="item.quantity" required
                                           class="w-full text-right text-xs rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white px-2.5 py-1.5 font-mono">
                                </td>
                                <td class="p-3 text-center">
                                    <button type="button" @click="removeItem(index)" class="text-rose-500 hover:text-rose-700 p-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <div class="p-4 bg-slate-50 dark:bg-slate-800/60 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-xs">
                <span class="text-slate-500">Total Requested Units:</span>
                <strong class="font-mono text-slate-900 dark:text-white" x-text="totalQty() + ' pcs'"></strong>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('transfers.index') }}" class="px-4 py-2 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-semibold">
                Cancel
            </a>
            <button type="submit" class="px-6 py-2.5 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold shadow-md shadow-indigo-600/30 transition">
                Submit Transfer Request
            </button>
        </div>
    </form>
</div>

<script>
function transferRequestForm() {
    return {
        items: [
            { product_variant_id: '', quantity: 10 }
        ],
        addItem() {
            this.items.push({ product_variant_id: '', quantity: 5 });
        },
        removeItem(index) {
            if (this.items.length > 1) {
                this.items.splice(index, 1);
            }
        },
        totalQty() {
            return this.items.reduce((sum, item) => sum + Number(item.quantity || 0), 0);
        }
    }
}
</script>
@endsection
