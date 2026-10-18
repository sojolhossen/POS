<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" 
      x-data="{ 
          darkMode: document.documentElement.classList.contains('dark'),
          sidebarOpen: false,
          isOnline: navigator.onLine,
          checkConnectivity() {
              fetch('{{ route('api.ping') }}')
                  .then(res => { this.isOnline = res.ok; })
                  .catch(() => { this.isOnline = false; });
          }
      }" 
      x-init="
          window.addEventListener('pos-theme-changed', e => darkMode = e.detail.darkMode);
          window.addEventListener('online', () => isOnline = true);
          window.addEventListener('offline', () => isOnline = false);
          setInterval(() => checkConnectivity(), 25000);
      "
      :class="{ 'dark': darkMode }">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Immediate Theme Initialization Script (Prevents FOUC & Guarantees Instant Dark/Light Mode) -->
    <script>
        (function() {
            try {
                var savedTheme = localStorage.getItem('pos_theme');
                var userTheme = '{{ auth()->user()?->theme }}' || 'light';
                var isDark = savedTheme !== null ? (savedTheme === 'dark') : (userTheme === 'dark');
                if (isDark) {
                    document.documentElement.classList.add('dark');
                } else {
                    document.documentElement.classList.remove('dark');
                }
            } catch (e) {}
        })();

        // Robust Global Theme Toggle Function
        function togglePosTheme() {
            try {
                var html = document.documentElement;
                var isDark = html.classList.toggle('dark');
                var themeName = isDark ? 'dark' : 'light';
                localStorage.setItem('pos_theme', themeName);

                // Notify Alpine component & any listeners
                window.dispatchEvent(new CustomEvent('pos-theme-changed', { detail: { darkMode: isDark } }));

                // Background sync to server if authenticated
                var tokenMeta = document.querySelector('meta[name="csrf-token"]');
                if (tokenMeta) {
                    fetch('{{ route('theme.set') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': tokenMeta.getAttribute('content'),
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ theme: themeName })
                    }).catch(function() {});
                }
                return isDark;
            } catch (err) {
                console.error('Failed to toggle theme:', err);
            }
        }
    </script>

    <title>@yield('title', \App\Models\AppSetting::getAppName()) — {{ \App\Models\AppSetting::getAppName() }}</title>
    @if(\App\Models\AppSetting::getAppFavicon())
        <link rel="icon" href="{{ \App\Models\AppSetting::getAppFavicon() }}">
    @endif

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Noto+Sans+Bengali:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body {
            font-family: 'Plus Jakarta Sans', 'Noto Sans Bengali', -apple-system, BlinkMacSystemFont, sans-serif;
        }
        [x-cloak] { display: none !important; }

        /* Permanent Fixed Sidebar and Main Content Offset */
        @media (min-width: 1024px) {
            .app-sidebar {
                position: fixed !important;
                top: 0 !important;
                bottom: 0 !important;
                left: 0 !important;
                width: 16rem !important; /* 256px */
                height: 100vh !important;
                height: 100dvh !important;
                z-index: 40 !important;
            }
            .app-main-wrapper {
                margin-left: 16rem !important; /* Physically shift page content 256px to the right */
                width: calc(100% - 16rem) !important;
                min-width: 0 !important;
            }
        }
        @media (max-width: 1023.98px) {
            .app-main-wrapper {
                margin-left: 0 !important;
                width: 100% !important;
            }
        }
        @media print {
            .no-print { display: none !important; }
            .app-sidebar { display: none !important; }
            .app-main-wrapper {
                margin-left: 0 !important;
                width: 100% !important;
                padding-left: 0 !important;
            }
        }

        /* Sleek scrollbar for sidebar navigation */
        .sidebar-nav-scroll::-webkit-scrollbar {
            width: 4px;
        }
        .sidebar-nav-scroll::-webkit-scrollbar-track {
            background: transparent;
        }
        .sidebar-nav-scroll::-webkit-scrollbar-thumb {
            background: rgba(148, 163, 184, 0.25);
            border-radius: 4px;
        }
        .sidebar-nav-scroll::-webkit-scrollbar-thumb:hover {
            background: rgba(148, 163, 184, 0.45);
        }
    </style>
    @stack('styles')
</head>
<body class="bg-slate-50 dark:bg-[#0b0f19] text-slate-900 dark:text-slate-100 min-h-screen flex flex-col font-sans antialiased selection:bg-indigo-500 selection:text-white transition-colors duration-200">

    <!-- CONNECTIVITY SAFEGUARD MODAL (Section 9 Requirement) -->
    <div x-cloak x-show="!isOnline" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/85 backdrop-blur-md p-4">
        <div class="bg-white dark:bg-slate-900 border-2 border-rose-500 rounded-2xl max-w-md w-full p-6 text-center shadow-2xl">
            <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-rose-100 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 flex items-center justify-center">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636a9 9 0 010 12.728m0 0l-2.829-2.829m2.829 2.829L21 21M15.536 8.464a5 5 0 010 7.072m0 0l-2.829-2.829m-4.243 4.243a9 9 0 01-12.728 0m0 0l2.829-2.829m-2.829 2.829L3 21m2.829-12.728a5 5 0 017.072 0m-7.072 0L3 3m18 0l-2.828 2.828" />
                </svg>
            </div>
            <h2 class="text-xl font-bold text-rose-600 dark:text-rose-400 mb-2">{{ __('messages.offline_banner_title') }}</h2>
            <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-400 mb-6 leading-relaxed">
                {{ __('messages.offline_banner_desc') }}
            </p>
            <button @click="checkConnectivity()" class="w-full py-2.5 px-4 bg-rose-600 hover:bg-rose-700 text-white rounded-xl font-bold text-xs uppercase tracking-wider transition shadow-lg shadow-rose-600/30">
                Retry Server Connection
            </button>
        </div>
    </div>

    <!-- MAIN APP WRAPPER -->
    <div class="min-h-screen bg-slate-50 dark:bg-[#0b0f19]">
        
        <!-- MOBILE BACKDROP -->
        <div x-show="sidebarOpen" x-cloak @click="sidebarOpen = false"
             class="fixed inset-0 z-30 bg-slate-950/60 backdrop-blur-sm lg:hidden transition-opacity"></div>

        <!-- SIDEBAR (PERMANENTLY FIXED ON DESKTOP & SLIDE-OVER ON MOBILE) -->
        <aside class="app-sidebar no-print fixed inset-y-0 left-0 z-40 w-64 h-screen h-[100dvh] bg-white dark:bg-slate-900 border-r border-slate-200/90 dark:border-slate-800 flex flex-col transition-transform duration-300 lg:translate-x-0"
               :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'">
            
            <!-- Logo & Brand Header with Integrated Persona Panel Badge -->
            @php
                $user = auth()->user();
                if ($user->isSuperAdmin()) {
                    $panelTitle = 'Super Admin Panel';
                    $badgeStyle = 'bg-indigo-50 text-indigo-700 border-indigo-200 dark:bg-indigo-950/60 dark:text-indigo-300 dark:border-indigo-800';
                    $dotColor = 'bg-indigo-500';
                } elseif ($user->isStoreManager()) {
                    $panelTitle = 'Store Manager Hub';
                    $badgeStyle = 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-800';
                    $dotColor = 'bg-emerald-500';
                } elseif ($user->isCashier()) {
                    $panelTitle = 'Cashier POS Hub';
                    $badgeStyle = 'bg-cyan-50 text-cyan-700 border-cyan-200 dark:bg-cyan-950/60 dark:text-cyan-300 dark:border-cyan-800';
                    $dotColor = 'bg-cyan-500';
                } elseif ($user->isWarehouseStaff()) {
                    $panelTitle = 'Logistics & Warehouse';
                    $badgeStyle = 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/60 dark:text-amber-300 dark:border-amber-800';
                    $dotColor = 'bg-amber-500';
                } elseif ($user->isAccountant()) {
                    $panelTitle = 'Accountant Panel';
                    $badgeStyle = 'bg-violet-50 text-violet-700 border-violet-200 dark:bg-violet-950/60 dark:text-violet-300 dark:border-violet-800';
                    $dotColor = 'bg-violet-500';
                } else {
                    $panelTitle = 'Staff Workstation';
                    $badgeStyle = 'bg-slate-100 text-slate-700 border-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700';
                    $dotColor = 'bg-slate-500';
                }
            @endphp

            @php
                $customLogo = \App\Models\AppSetting::getAppLogo();
                $siteAppName = \App\Models\AppSetting::getAppName('FashionPOS');
                $words = preg_split('/\s+/', trim($siteAppName));
                $initials = count($words) >= 2 
                    ? mb_strtoupper(mb_substr($words[0], 0, 1) . mb_substr($words[1], 0, 1))
                    : mb_strtoupper(mb_substr($siteAppName, 0, 2));
            @endphp

            <div class="h-16 flex items-center justify-between px-4 border-b border-slate-200/90 dark:border-slate-800 flex-shrink-0">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5 min-w-0">
                    @if($customLogo)
                        <img src="{{ $customLogo }}" alt="{{ $siteAppName }}" class="w-9 h-9 object-contain rounded-xl bg-white dark:bg-slate-800 p-0.5 border border-slate-200 dark:border-slate-700 shadow-sm flex-shrink-0">
                    @else
                        <div class="w-9 h-9 rounded-xl bg-indigo-600 text-white flex items-center justify-center font-black text-sm shadow-md shadow-indigo-600/30 flex-shrink-0">
                            {{ $initials }}
                        </div>
                    @endif
                    <div class="min-w-0">
                        <div class="font-extrabold text-sm tracking-tight text-slate-900 dark:text-white leading-none truncate">
                            {{ $siteAppName }}
                        </div>
                        <div class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded border text-[10px] font-bold tracking-tight mt-1 {{ $badgeStyle }}">
                            <span class="w-1.5 h-1.5 rounded-full {{ $dotColor }} animate-pulse"></span>
                            <span class="truncate">{{ $panelTitle }}</span>
                        </div>
                    </div>
                </a>
                <button @click="sidebarOpen = false" class="lg:hidden text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 ml-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <!-- Active Store Context Indicator -->
            <div class="p-3 mx-3 my-2.5 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200/80 dark:border-slate-700/60 text-xs flex-shrink-0">
                <div class="flex items-center justify-between text-[10px] uppercase font-bold tracking-wider text-slate-400 dark:text-slate-500 mb-1">
                    <span>Active Location</span>
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                </div>
                <div class="font-bold text-slate-900 dark:text-slate-100 truncate text-[11px]">
                    @if(auth()->user()->isSuperAdmin())
                        Central Command (All Stores)
                    @elseif(auth()->user()->isStoreManager() && auth()->user()->managedStores->count() > 1)
                        {{ auth()->user()->store?->name ?? auth()->user()->managedStores->first()->name }} (+{{ auth()->user()->managedStores->count() - 1 }} more)
                    @elseif(auth()->user()->store)
                        {{ auth()->user()->store->name }}
                    @elseif(auth()->user()->isStoreManager() && auth()->user()->managedStores->isNotEmpty())
                        {{ auth()->user()->managedStores->first()->name }}
                    @else
                        Unassigned Location
                    @endif
                </div>
                <div class="text-[10px] text-slate-500 font-mono mt-0.5">
                    @if(auth()->user()->isSuperAdmin())
                        Central Warehouse + 6 Stores
                    @elseif(auth()->user()->isStoreManager() && auth()->user()->managedStores->count() > 1)
                        Manager of {{ auth()->user()->managedStores->count() }} Stores
                    @elseif(auth()->user()->store)
                        Code: {{ auth()->user()->store->code }} • {{ auth()->user()->store->is_warehouse ? 'Warehouse' : 'Retail' }}
                    @elseif(auth()->user()->isStoreManager() && auth()->user()->managedStores->isNotEmpty())
                        Code: {{ auth()->user()->managedStores->first()->code }} • {{ auth()->user()->managedStores->first()->is_warehouse ? 'Warehouse' : 'Retail' }}
                    @else
                        Central Warehouse + 6 Stores
                    @endif
                </div>
            </div>

            <!-- Navigation Links -->
            <nav id="sidebar-nav" class="flex-1 overflow-y-auto px-3 py-2 space-y-3.5 text-xs font-semibold sidebar-nav-scroll">
                
                <!-- PRIMARY POS SHORTCUT -->
                <!-- PRIMARY POS SHORTCUT -->
                <div>
                    @if(auth()->user()->canAccessModule('pos'))
                        <a href="{{ route('pos.index') }}" {!! request()->routeIs('pos.*') ? 'data-active-nav="true"' : '' !!} class="flex items-center justify-between px-3.5 py-2.5 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 text-white font-bold shadow-md shadow-emerald-600/25 hover:from-emerald-700 hover:to-teal-700 transition">
                            <div class="flex items-center gap-2.5">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                                <span>{{ __('messages.pos') }}</span>
                            </div>
                            <span class="text-[10px] px-1.5 py-0.5 rounded bg-white/20 uppercase font-black">FAST</span>
                        </a>
                    @else
                        <div class="flex items-center justify-between px-3.5 py-2.5 rounded-xl bg-slate-100 dark:bg-slate-800/50 text-slate-400 dark:text-slate-500 font-bold border border-slate-200/60 dark:border-slate-800 cursor-not-allowed select-none" title="POS is locked by Store Owner (Super Admin)">
                            <div class="flex items-center gap-2.5 opacity-60">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                                <span class="line-through decoration-slate-400/60">{{ __('messages.pos') }}</span>
                            </div>
                            <span class="inline-flex items-center gap-1 text-[10px] font-bold text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-950/70 border border-amber-200 dark:border-amber-900/60 px-1.5 py-0.5 rounded">
                                <svg class="w-3 h-3 text-amber-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                <span>Lock</span>
                            </span>
                        </div>
                    @endif
                </div>

                <!-- Section 1: Retail Operations -->
                <div>
                    <div class="px-3 pb-1 text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">
                        Retail Operations
                    </div>
                    <div class="space-y-0.5">
                        <!-- Dashboard -->
                        <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-3 py-2 rounded-xl transition {{ request()->routeIs('dashboard', 'home') ? 'bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 font-bold' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                            <svg class="w-4 h-4 opacity-75" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                            <span>{{ __('messages.dashboard') }}</span>
                        </a>

                        <!-- Sales History & Invoices -->
                        <x-nav-item module="sales_history" :route="route('pos.sales.history')" active="pos.sales.history" label="Sales History & Invoices">
                            <svg class="w-4 h-4 opacity-75" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                        </x-nav-item>

                        <!-- Returns & Exchanges -->
                        <x-nav-item module="returns" :route="route('returns.index')" active="returns.*" :label="__('messages.returns')">
                            <svg class="w-4 h-4 opacity-75" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 15v-1a4 4 0 00-4-4H4m0 0l4-4m-4 4l4 4m6 5v1a4 4 0 004 4h8m0 0l-4 4m4-4l-4-4"/></svg>
                        </x-nav-item>

                        <!-- Daily Registers -->
                        <x-nav-item module="daily_registers" :route="route('daily-registers.index')" active="daily-registers.*" :label="__('messages.daily_registers')">
                            <svg class="w-4 h-4 opacity-75" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        </x-nav-item>

                        <!-- Retail Stores -->
                        <x-nav-item module="stores" :route="route('stores.index')" active="stores.*" :label="__('messages.stores')">
                            <svg class="w-4 h-4 opacity-75" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        </x-nav-item>

                        <!-- Stock Transfers -->
                        <x-nav-item module="transfers" :route="route('transfers.index')" active="transfers.*" :label="__('messages.transfers')">
                            <svg class="w-4 h-4 opacity-75" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                        </x-nav-item>
                    </div>
                </div>

                <!-- Section 2: Purchasing & Vendors -->
                <div>
                    <div class="px-3 pb-1 text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">
                        Purchasing & Vendors
                    </div>
                    <div class="space-y-0.5">
                        <x-nav-item module="suppliers" :route="route('suppliers.index')" active="suppliers.*" :label="__('messages.suppliers')">
                            <svg class="w-4 h-4 opacity-75" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        </x-nav-item>

                        <x-nav-item module="purchase_orders" :route="route('purchase-orders.index')" active="purchase-orders.*" :label="__('messages.purchase_orders')">
                            <svg class="w-4 h-4 opacity-75" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        </x-nav-item>

                        <x-nav-item module="supplier_returns" :route="route('supplier-returns.index')" active="supplier-returns.*" :label="__('messages.supplier_returns')">
                            <svg class="w-4 h-4 opacity-75" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg>
                        </x-nav-item>
                    </div>
                </div>

                <!-- Section 3: Catalog & Promos -->
                <div>
                    <div class="px-3 pb-1 text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">
                        Catalog & Promotions
                    </div>
                    <div class="space-y-0.5">
                        <x-nav-item module="categories" :route="route('categories.index')" active="categories.*" :label="__('messages.categories')">
                            <svg class="w-4 h-4 opacity-75" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                        </x-nav-item>

                        <x-nav-item module="products" :route="route('products.index')" active="products.*" :label="__('messages.products')">
                            <svg class="w-4 h-4 opacity-75" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                        </x-nav-item>

                        <x-nav-item module="barcodes" :route="route('barcodes.index')" active="barcodes.*" :label="__('messages.barcodes')">
                            <svg class="w-4 h-4 opacity-75" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                        </x-nav-item>

                        <x-nav-item module="promotions" :route="route('promotions.index')" active="promotions.*" :label="__('messages.promotions')">
                            <svg class="w-4 h-4 opacity-75" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7"/></svg>
                        </x-nav-item>
                    </div>
                </div>

                <!-- Section 4: Inventory & Ledger -->
                <div>
                    <div class="px-3 pb-1 text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">
                        Inventory & Ledger
                    </div>
                    <div class="space-y-0.5">
                        <x-nav-item module="inventory" :route="route('inventory.index')" active="inventory.index" :label="__('messages.inventory')">
                            <svg class="w-4 h-4 opacity-75" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                        </x-nav-item>

                        <x-nav-item module="inventory" :route="route('inventory.ledger')" active="inventory.ledger" :label="__('messages.stock_movements')">
                            <svg class="w-4 h-4 opacity-75" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </x-nav-item>

                        @php
                            $pendingAdjCount = \App\Models\StockAdjustment::where('status', 'pending')->count();
                        @endphp
                        <x-nav-item module="stock_adjustments" :route="route('stock-adjustments.index')" active="stock-adjustments.*" :label="__('messages.stock_adjustments')" :badge="($pendingAdjCount > 0 && auth()->user()->isSuperAdmin()) ? $pendingAdjCount : null" badgeClass="bg-amber-500 text-white animate-pulse">
                            <svg class="w-4 h-4 opacity-75" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/></svg>
                        </x-nav-item>

                        <x-nav-item module="stock_counts" :route="route('stock-counts.index')" active="stock-counts.*" :label="__('messages.stock_counts')">
                            <svg class="w-4 h-4 opacity-75" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </x-nav-item>
                    </div>
                </div>

                <!-- Section 5: Accounting & Finance -->
                <div>
                    <div class="px-3 pb-1 text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">
                        Accounting & Finance
                    </div>
                    <div class="space-y-0.5">
                        <x-nav-item module="financial_accounts" :route="route('financial-accounts.index')" active="financial-accounts.*" label="Financial Accounts">
                            <svg class="w-4 h-4 opacity-75" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                        </x-nav-item>

                        <x-nav-item module="expenses" :route="route('expenses.index')" active="expenses.*" label="Expenses & Approvals">
                            <svg class="w-4 h-4 opacity-75" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        </x-nav-item>

                        <x-nav-item module="reports" :route="route('reports.financial')" active="reports.financial" label="Profit & Loss Reports">
                            <svg class="w-4 h-4 opacity-75" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                        </x-nav-item>

                        <x-nav-item module="reports" :route="route('reports.cash')" active="reports.cash" label="Cash Reconciliation">
                            <svg class="w-4 h-4 opacity-75" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </x-nav-item>

                        <x-nav-item module="reports" :route="route('reports.slow_moving')" active="reports.slow_moving" label="Slow-Moving Stock">
                            <svg class="w-4 h-4 opacity-75" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </x-nav-item>

                        <x-nav-item module="reports" :route="route('reports.store_comparison')" active="reports.store_comparison" label="Store Comparison">
                            <svg class="w-4 h-4 opacity-75" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 13v-1m4 1v-3m4 3V8M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/></svg>
                        </x-nav-item>
                    </div>
                </div>

                <!-- Section 6: HR & Payroll -->
                <div>
                    <div class="px-3 pb-1 text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">
                        Staff & Payroll
                    </div>
                    <div class="space-y-0.5">
                        <x-nav-item module="employees" :route="route('employees.index')" active="employees.*" label="Employees & Staff">
                            <svg class="w-4 h-4 opacity-75" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        </x-nav-item>

                        <x-nav-item module="employees" :route="route('attendance.index')" active="attendance.*" label="Store Attendance">
                            <svg class="w-4 h-4 opacity-75" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                        </x-nav-item>

                        <x-nav-item module="employees" :route="route('payroll.index')" active="payroll.*" label="Payroll & Advances">
                            <svg class="w-4 h-4 opacity-75" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </x-nav-item>
                    </div>
                </div>

                <!-- Section 7: Data Migration & Imports -->
                @if(auth()->user()->isSuperAdmin())
                <div>
                    <div class="px-3 pb-1 text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">
                        Data Migration
                    </div>
                    <div class="space-y-0.5">
                        <a href="{{ route('imports.index') }}" {!! request()->routeIs('imports.*') ? 'data-active-nav="true"' : '' !!} class="flex items-center gap-3 px-3 py-2 rounded-xl transition {{ request()->routeIs('imports.*') ? 'bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 font-bold' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                            <svg class="w-4 h-4 opacity-75" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                            <span>Controlled Imports</span>
                        </a>
                    </div>
                </div>
                @endif

                <!-- Section 8: Customers & Governance -->
                <div>
                    <div class="px-3 pb-1 text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">
                        Customers & Administration
                    </div>
                    <div class="space-y-0.5">
                        <x-nav-item module="customers" :route="route('customers.index')" active="customers.*" :label="__('messages.customers')">
                            <svg class="w-4 h-4 opacity-75" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                        </x-nav-item>

                        @if(auth()->user()->isSuperAdmin())
                            <!-- Role Permissions Matrix (Shop Owner / Super Admin ONLY) -->
                            <a href="{{ route('roles.permissions.index') }}" {!! request()->routeIs('roles.permissions.*') ? 'data-active-nav="true"' : '' !!} class="flex items-center justify-between px-3 py-2 rounded-xl transition {{ request()->routeIs('roles.permissions.*') ? 'bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 font-bold' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                                <div class="flex items-center gap-3">
                                    <svg class="w-4 h-4 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                    <span>Role Permissions</span>
                                </div>
                                <span class="text-[9px] px-1.5 py-0.5 rounded bg-indigo-600 text-white font-black uppercase">OWNER</span>
                            </a>

                            <!-- Tax / VAT Settings (Shop Owner / Super Admin ONLY) -->
                            <a href="{{ route('admin.tax.index') }}" {!! request()->routeIs('admin.tax.*') ? 'data-active-nav="true"' : '' !!} class="flex items-center justify-between px-3 py-2 rounded-xl transition {{ request()->routeIs('admin.tax.*') ? 'bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 font-bold' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                                <div class="flex items-center gap-3">
                                    <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/></svg>
                                    <span>Tax / VAT Settings</span>
                                </div>
                                <span class="text-[9px] px-1.5 py-0.5 rounded {{ \App\Models\AppSetting::isVatEnabled() ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/60 dark:text-emerald-300' : 'bg-slate-200 text-slate-700 dark:bg-slate-700 dark:text-slate-300' }} font-mono font-bold">
                                    {{ \App\Models\AppSetting::isVatEnabled() ? \App\Models\AppSetting::getVatRate() . '%' : 'OFF' }}
                                </span>
                            </a>

                            <!-- Site & Logo Branding (Shop Owner / Super Admin ONLY) -->
                            <a href="{{ route('admin.branding.index') }}" {!! request()->routeIs('admin.branding.*') ? 'data-active-nav="true"' : '' !!} class="flex items-center justify-between px-3 py-2 rounded-xl transition {{ request()->routeIs('admin.branding.*') ? 'bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 font-bold' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                                <div class="flex items-center gap-3">
                                    <svg class="w-4 h-4 text-violet-600 dark:text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/></svg>
                                    <span>Site & Logo Branding</span>
                                </div>
                                <span class="text-[9px] px-1.5 py-0.5 rounded bg-violet-600 text-white font-black uppercase">OWNER</span>
                            </a>

                            <x-nav-item module="staff" :route="route('staff.index')" active="staff.*" :label="__('messages.staff')">
                                <svg class="w-4 h-4 opacity-75" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"/></svg>
                            </x-nav-item>

                            <x-nav-item module="audit" :route="route('audit.index')" active="audit.*" :label="__('messages.audit_logs')">
                                <svg class="w-4 h-4 opacity-75" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            </x-nav-item>
                        @endif
                    </div>
                </div>

            </nav>

            <script>
                // Instant synchronous restoration of sidebar scroll before paint
                (function() {
                    try {
                        var nav = document.getElementById('sidebar-nav');
                        if (nav) {
                            var saved = sessionStorage.getItem('pos_sidebar_scroll');
                            if (saved !== null) {
                                nav.scrollTop = parseInt(saved, 10);
                            }
                        }
                    } catch (e) {}
                })();
            </script>

            <!-- Bottom Staff User Card -->
            <div class="p-3.5 border-t border-slate-200/90 dark:border-slate-800 flex items-center justify-between bg-slate-50/50 dark:bg-slate-900/50 flex-shrink-0">
                <div class="flex items-center gap-2.5 truncate">
                    <div class="w-8 h-8 rounded-lg bg-indigo-600 text-white flex items-center justify-center font-bold text-xs flex-shrink-0">
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    </div>
                    <div class="truncate">
                        <div class="text-xs font-bold text-slate-900 dark:text-white truncate">{{ auth()->user()->name }}</div>
                        <div class="text-[10px] text-indigo-600 dark:text-indigo-400 font-semibold tracking-wide flex items-center gap-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                            <span>{{ auth()->user()->role ? (is_object(auth()->user()->role) && method_exists(auth()->user()->role, 'label') ? auth()->user()->role->label() : (string) auth()->user()->role) : 'Staff User' }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </aside>

        <!-- MAIN CONTENT AREA (OFFSET BY FIXED SIDEBAR ON DESKTOP) -->
        <div class="app-main-wrapper flex-1 flex flex-col min-w-0 min-h-screen transition-all duration-300">
            
            <!-- TOP NAVBAR (STICKY AT TOP) -->
            <header class="no-print sticky top-0 z-20 h-16 bg-white/95 dark:bg-slate-900/95 backdrop-blur-md border-b border-slate-200/90 dark:border-slate-800 px-4 sm:px-6 flex items-center justify-between shadow-xs">
                <div class="flex items-center gap-3">
                    <button @click="sidebarOpen = true" class="lg:hidden p-2 rounded-lg text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-200 focus:outline-none">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </button>
                    <h1 class="text-base font-bold text-slate-900 dark:text-white hidden sm:block">
                        @yield('header', __('messages.dashboard'))
                    </h1>
                </div>

                <!-- Right Header Controls -->
                <div class="flex items-center gap-2 sm:gap-3">
                    
                    <!-- Live Status Badge -->
                    <div class="hidden md:flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/60 text-emerald-700 dark:text-emerald-300 text-[11px] font-semibold">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-ping"></span>
                        <span>BDT (৳) Online Hub</span>
                    </div>

                    <!-- Language Switcher Form -->
                    <form action="{{ route('locale.set') }}" method="POST" class="inline">
                        @csrf
                        <select name="locale" onchange="this.form.submit()" 
                                class="text-xs font-semibold rounded-lg bg-slate-100 dark:bg-slate-800 border-slate-200 dark:border-slate-700 py-1.5 px-2.5 text-slate-700 dark:text-slate-200 cursor-pointer focus:ring-1 focus:ring-indigo-500">
                            <option value="en" {{ app()->getLocale() === 'en' ? 'selected' : '' }}>English</option>
                            <option value="bn" {{ app()->getLocale() === 'bn' ? 'selected' : '' }}>বাংলা (BN)</option>
                        </select>
                    </form>

                    <!-- Dark Mode Toggle Button -->
                    <button type="button" 
                            onclick="togglePosTheme()" 
                            class="p-2 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:text-indigo-600 dark:hover:text-indigo-400 border border-slate-200 dark:border-slate-700 transition cursor-pointer"
                            title="Toggle Theme">
                        <svg x-show="!darkMode" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                        <svg x-cloak x-show="darkMode" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    </button>

                    <!-- Logout Button -->
                    <form action="{{ route('logout') }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" 
                                class="text-xs font-semibold py-1.5 px-3 rounded-lg bg-slate-100 hover:bg-rose-50 text-slate-700 hover:text-rose-600 dark:bg-slate-800 dark:hover:bg-rose-950/40 dark:text-slate-300 dark:hover:text-rose-400 border border-slate-200 dark:border-slate-700 transition flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                            <span class="hidden sm:inline">{{ __('messages.logout') }}</span>
                        </button>
                    </form>
                </div>
            </header>

            <!-- FLASH ALERTS -->
            <div class="px-4 sm:px-6 pt-4">
                @if(session('success'))
                    <div class="mb-4 p-3.5 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/60 text-emerald-800 dark:text-emerald-200 text-xs flex items-center justify-between shadow-sm">
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-emerald-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                            <span class="font-semibold">{{ session('success') }}</span>
                        </div>
                    </div>
                @endif

                @if(session('error') || $errors->any())
                    <div class="mb-4 p-3.5 rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-900/60 text-rose-800 dark:text-rose-200 text-xs shadow-sm">
                        <div class="flex items-center gap-2 font-bold mb-1">
                            <svg class="w-4 h-4 text-rose-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                            <span>{{ session('error') ?? 'Please check the form for errors:' }}</span>
                        </div>
                        @if($errors->any())
                            <ul class="list-disc pl-6 space-y-0.5 text-[11px]">
                                @foreach($errors->all() as $err)
                                    <li>{{ $err }}</li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                @endif
            </div>

            <!-- MAIN CONTENT BODY -->
            <main class="flex-1 px-4 sm:px-6 pb-12 pt-2">
                @yield('content')
            </main>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var nav = document.getElementById('sidebar-nav');
            if (!nav) return;

            // 1. Restore scroll position from sessionStorage
            var saved = sessionStorage.getItem('pos_sidebar_scroll');
            if (saved !== null) {
                nav.scrollTop = parseInt(saved, 10);
            }

            // 2. Ensure active nav item is visible in viewport
            var active = nav.querySelector('[data-active-nav="true"]');
            if (active) {
                var navRect = nav.getBoundingClientRect();
                var activeRect = active.getBoundingClientRect();
                // If active item is above top or below bottom of visible nav area, bring it into view
                if (activeRect.top < navRect.top || activeRect.bottom > navRect.bottom) {
                    active.scrollIntoView({ block: 'nearest', behavior: 'instant' });
                    sessionStorage.setItem('pos_sidebar_scroll', nav.scrollTop);
                }
            }

            // 3. Save scroll position on user scroll (debounced)
            var timer = null;
            nav.addEventListener('scroll', function() {
                clearTimeout(timer);
                timer = setTimeout(function() {
                    sessionStorage.setItem('pos_sidebar_scroll', nav.scrollTop);
                }, 50);
            }, { passive: true });

            // 4. Save scroll position before navigating via link
            nav.addEventListener('click', function(e) {
                var link = e.target.closest('a');
                if (link) {
                    sessionStorage.setItem('pos_sidebar_scroll', nav.scrollTop);
                }
            });
        });
    </script>

    @stack('scripts')
</body>
</html>
