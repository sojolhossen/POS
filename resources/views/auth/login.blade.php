<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      x-data="{ 
          darkMode: localStorage.getItem('pos_theme') === 'dark',
          showPassword: false,
          activeRoleTab: 'super_admin'
      }"
      :class="{ 'dark': darkMode }">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('messages.login') }} — FashionPOS BD</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Noto+Sans+Bengali:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-50 dark:bg-[#0b0f19] text-slate-900 dark:text-slate-100 min-h-screen flex flex-col font-sans antialiased transition-colors duration-200">

    <!-- TOP UTILITY BAR (Language & Theme switchers) -->
    <header class="w-full px-6 py-4 flex items-center justify-between border-b border-slate-200/80 dark:border-slate-800/80 bg-white/70 dark:bg-slate-900/70 backdrop-blur-md sticky top-0 z-20">
        <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-lg bg-indigo-600 flex items-center justify-center text-white font-black text-sm tracking-tighter shadow-md shadow-indigo-600/20">
                FP
            </div>
            <div>
                <span class="font-extrabold text-sm tracking-tight text-slate-900 dark:text-white">FashionPOS</span>
                <span class="text-[11px] font-medium text-slate-500 dark:text-slate-400 ml-1 hidden sm:inline">• Enterprise Retail ERP</span>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <!-- Language Switcher Form -->
            <form action="{{ route('locale.set') }}" method="POST" class="inline">
                @csrf
                <div class="relative">
                    <select name="locale" onchange="this.form.submit()" 
                            class="text-xs font-semibold rounded-lg bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 py-1.5 pl-3 pr-7 text-slate-700 dark:text-slate-200 cursor-pointer focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 appearance-none">
                        <option value="en" {{ app()->getLocale() === 'en' ? 'selected' : '' }}>English (EN)</option>
                        <option value="bn" {{ app()->getLocale() === 'bn' ? 'selected' : '' }}>বাংলা (BN)</option>
                    </select>
                    <svg class="w-3.5 h-3.5 text-slate-400 absolute right-2.5 top-2.5 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </div>
            </form>

            <!-- Dark / Light Mode Toggle -->
            <button type="button" @click="darkMode = !darkMode; localStorage.setItem('pos_theme', darkMode ? 'dark' : 'light')" 
                    class="p-2 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:text-indigo-600 dark:hover:text-indigo-400 border border-slate-200 dark:border-slate-700 transition"
                    title="Toggle Theme">
                <svg x-show="!darkMode" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                <svg x-cloak x-show="darkMode" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
            </button>
        </div>
    </header>

    <!-- MAIN LOGIN CONTAINER -->
    <main class="flex-1 flex items-center justify-center p-4 sm:p-6 lg:p-10">
        <div class="w-full max-w-5xl bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/90 dark:border-slate-800 shadow-2xl overflow-hidden grid grid-cols-1 lg:grid-cols-12 min-h-[600px]">
            
            <!-- LEFT PANEL: Brand, Network Status, Enterprise Pillars -->
            <div class="lg:col-span-5 bg-gradient-to-br from-slate-900 via-indigo-950 to-slate-900 text-white p-8 sm:p-10 flex flex-col justify-between relative overflow-hidden">
                <!-- Background decorative mesh -->
                <div class="absolute -right-16 -top-16 w-64 h-64 bg-indigo-500/20 rounded-full blur-3xl pointer-events-none"></div>
                <div class="absolute -left-16 -bottom-16 w-64 h-64 bg-violet-600/20 rounded-full blur-3xl pointer-events-none"></div>

                <div class="relative z-10 space-y-6">
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 backdrop-blur-md border border-white/15 text-[11px] font-semibold text-indigo-200">
                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                        <span>Phase 1 Production Architecture Active</span>
                    </div>

                    <div>
                        <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight leading-tight">
                            Enterprise Clothing Retail & Warehouse System
                        </h1>
                        <p class="text-xs sm:text-sm text-slate-300 mt-2 leading-relaxed">
                            Built for high-volume fashion chains across Bangladesh with multi-store inventory synchronization and audit logging.
                        </p>
                    </div>

                    <!-- Enterprise Pillars List -->
                    <div class="space-y-3.5 pt-2">
                        <div class="flex items-start gap-3">
                            <div class="w-7 h-7 rounded-lg bg-indigo-500/20 border border-indigo-400/30 flex items-center justify-center text-indigo-300 flex-shrink-0 mt-0.5">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                            </div>
                            <div>
                                <div class="text-xs font-bold text-white">Centralized Multi-Store Control</div>
                                <div class="text-[11px] text-slate-300">Central Warehouse (WH-001) + 6 Dedicated Retail Outlets</div>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <div class="w-7 h-7 rounded-lg bg-indigo-500/20 border border-indigo-400/30 flex items-center justify-center text-indigo-300 flex-shrink-0 mt-0.5">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                            </div>
                            <div>
                                <div class="text-xs font-bold text-white">Code 128 Variant Barcodes</div>
                                <div class="text-[11px] text-slate-300">Permanent variant identity, thermal printing & mobile scanning</div>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <div class="w-7 h-7 rounded-lg bg-indigo-500/20 border border-indigo-400/30 flex items-center justify-center text-indigo-300 flex-shrink-0 mt-0.5">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                            </div>
                            <div>
                                <div class="text-xs font-bold text-white">Immutable Ledger & Audit Trail</div>
                                <div class="text-[11px] text-slate-300">Row-locked atomic balance changes & single active session security</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="relative z-10 pt-8 border-t border-white/10 mt-6 flex items-center justify-between text-[11px] text-slate-400">
                    <span>Currency: <strong class="text-white">BDT (৳)</strong></span>
                    <span>Date: <strong class="text-white">DD-MM-YYYY</strong></span>
                    <span>Server: <strong class="text-emerald-400">Online</strong></span>
                </div>
            </div>

            <!-- RIGHT PANEL: Authentication Form & Quick Role Access -->
            <div class="lg:col-span-7 p-8 sm:p-10 flex flex-col justify-between">
                
                <div>
                    <!-- Form Title & Badge -->
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h2 class="text-xl font-bold tracking-tight text-slate-900 dark:text-white">
                                Staff Authentication Portal
                            </h2>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                                Enter your enterprise credentials to access your designated workspace.
                            </p>
                        </div>
                        <div class="hidden sm:flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-slate-100 dark:bg-slate-800 text-[11px] font-mono font-medium text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-700">
                            <svg class="w-3.5 h-3.5 text-emerald-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 1a4.5 4.5 0 00-4.5 4.5V9H5a2 2 0 00-2 2v6a2 2 0 002 2h10a2 2 0 002-2v-6a2 2 0 00-2-2h-.5V5.5A4.5 4.5 0 0010 1zm3 8V5.5a3 3 0 10-6 0V9h6z" clip-rule="evenodd"/></svg>
                            <span>TLS 256-Bit</span>
                        </div>
                    </div>

                    <!-- Error Messages -->
                    @if($errors->any())
                        <div class="mb-5 p-3.5 rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-900/60 text-rose-700 dark:text-rose-300 text-xs">
                            <div class="font-bold flex items-center gap-2 mb-1">
                                <svg class="w-4 h-4 text-rose-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                                <span>Authentication Failed</span>
                            </div>
                            <ul class="list-disc pl-5 space-y-0.5">
                                @foreach($errors->all() as $err)
                                    <li>{{ $err }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <!-- Login Form -->
                    <form action="{{ route('login.store') }}" method="POST" class="space-y-4">
                        @csrf
                        
                        <!-- Email Input -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                                Email Address
                            </label>
                            <div class="relative">
                                <input type="email" name="email" id="input_email" value="{{ old('email', 'admin@clothingpos.com') }}" required autofocus
                                       class="w-full text-sm rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/80 py-2.5 pl-10 pr-3.5 text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 focus:outline-none transition">
                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"/></svg>
                                </div>
                            </div>
                        </div>

                        <!-- Password Input -->
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                                    Account Password
                                </label>
                                <span class="text-[11px] text-slate-400">Min. 8 characters</span>
                            </div>
                            <div class="relative">
                                <input :type="showPassword ? 'text' : 'password'" name="password" id="input_password" value="Admin@2026!Pos" required
                                       class="w-full text-sm rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/80 py-2.5 pl-10 pr-10 text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 focus:outline-none transition">
                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                </div>
                                <button type="button" @click="showPassword = !showPassword" class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                                    <svg x-show="!showPassword" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    <svg x-cloak x-show="showPassword" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/></svg>
                                </button>
                            </div>
                        </div>

                        <!-- Single Active Session Policy Notice & Remember -->
                        <div class="flex items-center justify-between text-xs py-1">
                            <label class="flex items-center gap-2 cursor-pointer text-slate-600 dark:text-slate-400">
                                <input type="checkbox" name="remember" class="rounded border-slate-300 dark:border-slate-700 text-indigo-600 focus:ring-indigo-500">
                                <span>Remember this workstation</span>
                            </label>
                            <span class="text-[11px] text-slate-400">Single active session enforced</span>
                        </div>

                        <!-- Submit Button -->
                        <button type="submit" 
                                class="w-full py-3 px-4 rounded-xl font-bold text-sm text-white bg-indigo-600 hover:bg-indigo-700 active:scale-[0.99] transition shadow-lg shadow-indigo-600/25 flex items-center justify-center gap-2">
                            <span>Sign In to System</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                        </button>
                    </form>
                </div>

                <!-- QUICK TEST ROLE WORKSTATION (Accordion / Fast Switcher) -->
                <div class="mt-8 pt-5 border-t border-slate-200/80 dark:border-slate-800" x-data="{ expanded: true }">
                    <div class="flex items-center justify-between mb-3 cursor-pointer" @click="expanded = !expanded">
                        <div class="flex items-center gap-2">
                            <span class="w-1.5 h-1.5 rounded-full bg-indigo-600 dark:bg-indigo-400"></span>
                            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                Fast Switcher: 5 Verified Enterprise Roles
                            </span>
                        </div>
                        <span class="text-[10px] text-indigo-600 dark:text-indigo-400 font-semibold" x-text="expanded ? 'Hide accounts' : 'Show accounts'"></span>
                    </div>

                    <div x-show="expanded" x-transition class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-[11px]">
                        
                        <!-- Super Admin -->
                        <button type="button" onclick="fillAccount('admin@clothingpos.com', 'Admin@2026!Pos')"
                                class="p-2.5 rounded-xl border border-indigo-200 dark:border-indigo-900/60 bg-indigo-50/70 dark:bg-indigo-950/30 text-left hover:border-indigo-400 transition flex items-center justify-between group">
                            <div>
                                <div class="font-bold text-indigo-900 dark:text-indigo-300 flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full bg-indigo-600"></span>
                                    <span>Super Admin</span>
                                </div>
                                <div class="text-[10px] text-slate-500 dark:text-slate-400 font-mono mt-0.5">admin@clothingpos.com</div>
                            </div>
                            <span class="text-[10px] font-semibold text-indigo-600 dark:text-indigo-400 opacity-80 group-hover:opacity-100">Fill</span>
                        </button>

                        <!-- Store Manager -->
                        <button type="button" onclick="fillAccount('manager.dhanmondi@clothingpos.com', 'Manager@2026!')"
                                class="p-2.5 rounded-xl border border-slate-200 dark:border-slate-700/80 bg-slate-50 dark:bg-slate-800/60 text-left hover:border-slate-400 transition flex items-center justify-between group">
                            <div>
                                <div class="font-bold text-slate-800 dark:text-slate-200 flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                                    <span>Store Manager</span>
                                </div>
                                <div class="text-[10px] text-slate-500 dark:text-slate-400 font-mono mt-0.5">manager.dhanmondi@...</div>
                            </div>
                            <span class="text-[10px] font-semibold text-slate-600 dark:text-slate-300 opacity-80 group-hover:opacity-100">Fill</span>
                        </button>

                        <!-- Cashier -->
                        <button type="button" onclick="fillAccount('cashier.dhanmondi@clothingpos.com', 'Cashier@2026!')"
                                class="p-2.5 rounded-xl border border-slate-200 dark:border-slate-700/80 bg-slate-50 dark:bg-slate-800/60 text-left hover:border-slate-400 transition flex items-center justify-between group">
                            <div>
                                <div class="font-bold text-slate-800 dark:text-slate-200 flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                    <span>Cashier</span>
                                </div>
                                <div class="text-[10px] text-slate-500 dark:text-slate-400 font-mono mt-0.5">cashier.dhanmondi@...</div>
                            </div>
                            <span class="text-[10px] font-semibold text-slate-600 dark:text-slate-300 opacity-80 group-hover:opacity-100">Fill</span>
                        </button>

                        <!-- Warehouse Staff -->
                        <button type="button" onclick="fillAccount('rafiq@clothingpos.com', 'Warehouse@2026!')"
                                class="p-2.5 rounded-xl border border-slate-200 dark:border-slate-700/80 bg-slate-50 dark:bg-slate-800/60 text-left hover:border-slate-400 transition flex items-center justify-between group">
                            <div>
                                <div class="font-bold text-slate-800 dark:text-slate-200 flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full bg-purple-500"></span>
                                    <span>Warehouse Staff</span>
                                </div>
                                <div class="text-[10px] text-slate-500 dark:text-slate-400 font-mono mt-0.5">rafiq@clothingpos.com</div>
                            </div>
                            <span class="text-[10px] font-semibold text-slate-600 dark:text-slate-300 opacity-80 group-hover:opacity-100">Fill</span>
                        </button>

                        <!-- Accountant -->
                        <button type="button" onclick="fillAccount('accountant@clothingpos.com', 'Accounts@2026!')"
                                class="p-2.5 rounded-xl border border-slate-200 dark:border-slate-700/80 bg-slate-50 dark:bg-slate-800/60 text-left hover:border-slate-400 transition flex items-center justify-between group sm:col-span-2">
                            <div>
                                <div class="font-bold text-slate-800 dark:text-slate-200 flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                                    <span>Accountant</span>
                                </div>
                                <div class="text-[10px] text-slate-500 dark:text-slate-400 font-mono mt-0.5">accountant@clothingpos.com</div>
                            </div>
                            <span class="text-[10px] font-semibold text-slate-600 dark:text-slate-300 opacity-80 group-hover:opacity-100">Fill</span>
                        </button>
                    </div>
                </div>

            </div>

        </div>
    </main>

    <script>
        function fillAccount(email, password) {
            document.getElementById('input_email').value = email;
            document.getElementById('input_password').value = password;
        }
    </script>
</body>
</html>
