<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      x-data="{ darkMode: localStorage.getItem('pos_theme') === 'dark' }"
      :class="{ 'dark': darkMode }">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('messages.login') }} — FashionPOS BD</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Noto+Sans+Bengali:wght@400;500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-100 dark:bg-slate-950 text-slate-900 dark:text-slate-100 min-h-screen flex items-center justify-center p-4">

    <div class="max-w-md w-full">
        <!-- Logo and Brand -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-gradient-to-tr from-indigo-600 via-indigo-500 to-violet-500 text-white font-extrabold text-2xl shadow-xl shadow-indigo-500/30 mb-3">
                FP
            </div>
            <h1 class="text-2xl font-black tracking-tight text-slate-900 dark:text-white">FashionPOS Bangladesh</h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Multi-Store Clothing Retail & Warehouse System</p>
        </div>

        <!-- Login Card -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl p-8 border border-slate-200/80 dark:border-slate-800 shadow-xl">
            <h2 class="text-lg font-bold text-slate-800 dark:text-slate-100 mb-6">{{ __('messages.login') }}</h2>

            @if($errors->any())
                <div class="mb-5 p-4 rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 text-rose-700 dark:text-rose-300 text-xs">
                    <ul class="list-disc pl-4 space-y-1">
                        @foreach($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('login.store') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">Email Address</label>
                    <input type="email" name="email" value="{{ old('email', 'admin@clothingpos.com') }}" required autofocus
                           class="w-full text-sm rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/70 py-2.5 px-3.5 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">Password</label>
                    </div>
                    <input type="password" name="password" value="Admin@2026!Pos" required
                           class="w-full text-sm rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/70 py-2.5 px-3.5 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                </div>

                <div class="flex items-center justify-between text-xs text-slate-500 dark:text-slate-400 py-1">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="remember" class="rounded text-indigo-600 focus:ring-indigo-500">
                        <span>Remember this device</span>
                    </label>
                </div>

                <button type="submit" 
                        class="w-full py-3 px-4 rounded-xl font-bold text-sm text-white bg-indigo-600 hover:bg-indigo-700 transition shadow-lg shadow-indigo-600/30">
                    {{ __('messages.login') }}
                </button>
            </form>

            <!-- Test Credentials Switcher Box -->
            <div class="mt-8 pt-6 border-t border-slate-100 dark:border-slate-800 text-xs">
                <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-3 text-center">
                    Verified Role Test Accounts
                </div>
                <div class="grid grid-cols-1 gap-2 text-[11px]">
                    <button type="button" onclick="fillCreds('admin@clothingpos.com', 'Admin@2026!Pos')"
                            class="p-2 rounded-lg bg-indigo-50 dark:bg-indigo-950/40 border border-indigo-200 dark:border-indigo-800/50 text-indigo-700 dark:text-indigo-300 text-left hover:bg-indigo-100 transition flex items-center justify-between">
                        <span class="font-bold">Super Admin: admin@clothingpos.com</span>
                        <span class="text-[10px] opacity-75">Click to Fill</span>
                    </button>
                    <button type="button" onclick="fillCreds('manager.dhanmondi@clothingpos.com', 'Manager@2026!')"
                            class="p-2 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-left hover:bg-slate-200 dark:hover:bg-slate-700 transition flex items-center justify-between">
                        <span class="font-bold">Store Manager: manager.dhanmondi@clothingpos.com</span>
                        <span class="text-[10px] opacity-75">Click to Fill</span>
                    </button>
                    <button type="button" onclick="fillCreds('cashier.dhanmondi@clothingpos.com', 'Cashier@2026!')"
                            class="p-2 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-left hover:bg-slate-200 dark:hover:bg-slate-700 transition flex items-center justify-between">
                        <span class="font-bold">Cashier: cashier.dhanmondi@clothingpos.com</span>
                        <span class="text-[10px] opacity-75">Click to Fill</span>
                    </button>
                    <button type="button" onclick="fillCreds('rafiq@clothingpos.com', 'Warehouse@2026!')"
                            class="p-2 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-left hover:bg-slate-200 dark:hover:bg-slate-700 transition flex items-center justify-between">
                        <span class="font-bold">Warehouse Staff: rafiq@clothingpos.com</span>
                        <span class="text-[10px] opacity-75">Click to Fill</span>
                    </button>
                    <button type="button" onclick="fillCreds('accountant@clothingpos.com', 'Accounts@2026!')"
                            class="p-2 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-left hover:bg-slate-200 dark:hover:bg-slate-700 transition flex items-center justify-between">
                        <span class="font-bold">Accountant: accountant@clothingpos.com</span>
                        <span class="text-[10px] opacity-75">Click to Fill</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function fillCreds(email, pass) {
            document.querySelector('input[name="email"]').value = email;
            document.querySelector('input[name="password"]').value = pass;
        }
    </script>
</body>
</html>
