@extends('layouts.app')

@section('title', 'Site Branding & Identity — Super Admin')
@section('header', 'Site Branding & Identity')

@section('content')
<div class="max-w-5xl mx-auto space-y-6" x-data="{
    appName: '{{ addslashes($appName) }}',
    appTagline: '{{ addslashes($appTagline) }}',
    companyAddress: '{{ addslashes($companyAddress) }}',
    companyPhone: '{{ addslashes($companyPhone) }}',
    receiptFooter: '{{ addslashes($receiptFooter) }}',
    currentLogo: '{{ $appLogo ? addslashes($appLogo) : '' }}',
    previewLogo: '{{ $appLogo ? addslashes($appLogo) : '' }}',
    removeLogo: false,
    handleLogoChange(event) {
        const file = event.target.files[0];
        if (file) {
            this.removeLogo = false;
            const reader = new FileReader();
            reader.onload = (e) => {
                this.previewLogo = e.target.result;
            };
            reader.readAsDataURL(file);
        }
    },
    triggerRemoveLogo() {
        this.removeLogo = true;
        this.previewLogo = '';
        const input = document.getElementById('logoFileInput');
        if (input) input.value = '';
    },
    get initials() {
        const words = (this.appName || 'POS').trim().split(/\s+/);
        if (words.length >= 2) {
            return (words[0][0] + words[1][0]).toUpperCase();
        }
        return (this.appName || 'POS').substring(0, 2).toUpperCase();
    }
}">

    <!-- FLASH NOTIFICATION -->
    @if(session('success'))
        <div class="p-4 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-200 text-sm font-semibold flex items-center justify-between shadow-sm">
            <div class="flex items-center gap-2.5">
                <svg class="w-5 h-5 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                <span>{{ session('success') }}</span>
            </div>
            <a href="{{ route('dashboard') }}" class="px-3 py-1 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-lg transition shadow-sm">
                View Dashboard &rarr;
            </a>
        </div>
    @endif

    <!-- OWNER BANNER -->
    <div class="p-6 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 shadow-sm relative overflow-hidden">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="space-y-1.5">
                <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-indigo-50 text-indigo-700 border border-indigo-200 dark:bg-indigo-950/60 dark:text-indigo-300 dark:border-indigo-800 text-[11px] font-bold">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span>Super Admin Authority (Site Ownership)</span>
                </div>
                <h2 class="text-xl font-extrabold text-slate-900 dark:text-white tracking-tight">Site Name & Logo Configuration</h2>
                <p class="text-xs text-slate-600 dark:text-slate-400 max-w-2xl leading-relaxed">
                    Personalize your system branding. Changes to site logo, name, and slogan update instantly across the sidebar, navigation, login portal, POS receipts, and official store documents.
                </p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('pos.index') }}" class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 font-bold text-xs transition">
                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                    <span>Point of Sale</span>
                </a>
                <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs transition shadow-sm shadow-indigo-500/20">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                    <span>Dashboard</span>
                </a>
            </div>
        </div>
    </div>

    <!-- LIVE BRANDING PREVIEW -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <!-- Sidebar Brand Preview Card -->
        <div class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 shadow-sm space-y-3">
            <div class="flex items-center justify-between text-xs font-bold text-slate-500 uppercase tracking-wider">
                <span class="flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"/></svg>
                    <span>Sidebar Live</span>
                </span>
                <span class="text-[10px] bg-slate-100 dark:bg-slate-800 px-2 py-0.5 rounded text-slate-500">Navigation</span>
            </div>

            <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 flex items-center justify-between min-h-[96px]">
                <div class="flex items-center gap-3 min-w-0">
                    <template x-if="previewLogo && !removeLogo">
                        <img :src="previewLogo" alt="Brand Logo" class="w-10 h-10 object-contain rounded-xl bg-white dark:bg-slate-900 p-1 border border-slate-200 dark:border-slate-700 shadow-sm flex-shrink-0">
                    </template>
                    <template x-if="!previewLogo || removeLogo">
                        <div class="w-10 h-10 rounded-xl bg-indigo-600 text-white flex items-center justify-center font-black text-sm shadow-md shadow-indigo-600/30 flex-shrink-0" x-text="initials">
                        </div>
                    </template>
                    <div class="min-w-0">
                        <div class="font-extrabold text-sm tracking-tight text-slate-900 dark:text-white leading-none truncate" x-text="appName || 'FashionPOS'">
                        </div>
                        <div class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded border text-[10px] font-bold tracking-tight mt-1 bg-indigo-50 text-indigo-700 border-indigo-200 dark:bg-indigo-950/60 dark:text-indigo-300 dark:border-indigo-800">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                            <span>Executive HQ</span>
                        </div>
                    </div>
                </div>
            </div>
            <p class="text-[11px] text-slate-500">
                Shows at the top-left of the application sidebar for all logged-in staff.
            </p>
        </div>

        <!-- Barcode Label Live Preview Card -->
        <div class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 shadow-sm space-y-3">
            <div class="flex items-center justify-between text-xs font-bold text-slate-500 uppercase tracking-wider">
                <span class="flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                    <span>Barcode Label Brand</span>
                </span>
                <span class="text-[10px] bg-slate-100 dark:bg-slate-800 px-2 py-0.5 rounded text-slate-500">Barcode Top</span>
            </div>

            <div class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 flex flex-col items-center justify-center min-h-[96px]">
                <div class="w-full max-w-[210px] bg-white border border-slate-300 rounded p-1.5 text-slate-900 shadow-2xs flex flex-col items-center">
                    <!-- Brand Name at top center -->
                    <div class="text-[10px] font-black uppercase tracking-wider text-slate-900 truncate max-w-full text-center leading-tight" x-text="appName || 'FASHION STORE'"></div>
                    <!-- Simulated Barcode -->
                    <div class="w-32 h-5 flex items-center justify-center gap-0.5 my-0.5">
                        <div class="h-full w-0.5 bg-black"></div><div class="h-full w-1 bg-black"></div><div class="h-full w-0.5 bg-black"></div>
                        <div class="h-full w-1.5 bg-black"></div><div class="h-full w-0.5 bg-black"></div><div class="h-full w-1 bg-black"></div>
                        <div class="h-full w-0.5 bg-black"></div><div class="h-full w-1.5 bg-black"></div><div class="h-full w-1 bg-black"></div>
                        <div class="h-full w-0.5 bg-black"></div><div class="h-full w-1 bg-black"></div><div class="h-full w-0.5 bg-black"></div>
                    </div>
                    <div class="text-[8px] font-mono font-bold tracking-widest text-slate-700">880123456789</div>
                    <div class="w-full flex items-center justify-between text-[8px] border-t border-dashed border-slate-200 pt-0.5 mt-0.5">
                        <span class="truncate font-semibold text-slate-600">Premium Polo • L</span>
                        <span class="font-black text-slate-900 font-mono">৳1,250</span>
                    </div>
                </div>
            </div>
            <p class="text-[11px] text-slate-500">
                Printed at the top of 50×30mm thermal barcode stickers and A4 sheets.
            </p>
        </div>

        <!-- Receipt / Invoice Header Preview Card -->
        <div class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 shadow-sm space-y-3">
            <div class="flex items-center justify-between text-xs font-bold text-slate-500 uppercase tracking-wider">
                <span class="flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <span>Print Challan / Memo</span>
                </span>
                <span class="text-[10px] bg-slate-100 dark:bg-slate-800 px-2 py-0.5 rounded text-slate-500">Receipts</span>
            </div>

            <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 flex flex-col items-center text-center space-y-0.5 min-h-[96px] justify-center">
                <template x-if="previewLogo && !removeLogo">
                    <img :src="previewLogo" alt="Brand Logo" class="h-6 max-w-[100px] object-contain mb-1">
                </template>
                <div class="font-extrabold text-xs text-slate-900 dark:text-white tracking-wide uppercase truncate max-w-full" x-text="appName || 'CLOTHING STORE BD'"></div>
                <div class="text-[10px] text-slate-500 truncate max-w-full" x-text="companyAddress || 'Level 3, Bashundhara City, Dhaka'"></div>
                <div class="text-[9px] font-mono text-slate-400">Helpline: <span x-text="companyPhone || '01700-000000'"></span></div>
            </div>
            <p class="text-[11px] text-slate-500">
                Official headers on stock transfer challans and POS customer receipts.
            </p>
        </div>
    </div>

    <!-- MAIN SETTINGS FORM -->
    <form action="{{ route('admin.branding.update') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf

        <input type="hidden" name="remove_logo" :value="removeLogo ? '1' : '0'">

        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/90 dark:border-slate-800 shadow-sm overflow-hidden">
            <!-- Header -->
            <div class="p-5 border-b border-slate-200/90 dark:border-slate-800 flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 border border-indigo-200 dark:border-indigo-800 flex items-center justify-center text-indigo-600 dark:text-indigo-400 font-bold">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/></svg>
                </div>
                <div>
                    <h3 class="font-extrabold text-base text-slate-900 dark:text-white">Brand Assets & Identity Details</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Configure your store's graphic logo and official corporate information</p>
                </div>
            </div>

            <div class="p-6 space-y-6">
                <!-- LOGO UPLOAD & CURRENT ASSET -->
                <div class="grid grid-cols-1 md:grid-cols-12 gap-6 items-start">
                    <div class="md:col-span-4">
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">
                            Brand / Site Logo
                        </label>
                        <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                            Upload your official business logo. Transparent PNG, WEBP, or SVG with a square or horizontal ratio works best. Maximum size: 3MB.
                        </p>
                    </div>

                    <div class="md:col-span-8 space-y-4">
                        <div class="flex flex-wrap items-center gap-4">
                            <!-- Logo Preview Thumbnail -->
                            <div class="w-24 h-24 rounded-2xl border-2 border-dashed border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50 p-2 flex items-center justify-center relative overflow-hidden flex-shrink-0">
                                <template x-if="previewLogo && !removeLogo">
                                    <img :src="previewLogo" alt="Logo Preview" class="w-full h-full object-contain">
                                </template>
                                <template x-if="!previewLogo || removeLogo">
                                    <div class="flex flex-col items-center justify-center text-slate-400 text-center">
                                        <svg class="w-8 h-8 mb-1 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                        <span class="text-[10px] font-bold" x-text="initials"></span>
                                    </div>
                                </template>
                            </div>

                            <!-- Upload & Remove Buttons -->
                            <div class="space-y-2">
                                <label class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-950/60 dark:hover:bg-indigo-900/60 border border-indigo-200 dark:border-indigo-800 text-indigo-700 dark:text-indigo-300 font-bold text-xs cursor-pointer transition shadow-2xs">
                                    <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                                    <span>Choose New Logo File</span>
                                    <input type="file" id="logoFileInput" name="logo" accept="image/png,image/jpeg,image/webp,image/svg+xml" class="hidden" @change="handleLogoChange($event)">
                                </label>

                                <template x-if="(previewLogo && !removeLogo) || currentLogo">
                                    <button type="button" @click="triggerRemoveLogo()" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/60 dark:hover:bg-rose-900/60 border border-rose-200 dark:border-rose-800 text-rose-700 dark:text-rose-300 font-bold text-xs transition">
                                        <svg class="w-3.5 h-3.5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        <span>Remove Logo</span>
                                    </button>
                                </template>

                                <div class="text-[11px] text-slate-500">
                                    Formats: PNG, JPG, WEBP, SVG • Recommended: 256x256 or 512x512 px
                                </div>
                            </div>
                        </div>

                        <template x-if="removeLogo">
                            <div class="p-2.5 rounded-lg bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800 text-amber-800 dark:text-amber-300 text-xs flex items-center gap-2">
                                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                <span>Logo will be removed and initials icon will be displayed once saved.</span>
                            </div>
                        </template>

                        @error('logo')
                            <p class="text-xs text-rose-600 font-semibold">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="border-t border-slate-200 dark:border-slate-800 pt-6"></div>

                <!-- TEXT INPUTS: SITE NAME & TAGLINE -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="app_name" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                            Site / Brand Name <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" id="app_name" name="app_name" x-model="appName" required maxlength="100"
                               placeholder="e.g. FashionPOS / Richman / Artisan"
                               class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-4 py-2.5 text-sm font-semibold text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">
                            Main name displayed on the browser title, sidebar header, and login screen.
                        </p>
                        @error('app_name')
                            <p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="app_tagline" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                            Tagline / Slogan
                        </label>
                        <input type="text" id="app_tagline" name="app_tagline" x-model="appTagline" maxlength="200"
                               placeholder="e.g. Smart Retail POS & Multi-Store Management"
                               class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-4 py-2.5 text-sm font-semibold text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">
                            Brief subtitle displayed on the login page and portal headers.
                        </p>
                        @error('app_tagline')
                            <p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="border-t border-slate-200 dark:border-slate-800 pt-6"></div>

                <!-- CONTACT & HEAD OFFICE DETAILS -->
                <div class="space-y-4">
                    <h4 class="text-xs font-extrabold text-slate-900 dark:text-white uppercase tracking-wider">
                        Official Head Office & Receipt Information
                    </h4>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <label for="company_address" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                                Head Office Address
                            </label>
                            <input type="text" id="company_address" name="company_address" x-model="companyAddress" maxlength="255"
                                   placeholder="Level 3, Bashundhara City, Dhaka"
                                   class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3.5 py-2 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                            @error('company_address')
                                <p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="company_phone" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                                Central Helpline / Phone
                            </label>
                            <input type="text" id="company_phone" name="company_phone" x-model="companyPhone" maxlength="50"
                                   placeholder="01700-000000"
                                   class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3.5 py-2 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                            @error('company_phone')
                                <p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="company_email" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                                Official Email
                            </label>
                            <input type="email" id="company_email" name="company_email" value="{{ old('company_email', $companyEmail) }}" maxlength="100"
                                   placeholder="contact@brand.com"
                                   class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3.5 py-2 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                            @error('company_email')
                                <p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div>
                        <label for="receipt_footer" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                            Customer Receipt Footer Note / Website
                        </label>
                        <input type="text" id="receipt_footer" name="receipt_footer" x-model="receiptFooter" maxlength="255"
                               placeholder="Thank you for shopping with us! • www.yourbrand.com"
                               class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-4 py-2.5 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">
                            Printed at the bottom of customer purchase receipts and exchange memos.
                        </p>
                        @error('receipt_footer')
                            <p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Card Footer: Save Actions -->
            <div class="px-6 py-4 bg-slate-50 dark:bg-slate-800/50 border-t border-slate-200/90 dark:border-slate-800 flex items-center justify-between">
                <div class="text-xs text-slate-500">
                    <span class="inline-block w-2 h-2 rounded-full bg-emerald-500 mr-1.5"></span>
                    Changes take effect across the system immediately upon saving.
                </div>
                <div class="flex items-center gap-3">
                    <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs shadow-md shadow-indigo-600/20 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span>Save Site Settings</span>
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection
