@extends('layouts.app')

@section('title', $supplier->name . ' - Supplier Ledger')
@section('header', 'Supplier Profile & Ledger')

@section('content')
<div class="space-y-6">
    <!-- Header Card -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl p-6 border border-slate-200 dark:border-slate-800 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="font-mono text-xs font-bold px-2.5 py-0.5 rounded bg-indigo-50 dark:bg-indigo-950/70 text-indigo-700 dark:text-indigo-300">
                    {{ $supplier->code }}
                </span>
                @if($supplier->is_active)
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300">
                        Active
                    </span>
                @else
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-300">
                        Inactive
                    </span>
                @endif
            </div>
            <h1 class="text-2xl font-black text-slate-900 dark:text-white">{{ $supplier->name }}</h1>
            <div class="text-xs text-slate-500 dark:text-slate-400 mt-1 flex flex-wrap items-center gap-4">
                <span>Contact: <strong class="text-slate-700 dark:text-slate-300">{{ $supplier->contact_person ?? 'N/A' }}</strong> ({{ $supplier->phone }})</span>
                @if($supplier->email)<span>Email: {{ $supplier->email }}</span>@endif
                @if($supplier->city)<span>Location: {{ $supplier->city }}</span>@endif
            </div>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('suppliers.edit', $supplier) }}" class="px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-bold transition">
                Edit Details
            </a>
            <a href="{{ route('purchase-orders.create') }}?supplier_id={{ $supplier->id }}" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold shadow-md shadow-indigo-600/20 transition">
                + Create PO
            </a>
        </div>
    </div>

    <!-- Financial Balance Summary Tiles -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white dark:bg-slate-900 rounded-xl p-5 border border-slate-200 dark:border-slate-800 shadow-sm">
            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Total Purchase Billed</span>
            <div class="text-2xl font-black text-slate-900 dark:text-white mt-1">
                ৳{{ number_format($supplier->total_purchases, 2) }}
            </div>
            <p class="text-[11px] text-slate-400 mt-1">All approved supplier bills</p>
        </div>

        <div class="bg-white dark:bg-slate-900 rounded-xl p-5 border border-slate-200 dark:border-slate-800 shadow-sm">
            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Total Payments Cleared</span>
            <div class="text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-1">
                ৳{{ number_format($supplier->total_paid, 2) }}
            </div>
            <p class="text-[11px] text-slate-400 mt-1">Cash, Bank Transfer, Cheques</p>
        </div>

        <div class="bg-white dark:bg-slate-900 rounded-xl p-5 border border-slate-200 dark:border-slate-800 shadow-sm">
            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Current Outstanding Due</span>
            <div class="text-2xl font-black {{ $supplier->outstanding_balance > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-slate-900 dark:text-white' }} mt-1">
                ৳{{ number_format($supplier->outstanding_balance, 2) }}
            </div>
            <p class="text-[11px] text-slate-400 mt-1">Net payable liability</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Record Payment Box -->
        <div class="lg:col-span-1 bg-white dark:bg-slate-900 rounded-xl p-5 border border-slate-200 dark:border-slate-800 shadow-sm space-y-4">
            <div>
                <h3 class="text-sm font-black text-slate-900 dark:text-white flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    <span>Record Vendor Payment</span>
                </h3>
                <p class="text-[11px] text-slate-400 mt-0.5">Settle supplier dues with audit traceability.</p>
            </div>

            <form method="POST" action="{{ route('suppliers.payments.store', $supplier) }}" class="space-y-3">
                @csrf
                <div>
                    <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">
                        Payment Amount (BDT ৳) <span class="text-rose-500">*</span>
                    </label>
                    <input type="number" step="0.01" min="0.01" max="{{ max(0.01, $supplier->outstanding_balance) }}" name="amount" required
                           value="{{ old('amount', $supplier->outstanding_balance > 0 ? $supplier->outstanding_balance : '') }}"
                           placeholder="0.00"
                           class="w-full text-xs rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white px-3 py-2">
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">
                        Payment Method <span class="text-rose-500">*</span>
                    </label>
                    <select name="payment_method" required class="w-full text-xs rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white px-3 py-2">
                        <option value="bank_transfer">Bank Transfer (BEFTN/RTGS)</option>
                        <option value="cash">Cash Voucher</option>
                        <option value="cheque">Company Cheque</option>
                        <option value="bkash">bKash Merchant</option>
                        <option value="nagad">Nagad</option>
                        <option value="other">Other Settlement</option>
                    </select>
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">
                        Payment Date <span class="text-rose-500">*</span>
                    </label>
                    <input type="date" name="payment_date" required value="{{ date('Y-m-d') }}"
                           class="w-full text-xs rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white px-3 py-2">
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">
                        Transaction Reference / Cheque No
                    </label>
                    <input type="text" name="reference_number" placeholder="e.g. TR-98234 or CHQ-00129"
                           class="w-full text-xs rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white px-3 py-2">
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">
                        Notes / Voucher Remarks
                    </label>
                    <textarea name="notes" rows="2" placeholder="Payment voucher description"
                              class="w-full text-xs rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white px-3 py-2"></textarea>
                </div>

                <button type="submit" class="w-full py-2.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-md shadow-emerald-600/20 transition">
                    Submit Payment Voucher
                </button>
            </form>
        </div>

        <!-- Purchase Bills and Payments Tabbed/Split -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Purchase Orders -->
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
                <div class="p-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-200">
                        Purchase Bills & Orders
                    </h3>
                    <span class="text-xs text-slate-400 font-mono">{{ $supplier->purchaseOrders->count() }} Orders</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="bg-slate-50 dark:bg-slate-800/60 text-slate-500 font-bold border-b border-slate-100 dark:border-slate-800">
                                <th class="p-2.5">PO Number</th>
                                <th class="p-2.5">Date</th>
                                <th class="p-2.5 text-center">Status</th>
                                <th class="p-2.5 text-right">Items / Qty</th>
                                <th class="p-2.5 text-right">Bill Total</th>
                                <th class="p-2.5 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @forelse($supplier->purchaseOrders as $po)
                                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/40">
                                    <td class="p-2.5 font-mono font-bold text-indigo-600 dark:text-indigo-400">
                                        <a href="{{ route('purchase-orders.show', $po) }}" class="hover:underline">
                                            {{ $po->po_number }}
                                        </a>
                                    </td>
                                    <td class="p-2.5 text-slate-500">{{ optional($po->po_date ?? $po->created_at)->format('d M Y') ?? '—' }}</td>
                                    <td class="p-2.5 text-center">
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold
                                            @if($po->status === 'completed') bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300
                                            @elseif($po->status === 'approved') bg-blue-100 text-blue-800 dark:bg-blue-950/60 dark:text-blue-300
                                            @elseif($po->status === 'submitted') bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300
                                            @elseif($po->status === 'partially_received') bg-purple-100 text-purple-800 dark:bg-purple-950/60 dark:text-purple-300
                                            @else bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 @endif">
                                            {{ strtoupper($po->status) }}
                                        </span>
                                    </td>
                                    <td class="p-2.5 text-right text-slate-600 dark:text-slate-300 font-mono">
                                        {{ $po->total_received_qty }} / {{ $po->total_ordered_qty }}
                                    </td>
                                    <td class="p-2.5 text-right font-mono font-bold text-slate-800 dark:text-slate-200">
                                        ৳{{ number_format($po->total_amount, 2) }}
                                    </td>
                                    <td class="p-2.5 text-right">
                                        <a href="{{ route('purchase-orders.show', $po) }}" class="text-[11px] text-indigo-600 hover:underline">
                                            Details →
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="p-4 text-center text-slate-400 text-xs">No purchase orders found for this supplier.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Payment Ledger History -->
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
                <div class="p-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-200">
                        Payment Vouchers & Settlements
                    </h3>
                    <span class="text-xs text-slate-400 font-mono">{{ $supplier->payments->count() }} Payments</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="bg-slate-50 dark:bg-slate-800/60 text-slate-500 font-bold border-b border-slate-100 dark:border-slate-800">
                                <th class="p-2.5">Voucher #</th>
                                <th class="p-2.5">Date</th>
                                <th class="p-2.5">Method</th>
                                <th class="p-2.5">Reference</th>
                                <th class="p-2.5">Recorded By</th>
                                <th class="p-2.5 text-right">Amount</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @forelse($supplier->payments as $payment)
                                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/40">
                                    <td class="p-2.5 font-mono font-bold text-slate-800 dark:text-slate-200">
                                        {{ $payment->voucher_number }}
                                    </td>
                                    <td class="p-2.5 text-slate-500">{{ $payment->payment_date->format('d M Y') }}</td>
                                    <td class="p-2.5 capitalize text-slate-600 dark:text-slate-300">
                                        {{ str_replace('_', ' ', $payment->payment_method) }}
                                    </td>
                                    <td class="p-2.5 font-mono text-[11px] text-slate-400">
                                        {{ $payment->reference_number ?? '—' }}
                                    </td>
                                    <td class="p-2.5 text-slate-500">
                                        {{ $payment->creator->name ?? 'System' }}
                                    </td>
                                    <td class="p-2.5 text-right font-mono font-bold text-emerald-600 dark:text-emerald-400">
                                        ৳{{ number_format($payment->amount, 2) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="p-4 text-center text-slate-400 text-xs">No payments recorded yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
