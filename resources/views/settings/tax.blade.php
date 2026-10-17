@extends('layouts.app')

@section('title', 'Tax / VAT Configuration — Super Admin')
@section('header', 'Tax & VAT Configuration')

@section('content')
<div class="max-w-4xl mx-auto space-y-6" x-data="{
    vatEnabled: {{ $vatEnabled ? 'true' : 'false' }},
    vatRate: '{{ number_format($vatRate, 2, '.', '') }}',
    vatLabel: '{{ addslashes($vatLabel) }}',
    setRate(rate) {
        this.vatRate = rate.toFixed(2);
        if (rate === 0) {
            this.vatEnabled = false;
        } else {
            this.vatEnabled = true;
        }
    }
}">

    <!-- FLASH NOTIFICATION -->
    @if(session('success'))
        <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm font-semibold flex items-center justify-between shadow-sm">
            <div class="flex items-center gap-2.5">
                <svg class="w-5 h-5 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                <span>{{ session('success') }}</span>
            </div>
            <a href="{{ route('pos.index') }}" class="px-3 py-1 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-lg transition shadow-sm">
                Open POS &rarr;
            </a>
        </div>
    @endif

    <!-- OWNER BANNER -->
    <div class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 shadow-sm relative overflow-hidden">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="space-y-1.5">
                <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-indigo-50 text-indigo-700 border border-indigo-200 dark:bg-indigo-950/60 dark:text-indigo-300 dark:border-indigo-800 text-[11px] font-bold">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span>Shop Owner Authority (Super Admin)</span>
                </div>
                <h2 class="text-xl font-extrabold text-slate-900 dark:text-white tracking-tight">Tax & VAT System Configuration</h2>
                <p class="text-xs text-slate-600 dark:text-slate-400 max-w-2xl leading-relaxed">
                    Configure or completely turn OFF Tax / VAT for customer checkouts in your stores. When VAT is turned OFF, <strong>৳0.00</strong> tax will be added to the customer bill and VAT will not appear on receipts.
                </p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('pos.index') }}" class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 font-bold text-xs transition">
                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                    <span>Point of Sale (POS)</span>
                </a>
            </div>
        </div>
    </div>

    <!-- LIVE STATUS SUMMARY CARD -->
    <div class="p-5 rounded-2xl border transition-all"
         :class="vatEnabled && parseFloat(vatRate) > 0 ? 'bg-emerald-50/60 border-emerald-200 dark:bg-emerald-950/20 dark:border-emerald-800/60' : 'bg-slate-50 border-slate-200 dark:bg-slate-800/40 dark:border-slate-700'">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-xl flex items-center justify-center font-black text-xl"
                     :class="vatEnabled && parseFloat(vatRate) > 0 ? 'bg-emerald-600 text-white' : 'bg-slate-400 text-white'">
                    <span x-text="vatEnabled && parseFloat(vatRate) > 0 ? '%' : '✕'"></span>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-bold text-slate-900 dark:text-white">Current Tax Status:</span>
                        <span class="px-2 py-0.5 rounded-full text-xs font-black uppercase tracking-wider"
                              :class="vatEnabled && parseFloat(vatRate) > 0 ? 'bg-emerald-100 text-emerald-800 border border-emerald-300 dark:bg-emerald-900 dark:text-emerald-200' : 'bg-rose-100 text-rose-800 border border-rose-300 dark:bg-rose-900 dark:text-rose-200'"
                              x-text="vatEnabled && parseFloat(vatRate) > 0 ? 'ACTIVE (' + parseFloat(vatRate) + '%)' : 'TURNED OFF (0%)'">
                        </span>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5"
                       x-text="vatEnabled && parseFloat(vatRate) > 0 ? 'Customers will be charged ' + parseFloat(vatRate) + '% ' + vatLabel + ' at checkout.' : 'No tax or VAT will be charged to customers. Bills will be subtotal only.'">
                    </p>
                </div>
            </div>

            <!-- QUICK TOGGLE ACTION -->
            <div class="flex items-center gap-2">
                <button type="button" @click="setRate(0)" x-show="vatEnabled && parseFloat(vatRate) > 0"
                        class="px-3.5 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs shadow-sm transition">
                    🛑 Turn OFF VAT Now
                </button>
                <button type="button" @click="setRate(5)" x-show="!vatEnabled || parseFloat(vatRate) === 0"
                        class="px-3.5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-sm transition">
                    ✓ Turn ON (5% Standard)
                </button>
            </div>
        </div>
    </div>

    <!-- MAIN FORM -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/90 dark:border-slate-800 shadow-sm p-6 space-y-6">
        <form action="{{ route('admin.tax.update') }}" method="POST" class="space-y-6">
            @csrf

            <!-- SECTION 1: MASTER TOGGLE -->
            <div class="space-y-3 pb-6 border-b border-slate-100 dark:border-slate-800">
                <label class="block text-xs font-black uppercase tracking-wider text-slate-500">
                    Step 1: Master Tax / VAT Switch
                </label>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- ENABLED OPTION -->
                    <label class="relative flex items-start gap-3 p-4 rounded-xl border-2 cursor-pointer transition"
                           :class="vatEnabled ? 'border-emerald-500 bg-emerald-50/40 dark:bg-emerald-950/20' : 'border-slate-200 dark:border-slate-800 hover:border-slate-300'">
                        <input type="radio" name="vat_enabled" value="1" x-model="vatEnabled" class="mt-1 text-emerald-600 focus:ring-emerald-500">
                        <div>
                            <span class="block text-sm font-bold text-slate-900 dark:text-white">Enable Tax / VAT</span>
                            <span class="block text-xs text-slate-500 mt-0.5">Charge VAT on every POS retail sale based on configured percentage.</span>
                        </div>
                    </label>

                    <!-- DISABLED OPTION -->
                    <label class="relative flex items-start gap-3 p-4 rounded-xl border-2 cursor-pointer transition"
                           :class="!vatEnabled ? 'border-rose-500 bg-rose-50/40 dark:bg-rose-950/20' : 'border-slate-200 dark:border-slate-800 hover:border-slate-300'">
                        <input type="radio" name="vat_enabled" value="0" x-model="vatEnabled" class="mt-1 text-rose-600 focus:ring-rose-500">
                        <div>
                            <span class="block text-sm font-bold text-slate-900 dark:text-white">Disable / Turn OFF (0%)</span>
                            <span class="block text-xs text-slate-500 mt-0.5">Turn OFF tax completely. Subtotal equals final payable amount.</span>
                        </div>
                    </label>
                </div>
            </div>

            <!-- SECTION 2: VAT RATE PERCENTAGE -->
            <div class="space-y-4 pb-6 border-b border-slate-100 dark:border-slate-800">
                <div class="flex items-center justify-between">
                    <div>
                        <label for="vat_rate_input" class="block text-xs font-black uppercase tracking-wider text-slate-500">
                            Step 2: VAT Rate Percentage (%)
                        </label>
                        <p class="text-xs text-slate-400 mt-0.5">
                            Enter the percentage to be calculated on taxable subtotal (after discounts).
                        </p>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                    <div class="relative w-full sm:w-48">
                        <input type="number" step="0.01" min="0" max="100" id="vat_rate_input" name="vat_rate" x-model="vatRate"
                               :disabled="!vatEnabled"
                               class="w-full text-lg font-black text-slate-900 dark:text-white dark:bg-slate-800 rounded-xl border-slate-300 dark:border-slate-700 pl-4 pr-10 py-2.5 focus:ring-2 focus:ring-indigo-500 disabled:opacity-50 disabled:bg-slate-100">
                        <span class="absolute right-3.5 top-3 font-bold text-slate-400">%</span>
                    </div>

                    <!-- PRESET BUTTONS -->
                    <div class="flex flex-wrap items-center gap-2">
                        <button type="button" @click="setRate(0)" class="px-3 py-2 rounded-xl text-xs font-bold border transition"
                                :class="parseFloat(vatRate) === 0 ? 'bg-slate-800 text-white border-slate-800' : 'bg-slate-50 hover:bg-slate-100 text-slate-700 border-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700'">
                            0% (OFF)
                        </button>
                        <button type="button" @click="setRate(5)" class="px-3 py-2 rounded-xl text-xs font-bold border transition"
                                :class="parseFloat(vatRate) === 5 ? 'bg-emerald-600 text-white border-emerald-600' : 'bg-slate-50 hover:bg-slate-100 text-slate-700 border-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700'">
                            5% (Fashion Standard)
                        </button>
                        <button type="button" @click="setRate(7.5)" class="px-3 py-2 rounded-xl text-xs font-bold border transition"
                                :class="parseFloat(vatRate) === 7.5 ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-slate-50 hover:bg-slate-100 text-slate-700 border-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700'">
                            7.5% (Retail)
                        </button>
                        <button type="button" @click="setRate(15)" class="px-3 py-2 rounded-xl text-xs font-bold border transition"
                                :class="parseFloat(vatRate) === 15 ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-slate-50 hover:bg-slate-100 text-slate-700 border-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700'">
                            15% (NBR Standard)
                        </button>
                    </div>
                </div>
            </div>

            <!-- SECTION 3: DISPLAY LABEL -->
            <div class="space-y-3 pb-6 border-b border-slate-100 dark:border-slate-800">
                <label for="vat_label_input" class="block text-xs font-black uppercase tracking-wider text-slate-500">
                    Step 3: Receipt & Display Label
                </label>
                <div class="max-w-md">
                    <input type="text" id="vat_label_input" name="vat_label" x-model="vatLabel" maxlength="50"
                           class="w-full text-sm font-semibold text-slate-900 dark:text-white dark:bg-slate-800 rounded-xl border-slate-300 dark:border-slate-700 px-3.5 py-2.5 focus:ring-2 focus:ring-indigo-500">
                    <p class="text-[11px] text-slate-400 mt-1">Default is "Tax / VAT". You may write "VAT", "Govt Tax (মূসক)", or "Sales Tax".</p>
                </div>
            </div>

            <!-- SAVE BUTTON -->
            <div class="flex items-center justify-between pt-2">
                <div class="text-xs text-slate-500">
                    Changes take effect <strong>immediately</strong> on all registers and open carts.
                </div>
                <div class="flex items-center gap-3">
                    <a href="{{ route('pos.index') }}" class="px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 text-xs font-bold hover:bg-slate-50 dark:hover:bg-slate-800 transition">
                        Cancel
                    </a>
                    <button type="submit" class="px-6 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-black shadow-md shadow-indigo-600/30 transition flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span>Save Tax Settings</span>
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- INSTRUCTIONS / GUIDANCE CARD IN BENGALI & ENGLISH -->
    <div class="p-5 rounded-2xl bg-amber-50/70 border border-amber-200/90 dark:bg-amber-950/20 dark:border-amber-800 text-amber-900 dark:text-amber-200 text-xs space-y-2">
        <div class="flex items-center gap-2 font-bold text-sm text-amber-800 dark:text-amber-300">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span>নির্দেশিকা (How it works):</span>
        </div>
        <ul class="list-disc pl-5 space-y-1 text-slate-700 dark:text-slate-300">
            <li><strong>ট্যাক্স বন্ধ করতে (OFF):</strong> "Disable / Turn OFF (0%)" সিলেক্ট করুন অথবা "0% (OFF)" বাটনে ক্লিক করে "Save Tax Settings" চাপুন। এরপর বিক্রির সময় কোনো ট্যাক্স বা ভ্যাট যোগ হবে না।</li>
            <li><strong>ট্যাক্স রেট পরিবর্তন করতে:</strong> ইচ্ছামতো পার্সেন্টেজ লিখুন (যেমন 5%, 7.5% বা 15%) এবং Save করুন।</li>
            <li><strong>POS স্ক্রিন থেকে সরাসরি:</strong> আপনি সরাসরি ক্যাশিয়ারের POS স্ক্রিনের Cart সামারির "Tax / VAT" লেখার পাশের <strong>[⚙️ Config]</strong> বাটনে ক্লিক করেও এটি সাথে সাথে চালু বা বন্ধ করতে পারবেন।</li>
        </ul>
    </div>

</div>
@endsection
