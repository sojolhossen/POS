@extends('layouts.app')

@section('title', 'Role Permissions & Access Control — Super Admin')
@section('header', 'Role Permissions & Sidebar Access Controls')

@section('content')
<div class="space-y-6" x-data="{
    search: '',
    toggleRoleAll(roleName, check) {
        document.querySelectorAll('input[data-role=' + roleName + ']').forEach(cb => {
            cb.checked = check;
        });
    }
}">

    <!-- OWNER BANNER (LIGHT & CLEAN DESIGN) -->
    <div class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 shadow-sm relative overflow-hidden">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="space-y-1.5">
                <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-indigo-50 text-indigo-700 border border-indigo-200 dark:bg-indigo-950/60 dark:text-indigo-300 dark:border-indigo-800 text-[11px] font-bold">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span>Shop Owner Authority (Super Admin)</span>
                </div>
                <h2 class="text-xl font-extrabold text-slate-900 dark:text-white tracking-tight">Role-Based Permission Matrix</h2>
                <p class="text-xs text-slate-600 dark:text-slate-400 max-w-2xl leading-relaxed">
                    Set granular module access permissions for every role. When permission is revoked for a role, the corresponding menu link will appear with a <strong>Lock icon (🔒)</strong> in their side navigation bar and URL access will be strictly blocked.
                </p>
            </div>
            <div class="flex items-center gap-3">
                <form action="{{ route('roles.permissions.reset') }}" method="POST" onsubmit="return confirm('Are you sure you want to reset all role permissions to factory defaults?');">
                    @csrf
                    <button type="submit" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 font-bold text-xs transition flex items-center gap-2">
                        <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        <span>Reset Defaults</span>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- MAIN FORM -->
    <form action="{{ route('roles.permissions.update') }}" method="POST">
        @csrf

        <!-- ROLE HEADER CARDS -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 mb-6">
            @foreach($allRoles as $role)
                @php
                    $isOwner = $role->value === 'super_admin';
                    $roleCardBg = match($role->value) {
                        'super_admin' => 'bg-indigo-50/70 border-indigo-200 dark:bg-indigo-950/40 dark:border-indigo-800',
                        'store_manager' => 'bg-emerald-50/70 border-emerald-200 dark:bg-emerald-950/40 dark:border-emerald-800',
                        'cashier' => 'bg-cyan-50/70 border-cyan-200 dark:bg-cyan-950/40 dark:border-cyan-800',
                        'warehouse_staff' => 'bg-amber-50/70 border-amber-200 dark:bg-amber-950/40 dark:border-amber-800',
                        'accountant' => 'bg-violet-50/70 border-violet-200 dark:bg-violet-950/40 dark:border-violet-800',
                        default => 'bg-slate-50 border-slate-200',
                    };
                @endphp
                <div class="p-3.5 rounded-2xl border {{ $roleCardBg }} flex flex-col justify-between">
                    <div>
                        <div class="text-[10px] uppercase font-bold tracking-wider text-slate-400 mb-0.5">Role</div>
                        <div class="font-extrabold text-xs text-slate-900 dark:text-white truncate">
                            {{ $role->label() }}
                        </div>
                        <div class="text-[10px] text-slate-500 font-mono mt-0.5">
                            {{ $role->value }}
                        </div>
                    </div>

                    <div class="mt-3 pt-2 border-t border-slate-200/60 dark:border-slate-800/60 flex items-center justify-between text-[11px]">
                        @if($isOwner)
                            <span class="px-2 py-0.5 rounded-full bg-indigo-600 text-white font-black text-[9px] uppercase tracking-wider">
                                Permanent Owner Bypass
                            </span>
                        @else
                            <button type="button" @click="toggleRoleAll('{{ $role->value }}', true)" class="text-indigo-600 dark:text-indigo-400 hover:underline font-bold text-[10px]">
                                All ON
                            </button>
                            <span class="text-slate-300">|</span>
                            <button type="button" @click="toggleRoleAll('{{ $role->value }}', false)" class="text-rose-600 dark:text-rose-400 hover:underline font-bold text-[10px]">
                                All OFF
                            </button>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        <!-- SEARCH BAR -->
        <div class="mb-4">
            <div class="relative">
                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </span>
                <input type="text" x-model="search" placeholder="Filter modules by name or description (e.g. pos, return, stock, expense)..." 
                       class="w-full pl-10 pr-4 py-2.5 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-200 placeholder-slate-400 focus:ring-2 focus:ring-indigo-500 focus:outline-none shadow-sm">
            </div>
        </div>

        <!-- CATEGORIES AND MODULE MATRIX -->
        <div class="space-y-6">
            @foreach($categories as $categoryName => $catModules)
                <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/90 dark:border-slate-800 shadow-sm overflow-hidden"
                     x-show="!search || {{ json_encode(array_values(array_map(fn($m) => strtolower($m['name'] . ' ' . $m['description']), $catModules))) }}.some(text => text.includes(search.toLowerCase()))">
                    
                    <!-- Category Header -->
                    <div class="px-5 py-3.5 bg-slate-50/80 dark:bg-slate-800/80 border-b border-slate-200/90 dark:border-slate-800 flex items-center justify-between">
                        <h3 class="font-extrabold text-xs uppercase tracking-wider text-slate-700 dark:text-slate-300 flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                            <span>{{ $categoryName }}</span>
                        </h3>
                        <span class="text-[11px] font-semibold text-slate-400">
                            {{ count($catModules) }} Modules
                        </span>
                    </div>

                    <!-- Category Table -->
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-slate-100/60 dark:bg-slate-800/40 text-[10px] font-extrabold uppercase tracking-wider text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
                                <tr>
                                    <th class="py-3 px-4 w-2/5">Module & Capabilities</th>
                                    @foreach($allRoles as $role)
                                        <th class="py-3 px-3 text-center w-[12%]">
                                            {{ $role->label() }}
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                @foreach($catModules as $modKey => $info)
                                    <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/30 transition"
                                        x-show="!search || '{{ strtolower($info['name'] . ' ' . $info['description'] . ' ' . $modKey) }}'.includes(search.toLowerCase())">
                                        
                                        <!-- Module Info -->
                                        <td class="py-3.5 px-4">
                                            <div class="font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                                <span>{{ $info['name'] }}</span>
                                                <span class="font-mono text-[10px] px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-500 font-normal">
                                                    {{ $modKey }}
                                                </span>
                                            </div>
                                            <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                                                {{ $info['description'] }}
                                            </div>
                                        </td>

                                        <!-- Roles Checkboxes -->
                                        @foreach($allRoles as $role)
                                            @php
                                                $roleVal = $role->value;
                                                $isAllowed = $matrix[$roleVal][$modKey] ?? false;
                                                $isOwner = $roleVal === 'super_admin';
                                            @endphp
                                            <td class="py-3.5 px-3 text-center align-middle">
                                                @if($isOwner)
                                                    <div class="inline-flex items-center justify-center w-6 h-6 rounded-lg bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300" title="Shop Owner: Full Unrestricted Access">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                                    </div>
                                                @else
                                                    <label class="inline-flex items-center justify-center cursor-pointer p-1">
                                                        <input type="checkbox" 
                                                               name="permissions[{{ $roleVal }}][{{ $modKey }}]" 
                                                               value="1" 
                                                               data-role="{{ $roleVal }}"
                                                               {{ $isAllowed ? 'checked' : '' }}
                                                               class="w-4 h-4 rounded text-indigo-600 focus:ring-indigo-500 border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 cursor-pointer transition">
                                                    </label>
                                                @endif
                                            </td>
                                        @endforeach

                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- STICKY ACTION FOOTER -->
        <div class="sticky bottom-4 z-20 mt-6 p-4 rounded-2xl bg-white/95 dark:bg-slate-900/95 backdrop-blur-md border border-slate-200 dark:border-slate-800 shadow-xl flex items-center justify-between">
            <div class="text-xs text-slate-500 font-medium">
                Changes take effect immediately for staff sessions upon saving.
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('dashboard') }}" class="px-4 py-2.5 text-xs font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-xl transition">
                    Cancel
                </a>
                <button type="submit" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-extrabold text-xs rounded-xl transition shadow-lg shadow-indigo-600/30 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Save Permission Configuration
                </button>
            </div>
        </div>

    </form>

</div>
@endsection
