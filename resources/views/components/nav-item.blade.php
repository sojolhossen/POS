@props([
    'module',
    'route',
    'active',
    'label',
    'badge' => null,
    'badgeClass' => 'bg-amber-500 text-white',
])

@php
    $canAccess = auth()->user()->canAccessModule($module);
    $isActive = request()->routeIs($active);
@endphp

@if($canAccess)
    <a href="{{ $route }}" 
       {!! $isActive ? 'data-active-nav="true"' : '' !!}
       class="flex items-center justify-between px-3 py-2 rounded-xl transition {{ $isActive ? 'bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 font-bold' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
        <div class="flex items-center gap-3 min-w-0">
            {{ $slot }}
            <span class="truncate">{{ $label }}</span>
        </div>
        @if($badge)
            <span class="px-2 py-0.5 text-[10px] font-black rounded-full {{ $badgeClass }}">
                {{ $badge }}
            </span>
        @endif
    </a>
@else
    <div class="flex items-center justify-between px-3 py-2 rounded-xl text-slate-400 dark:text-slate-600 bg-slate-50/50 dark:bg-slate-800/30 cursor-not-allowed select-none group transition" 
         title="{{ $label }} is locked by Store Owner (Super Admin)">
        <div class="flex items-center gap-3 min-w-0 opacity-60">
            {{ $slot }}
            <span class="truncate line-through decoration-slate-400/50">{{ $label }}</span>
        </div>
        <span class="inline-flex items-center gap-1 text-[10px] font-bold text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-950/70 border border-amber-200 dark:border-amber-900/60 px-1.5 py-0.5 rounded shadow-2xs shrink-0">
            <svg class="w-3 h-3 text-amber-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
            <span>Lock</span>
        </span>
    </div>
@endif
