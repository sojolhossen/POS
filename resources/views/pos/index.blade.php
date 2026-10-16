@extends('layouts.app')

@section('title', 'Point of Sale (POS) - Retail Checkout')
@section('header', 'Retail POS Checkout')

@section('content')
<div class="h-[calc(100vh-140px)] flex flex-col space-y-3" x-data="posApp()">

    <!-- TOP STATUS BAR & REGISTER AUDIT -->
    <div class="bg-white dark:bg-slate-900 rounded-xl px-4 py-2.5 border border-slate-200 dark:border-slate-800 shadow-sm flex flex-wrap items-center justify-between gap-3 text-xs">
        <div class="flex items-center gap-3">
            <div class="flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                <span class="font-bold text-slate-900 dark:text-white">{{ $store->name }}</span>
                <span class="text-slate-400 font-mono text-[11px]">({{ $store->code }})</span>
            </div>
            <span class="text-slate-300 dark:text-slate-700">|</span>
            <div class="text-slate-500">
                Cashier: <strong class="text-slate-800 dark:text-slate-200">{{ auth()->user()->name }}</strong>
            </div>
            <span class="text-slate-300 dark:text-slate-700">|</span>
            <div class="text-slate-500">
                Business Day: <strong class="text-indigo-600 dark:text-indigo-400 font-mono">{{ now()->format('d M Y') }}</strong>
            </div>
        </div>

        <div class="flex items-center gap-2">
            @if($register && $register->isOpen())
                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    <span>Register Active (Open Cash: ৳{{ number_format($register->opening_cash, 2) }})</span>
                </span>
                <a href="{{ route('daily-registers.index') }}" class="text-[11px] text-slate-500 hover:text-slate-800 dark:hover:text-white underline">
                    Register Control
                </a>
            @else
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-lg bg-amber-50 dark:bg-amber-950/60 border border-amber-200 dark:border-amber-800 text-amber-800 dark:text-amber-300 text-[11px] font-bold">
                    <span>⚠️ Daily Cash Register Not Opened Yet!</span>
                    <a href="{{ route('daily-registers.index') }}" class="px-2 py-0.5 rounded bg-amber-600 hover:bg-amber-700 text-white font-bold text-[10px] transition">
                        Open Day Register
                    </a>
                </div>
            @endif
        </div>
    </div>

    <!-- MAIN TWO-PANEL WORKSTATION -->
    <div class="flex-1 grid grid-cols-1 lg:grid-cols-12 gap-3 min-h-0">

        <!-- LEFT PANEL: CART & SCANNING (8 Cols) -->
        <div class="lg:col-span-8 flex flex-col bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
            
            <!-- FAST SCANNER INPUT HUD -->
            <div class="p-3 bg-slate-900 text-white border-b border-slate-800 flex items-center gap-3">
                <div class="relative flex-1">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                    </div>
                    <input type="text" x-ref="barcodeInput" x-model="searchQuery" @keydown.enter.prevent="handleSearchOrScan()"
                           placeholder="Scan Code 128 / EAN Barcode or type garment name/SKU..."
                           class="w-full text-xs rounded-xl border-white/20 bg-white/10 text-white placeholder-slate-400 pl-9 pr-4 py-2.5 font-mono focus:ring-2 focus:ring-emerald-400 focus:outline-none">
                </div>

                <button type="button" @click="handleSearchOrScan()" class="px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs transition">
                    Scan / Add
                </button>
            </div>

            <!-- SEARCH AUTOCOMPLETE RESULTS (IF VISIBLE) -->
            <div x-show="searchResults.length > 0" class="max-h-48 overflow-y-auto bg-slate-50 dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700 p-2 space-y-1">
                <template x-for="item in searchResults" :key="item.id">
                    <div @click="addToCart(item)" class="flex items-center justify-between p-2 rounded-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 hover:border-indigo-500 cursor-pointer text-xs">
                        <div>
                            <span class="font-bold text-slate-900 dark:text-white" x-text="item.name"></span>
                            <span class="text-slate-400 text-[11px]" x-text="' (' + (item.color || 'Standard') + ' / ' + (item.size || 'Free') + ')'"></span>
                            <span class="text-indigo-600 dark:text-indigo-400 font-mono text-[10px] ml-1" x-text="'[' + item.barcode + ']'"></span>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="font-mono text-slate-500 text-[11px]" x-text="'Stock: ' + item.available_stock + ' pcs'"></span>
                            <span class="font-mono font-bold text-emerald-600" x-text="'৳' + item.price.toFixed(2)"></span>
                        </div>
                    </div>
                </template>
            </div>

            <!-- CART ITEMS TABLE (FLEX-1 SCROLLABLE) -->
            <div class="flex-1 overflow-y-auto">
                <table class="w-full text-left text-xs">
                    <thead class="sticky top-0 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-bold border-b border-slate-200 dark:border-slate-700">
                        <tr>
                            <th class="p-3 w-8 text-center">#</th>
                            <th class="p-3">Garment Item & Variant</th>
                            <th class="p-3 text-right w-24">Price (৳)</th>
                            <th class="p-3 text-center w-32">Quantity</th>
                            <th class="p-3 text-right w-28">Subtotal (৳)</th>
                            <th class="p-3 w-10 text-center"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        <template x-for="(line, index) in cart" :key="line.variant_id">
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/40">
                                <td class="p-3 text-center text-slate-400 font-mono" x-text="index + 1"></td>
                                <td class="p-3">
                                    <div class="font-bold text-slate-900 dark:text-white" x-text="line.name"></div>
                                    <div class="text-[11px] text-slate-400" x-text="(line.color || 'Standard') + ' / ' + (line.size || 'Free') + ' • ' + line.barcode"></div>
                                </td>
                                <td class="p-3 text-right font-mono text-slate-700 dark:text-slate-300" x-text="line.price.toFixed(2)"></td>
                                <td class="p-3">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <button type="button" @click="decrementQty(index)" class="w-6 h-6 rounded bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 text-slate-700 dark:text-slate-300 font-bold flex items-center justify-center">
                                            -
                                        </button>
                                        <span class="w-8 text-center font-mono font-bold text-slate-900 dark:text-white" x-text="line.quantity"></span>
                                        <button type="button" @click="incrementQty(index)" class="w-6 h-6 rounded bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 text-slate-700 dark:text-slate-300 font-bold flex items-center justify-center">
                                            +
                                        </button>
                                    </div>
                                </td>
                                <td class="p-3 text-right font-mono font-bold text-slate-900 dark:text-white" x-text="(line.price * line.quantity).toFixed(2)"></td>
                                <td class="p-3 text-center">
                                    <button type="button" @click="removeLine(index)" class="text-rose-500 hover:text-rose-700 p-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </td>
                            </tr>
                        </template>

                        <tr x-show="cart.length === 0">
                            <td colspan="6" class="p-12 text-center text-slate-400 dark:text-slate-500">
                                <svg class="w-12 h-12 mx-auto text-slate-300 dark:text-slate-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                                <div class="font-bold">Cart is Empty</div>
                                <div class="text-[11px] mt-0.5">Scan product barcode tag with scanner or enter SKU above.</div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- CART FOOTER QUICK BAR -->
            <div class="p-3 bg-slate-50 dark:bg-slate-800/60 border-t border-slate-200 dark:border-slate-800 flex items-center justify-between text-xs">
                <button type="button" @click="clearCart()" x-show="cart.length > 0" class="text-rose-600 hover:underline font-semibold">
                    Clear Entire Cart
                </button>
                <div class="text-slate-500 font-mono">
                    Total Items: <strong class="text-slate-900 dark:text-white" x-text="totalItemCount()"></strong> pcs
                </div>
            </div>
        </div>

        <!-- RIGHT PANEL: CUSTOMER, DISCOUNTS, TOTALS & PAY (4 Cols) -->
        <div class="lg:col-span-4 flex flex-col bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm p-4 space-y-4">
            
            <!-- CUSTOMER & LOYALTY LOOKUP -->
            <div class="bg-slate-50 dark:bg-slate-800/50 p-3 rounded-xl border border-slate-100 dark:border-slate-800 space-y-2">
                <div class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                    Customer & Loyalty (Optional)
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <input type="text" x-model="customerPhone" placeholder="Mobile Number..."
                           class="w-full text-xs rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white px-2.5 py-1.5 font-mono">
                    <input type="text" x-model="customerName" placeholder="Customer Name..."
                           class="w-full text-xs rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white px-2.5 py-1.5">
                </div>
            </div>

            <!-- PROMOTION & CAMPAIGN RULE (SUPER ADMIN DEFINED) -->
            <div class="space-y-1">
                <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                    Scheduled Promotion (Non-Stacking)
                </label>
                <select x-model="selectedPromotion" class="w-full text-xs rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white px-2.5 py-1.5">
                    <option value="">No Promotion Applied</option>
                    @foreach($activePromotions as $promo)
                        <option value="{{ $promo->code }}">
                            {{ $promo->name }} ({{ $promo->discount_percent }}% OFF)
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- FINANCIAL TOTALS BREAKDOWN -->
            <div class="flex-1 bg-slate-50 dark:bg-slate-800/40 p-4 rounded-xl border border-slate-200/80 dark:border-slate-800 flex flex-col justify-between space-y-3 font-mono text-xs">
                <div class="space-y-2">
                    <div class="flex items-center justify-between text-slate-500">
                        <span>Cart Subtotal</span>
                        <span class="font-bold text-slate-800 dark:text-slate-200">৳<span x-text="subtotal().toFixed(2)"></span></span>
                    </div>

                    <div class="flex items-center justify-between text-emerald-600 dark:text-emerald-400" x-show="promoDiscount() > 0">
                        <span>Promotion Discount</span>
                        <span class="font-bold">-৳<span x-text="promoDiscount().toFixed(2)"></span></span>
                    </div>

                    <div class="flex items-center justify-between text-slate-500">
                        <span>Tax / VAT ({{ config('pos.tax_rate', 5) }}%)</span>
                        <span class="font-bold text-slate-800 dark:text-slate-200">৳<span x-text="vatAmount().toFixed(2)"></span></span>
                    </div>

                    <div class="flex items-center justify-between text-slate-400 text-[11px]" x-show="roundingAdjustment() !== 0">
                        <span>BDT Rounding</span>
                        <span x-text="(roundingAdjustment() > 0 ? '+৳' : '-৳') + Math.abs(roundingAdjustment()).toFixed(2)"></span>
                    </div>
                </div>

                <div class="pt-3 border-t-2 border-slate-200 dark:border-slate-700 flex items-baseline justify-between">
                    <div>
                        <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Net Payable Total</div>
                        <div class="text-xs text-slate-400">Rounded to Nearest BDT ৳</div>
                    </div>
                    <div class="text-2xl font-black text-indigo-600 dark:text-indigo-400">
                        ৳<span x-text="roundedTotal()"></span>
                    </div>
                </div>
            </div>

            <!-- TENDER / PAY BUTTON -->
            <button type="button" @click="openPaymentModal()" :disabled="cart.length === 0"
                    class="w-full py-3.5 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 disabled:opacity-40 disabled:cursor-not-allowed text-white font-black text-sm shadow-lg shadow-emerald-600/30 transition flex items-center justify-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                <span>Settle & Checkout [F9]</span>
            </button>
        </div>
    </div>

    <!-- SPLIT PAYMENTS & SETTLEMENT MODAL -->
    <div x-show="showPayModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/70 backdrop-blur-sm flex items-center justify-center p-4">
        <div @click.away="showPayModal = false" class="bg-white dark:bg-slate-900 rounded-2xl max-w-xl w-full p-6 border border-slate-200 dark:border-slate-800 shadow-2xl space-y-5">
            
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                <div>
                    <h3 class="text-base font-black text-slate-900 dark:text-white">Multi-Rail Split Payment Checkout</h3>
                    <p class="text-xs text-slate-500">Invoice Total: <strong class="text-indigo-600 font-mono text-sm">৳<span x-text="roundedTotal()"></span></strong></p>
                </div>
                <button type="button" @click="showPayModal = false" class="text-slate-400 hover:text-slate-600 p-1 text-lg font-bold">&times;</button>
            </div>

            <!-- PAYMENT SPLIT LINES -->
            <div class="space-y-3">
                <template x-for="(pay, idx) in paymentSplits" :key="idx">
                    <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-slate-700 dark:text-slate-300" x-text="'Payment Rail #' + (idx + 1)"></span>
                            <button type="button" @click="removeSplit(idx)" x-show="paymentSplits.length > 1" class="text-rose-500 text-[11px] hover:underline">Remove</button>
                        </div>

                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                            <div>
                                <label class="block text-[10px] font-bold text-slate-400 mb-0.5">Method</label>
                                <select x-model="pay.payment_method" class="w-full text-xs rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white px-2 py-1.5">
                                    <option value="cash">Cash Tender</option>
                                    <option value="bkash">bKash Mobile</option>
                                    <option value="nagad">Nagad Mobile</option>
                                    <option value="rocket">Rocket Mobile</option>
                                    <option value="card">Debit / Credit Card</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-[10px] font-bold text-slate-400 mb-0.5">Allocated (৳)</label>
                                <input type="number" step="1" min="1" x-model.number="pay.amount"
                                       class="w-full text-xs rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white px-2 py-1.5 font-mono font-bold">
                            </div>

                            <div x-show="pay.payment_method === 'cash'">
                                <label class="block text-[10px] font-bold text-slate-400 mb-0.5">Tendered (৳)</label>
                                <input type="number" step="1" min="0" x-model.number="pay.tendered_amount"
                                       placeholder="Cash Given"
                                       class="w-full text-xs rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white px-2 py-1.5 font-mono">
                            </div>

                            <div x-show="pay.payment_method !== 'cash'">
                                <label class="block text-[10px] font-bold text-slate-400 mb-0.5">Txn / Approval ID</label>
                                <input type="text" x-model="pay.reference" placeholder="e.g. 9B8721X"
                                       class="w-full text-xs rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white px-2 py-1.5 font-mono">
                            </div>
                        </div>
                    </div>
                </template>

                <button type="button" @click="addSplit()" class="text-xs text-indigo-600 dark:text-indigo-400 font-bold hover:underline flex items-center gap-1">
                    + Add Another Payment Method (Split Bill)
                </button>
            </div>

            <!-- PAYMENT SUMMARY & CHANGE -->
            <div class="bg-slate-100 dark:bg-slate-800 p-3 rounded-xl space-y-1.5 text-xs font-mono">
                <div class="flex items-center justify-between text-slate-600 dark:text-slate-300">
                    <span>Total Allocated across Rails:</span>
                    <strong x-text="'৳' + totalAllocated()"></strong>
                </div>
                <div class="flex items-center justify-between" :class="remainingDue() > 0 ? 'text-rose-600 font-bold' : 'text-slate-500'">
                    <span>Remaining Due:</span>
                    <strong x-text="'৳' + remainingDue()"></strong>
                </div>
                <div class="flex items-center justify-between text-emerald-600 font-bold" x-show="cashChange() > 0">
                    <span>Cash Change Return to Customer:</span>
                    <strong class="text-sm" x-text="'৳' + cashChange()"></strong>
                </div>
            </div>

            <!-- SUBMIT BUTTON -->
            <div class="flex items-center justify-end gap-2 pt-2">
                <button type="button" @click="showPayModal = false" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-semibold">
                    Back to Cart
                </button>
                <button type="button" @click="submitCheckout()" :disabled="remainingDue() !== 0 || isSubmitting"
                        class="px-6 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 disabled:opacity-40 disabled:cursor-not-allowed text-white font-bold text-xs shadow-md transition flex items-center gap-1.5">
                    <span x-show="!isSubmitting">Complete Sale & Print Receipt</span>
                    <span x-show="isSubmitting">Processing Transaction...</span>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function posApp() {
    return {
        searchQuery: '',
        searchResults: [],
        cart: [],
        customerPhone: '',
        customerName: '',
        selectedPromotion: '',
        taxRatePercent: {{ config('pos.tax_rate', 5) }},
        showPayModal: false,
        isSubmitting: false,
        idempotencyKey: 'IDEM-' + Date.now() + '-' + Math.random().toString(36).substring(2, 8),
        paymentSplits: [
            { payment_method: 'cash', amount: 0, tendered_amount: 0, reference: '' }
        ],

        handleSearchOrScan() {
            let q = this.searchQuery.trim();
            if (!q) return;

            fetch('{{ route('pos.search') }}?q=' + encodeURIComponent(q))
                .then(r => r.json())
                .then(data => {
                    if (data.length === 1) {
                        this.addToCart(data[0]);
                        this.searchQuery = '';
                        this.searchResults = [];
                    } else if (data.length > 1) {
                        this.searchResults = data;
                    } else {
                        alert('No product found for barcode / SKU: ' + q);
                    }
                })
                .catch(() => alert('Search network error'));
        },

        addToCart(item) {
            let existing = this.cart.find(c => c.variant_id === item.id);
            if (existing) {
                existing.quantity += 1;
            } else {
                this.cart.push({
                    variant_id: item.id,
                    name: item.name,
                    color: item.color,
                    size: item.size,
                    barcode: item.barcode,
                    price: Number(item.price),
                    quantity: 1
                });
            }
            this.searchResults = [];
            this.searchQuery = '';
            this.$refs.barcodeInput.focus();
        },

        incrementQty(idx) {
            this.cart[idx].quantity += 1;
        },

        decrementQty(idx) {
            if (this.cart[idx].quantity > 1) {
                this.cart[idx].quantity -= 1;
            } else {
                this.removeLine(idx);
            }
        },

        removeLine(idx) {
            this.cart.splice(idx, 1);
        },

        clearCart() {
            if (confirm('Clear all items from current cart?')) {
                this.cart = [];
            }
        },

        totalItemCount() {
            return this.cart.reduce((sum, line) => sum + line.quantity, 0);
        },

        subtotal() {
            return this.cart.reduce((sum, line) => sum + (line.price * line.quantity), 0);
        },

        promoDiscount() {
            if (!this.selectedPromotion) return 0;
            // E.g. 10% or similar
            return this.subtotal() * 0.10;
        },

        taxableAmount() {
            return Math.max(0, this.subtotal() - this.promoDiscount());
        },

        vatAmount() {
            return this.taxableAmount() * (this.taxRatePercent / 100);
        },

        rawTotal() {
            return this.taxableAmount() + this.vatAmount();
        },

        roundedTotal() {
            return Math.round(this.rawTotal());
        },

        roundingAdjustment() {
            return this.roundedTotal() - this.rawTotal();
        },

        openPaymentModal() {
            let total = this.roundedTotal();
            this.paymentSplits = [
                { payment_method: 'cash', amount: total, tendered_amount: total, reference: '' }
            ];
            this.showPayModal = true;
        },

        addSplit() {
            this.paymentSplits.push({ payment_method: 'bkash', amount: 0, tendered_amount: 0, reference: '' });
        },

        removeSplit(idx) {
            if (this.paymentSplits.length > 1) {
                this.paymentSplits.splice(idx, 1);
            }
        },

        totalAllocated() {
            return this.paymentSplits.reduce((sum, p) => sum + Number(p.amount || 0), 0);
        },

        remainingDue() {
            return Math.max(0, this.roundedTotal() - this.totalAllocated());
        },

        cashChange() {
            let cashSplit = this.paymentSplits.find(p => p.payment_method === 'cash');
            if (cashSplit && cashSplit.tendered_amount > cashSplit.amount) {
                return cashSplit.tendered_amount - cashSplit.amount;
            }
            return 0;
        },

        submitCheckout() {
            if (this.remainingDue() !== 0) {
                alert('Allocated payment total must equal invoice total ৳' + this.roundedTotal());
                return;
            }

            this.isSubmitting = true;

            let payload = {
                items: this.cart.map(c => ({
                    product_variant_id: c.variant_id,
                    quantity: c.quantity
                })),
                payments: this.paymentSplits.map(p => ({
                    payment_method: p.payment_method,
                    amount: p.amount,
                    tendered_amount: p.tendered_amount,
                    reference: p.reference
                })),
                customer_phone: this.customerPhone,
                customer_name: this.customerName,
                promotion_code: this.selectedPromotion,
                idempotency_key: this.idempotencyKey
            };

            fetch('{{ route('pos.checkout') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify(payload)
            })
            .then(res => res.json())
            .then(data => {
                this.isSubmitting = false;
                if (data.success) {
                    window.location.href = data.receipt_url;
                } else {
                    alert('Checkout Error: ' + (data.message || 'Validation failed.'));
                }
            })
            .catch(err => {
                this.isSubmitting = false;
                alert('Network connection lost or checkout failed.');
            });
        }
    }
}
</script>
@endsection
