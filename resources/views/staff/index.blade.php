@extends('layouts.app')

@section('title', __('messages.staff'))
@section('header', __('messages.staff'))

@section('content')
<div class="space-y-6">

    <!-- Header Actions -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold text-slate-900 dark:text-white">{{ __('messages.staff') }}</h2>
            <p class="text-xs text-slate-500 mt-0.5">Centralized Staff Authentication & Role Permissions</p>
        </div>

        <a href="{{ route('staff.create') }}" 
           class="inline-flex items-center gap-2 py-2.5 px-4 rounded-xl font-bold text-xs text-white bg-indigo-600 hover:bg-indigo-700 transition shadow-lg shadow-indigo-600/30">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            <span>+ Create Staff Account</span>
        </a>
    </div>

    <!-- Staff List Table -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm overflow-hidden">
        
        <div class="p-4 border-b border-slate-100 dark:border-slate-800 flex flex-wrap items-center justify-between gap-3">
            <form method="GET" action="{{ route('staff.index') }}" class="flex items-center gap-2 max-w-sm w-full">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search name, email, phone..."
                       class="w-full text-xs rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 py-2 px-3 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                <button type="submit" class="py-2 px-3 rounded-xl bg-slate-100 dark:bg-slate-800 text-xs font-semibold hover:bg-slate-200">
                    Search
                </button>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-slate-200 dark:border-slate-800 text-slate-400 uppercase text-[10px] tracking-wider font-bold">
                        <th class="py-3.5 px-4">Staff Name & Contact</th>
                        <th class="py-3.5 px-4">Role</th>
                        <th class="py-3.5 px-4">Assigned Store</th>
                        <th class="py-3.5 px-4">Status</th>
                        <th class="py-3.5 px-4">Last Login</th>
                        <th class="py-3.5 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800 font-medium">
                    @forelse($staff as $user)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition">
                            <td class="py-3.5 px-4">
                                <div class="font-bold text-slate-900 dark:text-white">{{ $user->name }}</div>
                                <div class="text-[11px] text-slate-500">{{ $user->email }} • {{ $user->phone ?? 'No phone' }}</div>
                            </td>
                            <td class="py-3.5 px-4">
                                @php
                                    $roleColors = [
                                        'super_admin' => 'bg-purple-100 text-purple-700 dark:bg-purple-950 dark:text-purple-300',
                                        'store_manager' => 'bg-indigo-100 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300',
                                        'cashier' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300',
                                        'warehouse_staff' => 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300',
                                        'accountant' => 'bg-sky-100 text-sky-700 dark:bg-sky-950 dark:text-sky-300',
                                    ];
                                    $colorClass = $roleColors[$user->role->value] ?? 'bg-slate-100 text-slate-700';
                                @endphp
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase {{ $colorClass }}">
                                    {{ $user->role->label() }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-slate-700 dark:text-slate-300 font-medium">
                                @if($user->isSuperAdmin())
                                    <span class="text-slate-400 italic">All Stores (Super Admin)</span>
                                @elseif($user->store)
                                    <span>{{ $user->store->name }}</span>
                                    <span class="font-mono text-[10px] text-slate-400">({{ $user->store->code }})</span>
                                @else
                                    <span class="text-slate-400 italic">Unassigned</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4">
                                @if($user->is_active)
                                    <span class="inline-flex items-center gap-1.5 text-[11px] font-bold text-emerald-600">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Active
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 text-[11px] font-bold text-rose-500">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span> Deactivated
                                    </span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 font-mono text-[11px] text-slate-500">
                                @posDate($user->last_login_at) {{ $user->last_login_at ? $user->last_login_at->format('H:i') : '' }}
                            </td>
                            <td class="py-3.5 px-4 text-right">
                                <a href="{{ route('staff.edit', $user) }}" class="py-1 px-3 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 text-xs font-semibold">
                                    Manage
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-slate-400">No staff accounts found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t border-slate-100 dark:border-slate-800">
            {{ $staff->links() }}
        </div>
    </div>

</div>
@endsection
