@extends('layouts.app')

@section('title', 'Transfer Challan ' . $transfer->reference_number)
@section('header', 'Stock Transfer Challan')

@section('content')
<div class="space-y-6">

    <!-- Top Action Bar & Header Card -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl p-5 sm:p-6 border border-slate-200/80 dark:border-slate-800 shadow-sm flex flex-col lg:flex-row lg:items-center justify-between gap-5">
        <div class="space-y-2">
            <div class="flex items-center gap-2.5 flex-wrap">
                <a href="{{ route('transfers.index') }}" class="inline-flex items-center text-xs font-semibold text-slate-500 hover:text-indigo-600 dark:hover:text-indigo-400 transition">
                    <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    Stock Transfers
                </a>
                <span class="text-slate-300 dark:text-slate-700">/</span>
                <span class="font-mono text-xs font-bold text-indigo-700 dark:text-indigo-300 bg-indigo-50 dark:bg-indigo-950/70 px-2.5 py-0.5 rounded-lg border border-indigo-200/60 dark:border-indigo-900/60 shadow-xs">
                    {{ $transfer->reference_number }}
                </span>

                <!-- Status Badge -->
                @if($transfer->status === 'completed')
                    <span class="inline-flex items-center gap-1.5 px-3 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/80 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/60 shadow-xs">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        Completed & Received
                    </span>
                @elseif($transfer->status === 'in_transit')
                    <span class="inline-flex items-center gap-1.5 px-3 py-0.5 rounded-full text-xs font-bold bg-purple-100 text-purple-800 dark:bg-purple-950/80 dark:text-purple-300 border border-purple-200 dark:border-purple-800/60 shadow-xs">
                        <span class="w-2 h-2 rounded-full bg-purple-500 animate-pulse"></span>
                        In Transit (On Road)
                    </span>
                @elseif($transfer->status === 'approved')
                    <span class="inline-flex items-center gap-1.5 px-3 py-0.5 rounded-full text-xs font-bold bg-blue-100 text-blue-800 dark:bg-blue-950/80 dark:text-blue-300 border border-blue-200 dark:border-blue-800/60 shadow-xs">
                        <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                        Approved by Super Admin
                    </span>
                @elseif($transfer->status === 'requested')
                    <span class="inline-flex items-center gap-1.5 px-3 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800 dark:bg-amber-950/80 dark:text-amber-300 border border-amber-200 dark:border-amber-800/60 shadow-xs">
                        <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                        Awaiting Super Admin Approval
                    </span>
                @else
                    <span class="inline-flex items-center gap-1.5 px-3 py-0.5 rounded-full text-xs font-bold bg-rose-100 text-rose-800 dark:bg-rose-950/80 dark:text-rose-300 border border-rose-200 shadow-xs">
                        {{ strtoupper($transfer->status) }}
                    </span>
                @endif
            </div>

            <div class="flex items-center gap-2 flex-wrap">
                <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white tracking-tight">
                    {{ $transfer->sourceStore->name }}
                </h1>
                <span class="text-slate-400 dark:text-slate-600 font-bold">➔</span>
                <h1 class="text-xl sm:text-2xl font-black text-indigo-600 dark:text-indigo-400 tracking-tight">
                    {{ $transfer->destinationStore->name }}
                </h1>
            </div>

            <p class="text-xs text-slate-500 dark:text-slate-400 flex items-center gap-2">
                <span>Requested on <strong>{{ $transfer->created_at->format('d M Y, h:i A') }}</strong> by <strong class="text-slate-700 dark:text-slate-300">{{ $transfer->creator->name ?? 'Staff' }}</strong></span>
                @if($transfer->notes)
                    <span class="text-slate-300 dark:text-slate-700">•</span>
                    <span class="italic text-slate-500 dark:text-slate-400 font-medium">"{{ $transfer->notes }}"</span>
                @endif
            </p>
        </div>

        <!-- Action Controls -->
        <div class="flex items-center gap-2.5 flex-wrap">
            <!-- Enterprise Print Dropdown with Alpine.js -->
            <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                <div class="inline-flex rounded-xl shadow-xs overflow-hidden border border-slate-200 dark:border-slate-700">
                    <a href="{{ route('transfers.print', [$transfer, 'copy' => 'auto']) }}" target="_blank" class="px-3.5 py-2.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-100 text-xs font-bold transition flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-slate-600 dark:text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                        <span>Print Official Challan</span>
                    </a>
                    <button type="button" @click="open = !open" class="px-2.5 py-2.5 bg-slate-200 hover:bg-slate-300 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 text-xs border-l border-slate-300 dark:border-slate-600 transition">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                </div>

                <!-- Dropdown Menu for Role Specific Copies -->
                <div x-show="open" x-cloak class="absolute right-0 mt-2 w-64 bg-white dark:bg-slate-800 rounded-xl shadow-xl border border-slate-200 dark:border-slate-700 py-1.5 z-30 text-xs font-medium">
                    <div class="px-3 py-1.5 text-[10px] font-bold uppercase tracking-wider text-slate-400 border-b border-slate-100 dark:border-slate-700/60">
                        Select Role Copy / কপি নির্বাচন করুন
                    </div>
                    <a href="{{ route('transfers.print', [$transfer, 'copy' => 'auto']) }}" target="_blank" class="flex items-center gap-2 px-3 py-2 text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700/50">
                        <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                        <div>
                            <div class="font-bold">Auto (My Role Copy)</div>
                            <div class="text-[10px] text-slate-400">Current: {{ auth()->user()?->role_name ?? 'User' }}</div>
                        </div>
                    </a>
                    <a href="{{ route('transfers.print', [$transfer, 'copy' => 'super_admin']) }}" target="_blank" class="flex items-center gap-2 px-3 py-2 text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700/50">
                        <span class="text-sm">🏢</span>
                        <div>
                            <div class="font-bold">Super Admin / Audit Copy</div>
                            <div class="text-[10px] text-slate-400">With valuation & ledger ref</div>
                        </div>
                    </a>
                    <a href="{{ route('transfers.print', [$transfer, 'copy' => 'warehouse_staff']) }}" target="_blank" class="flex items-center gap-2 px-3 py-2 text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700/50">
                        <span class="text-sm">📦</span>
                        <div>
                            <div class="font-bold">Warehouse & Gate Pass Copy</div>
                            <div class="text-[10px] text-slate-400">Carton seals & security sign</div>
                        </div>
                    </a>
                    <a href="{{ route('transfers.print', [$transfer, 'copy' => 'store_manager']) }}" target="_blank" class="flex items-center gap-2 px-3 py-2 text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700/50">
                        <span class="text-sm">🏪</span>
                        <div>
                            <div class="font-bold">Store Receiving & Inward Copy</div>
                            <div class="text-[10px] text-slate-400">Discrepancy & shelf inward</div>
                        </div>
                    </a>
                    <div class="border-t border-slate-100 dark:border-slate-700/60 my-1"></div>
                    <a href="{{ route('transfers.print', [$transfer, 'copy' => 'triplicate']) }}" target="_blank" class="flex items-center gap-2 px-3 py-2 text-indigo-600 dark:text-indigo-400 font-bold hover:bg-indigo-50 dark:hover:bg-indigo-950/40">
                        <span class="text-sm">📑</span>
                        <div>
                            <div>Complete Triplicate (All 3 Copies)</div>
                            <div class="text-[10px] text-slate-400 font-normal">Official 3-copy full set</div>
                        </div>
                    </a>
                </div>
            </div>

            <!-- Approve Button (Top Action Bar) -->
            @if($transfer->status === 'requested')
                @if(auth()->user()?->isSuperAdmin())
                    <button type="submit" form="adminTransferForm" onclick="return confirm('Authorize this transfer for dispatch? Only Super Admin has this authority.');" class="px-4 py-2.5 rounded-xl text-white font-bold text-xs shadow-md shadow-blue-600/30 transition flex items-center gap-1.5 cursor-pointer hover:opacity-95" style="background: #2563eb !important; color: #ffffff !important;">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                        <span>Approve Transfer (Super Admin)</span>
                    </button>
                @else
                    <div class="inline-flex items-center gap-2 px-3.5 py-2.5 rounded-xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200/80 dark:border-amber-900/60 text-amber-800 dark:text-amber-300 text-xs font-semibold shadow-xs">
                        <svg class="w-4 h-4 text-amber-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                        <span>🔒 Awaiting Super Admin Approval</span>
                    </div>
                @endif
            @endif

            @if($transfer->status === 'approved')
                <a href="{{ route('transfers.dispatch.view', $transfer) }}" class="px-4 py-2.5 rounded-xl text-white font-bold text-xs shadow-md shadow-purple-600/25 transition flex items-center gap-1.5" style="background: #7c3aed !important; color: #ffffff !important;">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"/></svg>
                    <span>Dispatch Stock (Reduce Source)</span>
                </a>
            @endif

            @if($transfer->status === 'in_transit')
                @if(auth()->user()?->isSuperAdmin() || !auth()->user()?->store_id || auth()->user()?->store_id == $transfer->destination_store_id)
                    <a href="{{ route('transfers.receive.view', $transfer) }}" class="px-4 py-2.5 rounded-xl text-white font-bold text-xs shadow-md shadow-emerald-600/25 transition flex items-center gap-1.5" style="background: #059669 !important; color: #ffffff !important;">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span>Confirm Receiving at {{ $transfer->destinationStore->name }}</span>
                    </a>
                @else
                    <div class="inline-flex items-center gap-1.5 px-3.5 py-2.5 rounded-xl bg-purple-50 dark:bg-purple-950/40 border border-purple-200 dark:border-purple-900/60 text-purple-700 dark:text-purple-300 text-xs font-semibold">
                        <span class="w-2 h-2 rounded-full bg-purple-500 animate-ping"></span>
                        <span>In Transit to {{ $transfer->destinationStore->name }}</span>
                    </div>
                @endif
            @endif
        </div>
    </div>

    <!-- Prominent Super Admin Approval & Stock Review Console (When Awaiting Approval) -->
    @if($transfer->status === 'requested')
        @if(auth()->user()?->isSuperAdmin())
            <div class="rounded-2xl p-6 border border-blue-200 dark:border-blue-800 shadow-lg space-y-5" style="background: linear-gradient(135deg, #1e3a8a 0%, #1e1b4b 100%); color: #ffffff;">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-4 border-b border-white/10">
                    <div class="flex items-center gap-3.5">
                        <div class="w-12 h-12 rounded-xl bg-white/15 backdrop-blur-md flex items-center justify-center font-bold text-2xl shrink-0 shadow-inner">
                            🛡️
                        </div>
                        <div>
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="px-2 py-0.5 rounded text-[10px] font-black uppercase tracking-wider bg-amber-400 text-slate-900 shadow-xs">Super Admin Authority Only</span>
                                <span class="px-2 py-0.5 rounded text-[10px] font-black uppercase tracking-wider bg-white/20 text-white">Stock & Source Review</span>
                            </div>
                            <h3 class="text-base font-extrabold text-white mt-1">Stock Transfer Verification & Approval Console</h3>
                            <p class="text-xs text-blue-100">Review warehouse inventory availability. If Central Warehouse lacks stock, you can change the Supplying Location or modify requested quantities below.</p>
                        </div>
                    </div>
                </div>

                @if($hasStockDeficit)
                    <div class="p-3.5 rounded-xl bg-rose-500/20 border border-rose-400/40 text-rose-100 text-xs flex items-center gap-3">
                        <span class="text-xl shrink-0">⚠️</span>
                        <div>
                            <strong class="font-bold text-white text-sm">Warehouse Stock Shortage Detected:</strong>
                            <div class="mt-0.5">One or more requested garment variants exceed available stock at <strong>{{ $transfer->sourceStore->name }}</strong>. As Super Admin, you can change the Supplying Location below to another branch/warehouse or reduce the requested quantities before approving.</div>
                        </div>
                    </div>
                @else
                    <div class="p-3 rounded-xl bg-emerald-500/20 border border-emerald-400/30 text-emerald-200 text-xs flex items-center gap-2">
                        <span class="text-base font-bold">✓</span>
                        <span><strong>Stock Verified:</strong> Sufficient stock is currently available at <strong>{{ $transfer->sourceStore->name }}</strong> for all requested garment items.</span>
                    </div>
                @endif

                <form id="adminTransferForm" method="POST" action="{{ route('transfers.approve', $transfer) }}" class="space-y-4">
                    @csrf
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 items-end bg-white/5 p-4 rounded-xl border border-white/10">
                        <div class="lg:col-span-7">
                            <label class="block text-xs font-bold text-white mb-1.5 flex items-center gap-1.5">
                                <span>Source Store (Supplying Location)</span>
                                <span class="text-[10px] bg-amber-400/30 text-amber-200 px-1.5 py-0.5 rounded font-mono font-semibold">Only Super Admin Can Change</span>
                            </label>
                            <select name="source_store_id" class="w-full text-xs rounded-xl bg-slate-900/90 text-white border border-white/20 px-3.5 py-2.5 font-medium focus:ring-2 focus:ring-white">
                                @foreach($allStores as $st)
                                    <option value="{{ $st->id }}" {{ (old('source_store_id', $transfer->source_store_id) == $st->id) ? 'selected' : '' }} class="bg-slate-900 text-white">
                                        {{ $st->name }} ({{ $st->code }}) {{ $st->is_warehouse ? '— [Central Warehouse]' : '— [Retail Store Branch]' }}
                                    </option>
                                @endforeach
                            </select>
                            <p class="text-[11px] text-blue-200 mt-1">Change to another warehouse or retail branch if current location has insufficient stock.</p>
                        </div>

                        <div class="lg:col-span-5 flex items-center justify-end gap-2.5 pt-2">
                            <button type="submit" formaction="{{ route('transfers.modify', $transfer) }}" class="px-4 py-2.5 rounded-xl bg-white/10 hover:bg-white/20 text-white font-bold text-xs border border-white/20 transition cursor-pointer flex items-center gap-1.5">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                                <span>Save Changes Only</span>
                            </button>
                            <button type="submit" onclick="return confirm('Authorize and approve this transfer with the configured supplying store and quantities?');" class="px-5 py-2.5 rounded-xl font-black text-xs shadow-lg transition flex items-center gap-2 cursor-pointer hover:bg-slate-100 active:scale-95 bg-white text-blue-900">
                                <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                <span>✓ Authorize & Approve</span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        @else
            <div class="rounded-2xl p-4 bg-amber-50/70 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-900/60 flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-amber-100 dark:bg-amber-900/50 text-amber-800 dark:text-amber-300 flex items-center justify-center text-lg shrink-0">
                    🔒
                </div>
                <div>
                    <h4 class="text-xs font-bold text-amber-900 dark:text-amber-200">Awaiting Super Admin Approval (অনুমোদনের অপেক্ষায়)</h4>
                    <p class="text-[11px] text-amber-700 dark:text-amber-400 mt-0.5">This requisition has been placed and is currently awaiting approval from the Super Administrator. Only Super Admin has authority to re-route source stock or modify requested quantities.</p>
                </div>
            </div>
        @endif
    @endif

    <!-- Interactive Visual Stepper (Horizontal Lifecycle Tracker) -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl p-6 border border-slate-200/80 dark:border-slate-800 shadow-sm">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 relative">
            <!-- Connecting Line on Desktop -->
            <div class="hidden md:block absolute top-1/2 left-8 right-8 h-1 bg-slate-100 dark:bg-slate-800 -translate-y-4 -z-0"></div>

            <!-- Step 1: Requisition -->
            <div class="relative z-10 flex flex-col items-center text-center p-3 rounded-xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 shadow-xs">
                <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-xs bg-emerald-500 text-white shadow-sm mb-2 ring-4 ring-emerald-50 dark:ring-emerald-950/40">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                </div>
                <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Step 1</div>
                <div class="text-xs font-black text-slate-800 dark:text-slate-100 mt-0.5">Requisition Created</div>
                <div class="text-[11px] text-slate-500 mt-1 font-medium">{{ $transfer->creator->name ?? 'Staff' }}</div>
                <div class="text-[10px] text-slate-400 font-mono mt-0.5">{{ $transfer->created_at->format('d M, h:i A') }}</div>
            </div>

            <!-- Step 2: Super Admin Approval -->
            @php $isApproved = in_array($transfer->status, ['approved', 'in_transit', 'completed']); @endphp
            <div class="relative z-10 flex flex-col items-center text-center p-3 rounded-xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 shadow-xs">
                <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-xs {{ $isApproved ? 'bg-blue-600 text-white ring-4 ring-blue-50 dark:ring-blue-950/40 shadow-sm' : 'bg-slate-100 dark:bg-slate-800 text-slate-400' }} mb-2">
                    @if($isApproved)
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                    @else
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    @endif
                </div>
                <div class="text-[11px] font-bold uppercase tracking-wider {{ $isApproved ? 'text-blue-600 dark:text-blue-400' : 'text-slate-400' }}">Step 2</div>
                <div class="text-xs font-black text-slate-800 dark:text-slate-100 mt-0.5">Super Admin Approval</div>
                <div class="text-[11px] {{ $isApproved ? 'text-slate-600 dark:text-slate-300 font-semibold' : 'text-slate-400' }} mt-1">
                    {{ $transfer->approver->name ?? ($isApproved ? 'Super Admin' : 'Pending Approval') }}
                </div>
                <div class="text-[10px] text-slate-400 font-mono mt-0.5">
                    {{ $transfer->approved_at ? $transfer->approved_at->format('d M, h:i A') : 'Awaiting' }}
                </div>
            </div>

            <!-- Step 3: Warehouse Dispatch -->
            @php $isDispatched = in_array($transfer->status, ['in_transit', 'completed']); @endphp
            <div class="relative z-10 flex flex-col items-center text-center p-3 rounded-xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 shadow-xs">
                <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-xs {{ $isDispatched ? 'bg-purple-600 text-white ring-4 ring-purple-50 dark:ring-purple-950/40 shadow-sm' : 'bg-slate-100 dark:bg-slate-800 text-slate-400' }} mb-2">
                    @if($isDispatched)
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                    @else
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"/></svg>
                    @endif
                </div>
                <div class="text-[11px] font-bold uppercase tracking-wider {{ $isDispatched ? 'text-purple-600 dark:text-purple-400' : 'text-slate-400' }}">Step 3</div>
                <div class="text-xs font-black text-slate-800 dark:text-slate-100 mt-0.5">Dispatched & In Transit</div>
                <div class="text-[11px] {{ $isDispatched ? 'text-slate-600 dark:text-slate-300 font-semibold' : 'text-slate-400' }} mt-1">
                    {{ $transfer->dispatcher->name ?? ($isDispatched ? 'Warehouse Staff' : 'Pending Dispatch') }}
                </div>
                <div class="text-[10px] text-slate-400 font-mono mt-0.5">
                    {{ $transfer->dispatched_at ? $transfer->dispatched_at->format('d M, h:i A') : 'Pending' }}
                </div>
            </div>

            <!-- Step 4: Destination Receipt -->
            @php $isCompleted = $transfer->status === 'completed'; @endphp
            <div class="relative z-10 flex flex-col items-center text-center p-3 rounded-xl bg-white dark:bg-slate-900 border {{ $isCompleted ? 'border-emerald-300 dark:border-emerald-800 ring-2 ring-emerald-500/20' : 'border-slate-200/80 dark:border-slate-800/80' }} shadow-xs">
                <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-xs {{ $isCompleted ? 'bg-emerald-600 text-white ring-4 ring-emerald-50 dark:ring-emerald-950/40 shadow-sm' : 'bg-slate-100 dark:bg-slate-800 text-slate-400' }} mb-2">
                    @if($isCompleted)
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                    @else
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    @endif
                </div>
                <div class="text-[11px] font-bold uppercase tracking-wider {{ $isCompleted ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400' }}">Step 4</div>
                <div class="text-xs font-black text-slate-800 dark:text-slate-100 mt-0.5">Verified & Received</div>
                <div class="text-[11px] {{ $isCompleted ? 'text-emerald-700 dark:text-emerald-300 font-bold' : 'text-slate-400' }} mt-1">
                    {{ $transfer->receiver->name ?? ($isCompleted ? 'Store Staff' : 'Awaiting Receipt') }}
                </div>
                <div class="text-[10px] text-slate-400 font-mono mt-0.5">
                    {{ $transfer->received_at ? $transfer->received_at->format('d M, h:i A') : 'Pending' }}
                </div>
            </div>
        </div>
    </div>

    <!-- Logistics Route & Store Information Card -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <!-- Origin Store -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl p-5 border border-slate-200/80 dark:border-slate-800 shadow-sm flex items-start gap-4">
            <div class="w-12 h-12 rounded-xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-600 dark:text-slate-300 shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
            </div>
            <div class="space-y-1 min-w-0">
                <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Source Store / Dispatch Warehouse</div>
                <h3 class="text-base font-bold text-slate-900 dark:text-white truncate">{{ $transfer->sourceStore->name }}</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400">{{ $transfer->sourceStore->address ?? 'Central Logistics Facility' }}</p>
                <div class="inline-block font-mono text-[11px] font-semibold text-slate-600 dark:text-slate-300 bg-slate-100 dark:bg-slate-800 px-2 py-0.5 rounded">
                    Code: {{ $transfer->sourceStore->code }}
                </div>
            </div>
        </div>

        <!-- Destination Store -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl p-5 border border-slate-200/80 dark:border-slate-800 shadow-sm flex items-start gap-4">
            <div class="w-12 h-12 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 flex items-center justify-center text-indigo-600 dark:text-indigo-400 shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            </div>
            <div class="space-y-1 min-w-0">
                <div class="text-[10px] font-bold uppercase tracking-wider text-indigo-600 dark:text-indigo-400">Destination Store (Target Retail Outlet)</div>
                <h3 class="text-base font-bold text-slate-900 dark:text-white truncate">{{ $transfer->destinationStore->name }}</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400">{{ $transfer->destinationStore->address ?? 'Retail Outlet Branch' }}</p>
                <div class="inline-block font-mono text-[11px] font-semibold text-indigo-700 dark:text-indigo-300 bg-indigo-50 dark:bg-indigo-950/60 px-2 py-0.5 rounded">
                    Code: {{ $transfer->destinationStore->code }}
                </div>
            </div>
        </div>
    </div>

    <!-- Garments & Items Table Card -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm overflow-hidden">
        <div class="p-5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
            <div>
                <h2 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                    <span>Transferred Garments & Stock Items</span>
                </h2>
                <p class="text-xs text-slate-400 mt-0.5">Itemized reconciliation of units requested, dispatched, received, and discrepancies.</p>
            </div>
            <span class="text-xs font-mono font-bold bg-slate-100 dark:bg-slate-800 px-3 py-1 rounded-lg text-slate-700 dark:text-slate-300">
                {{ $transfer->items->count() }} Line {{ Str::plural('Item', $transfer->items->count()) }}
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="bg-slate-50/80 dark:bg-slate-800/60 text-slate-600 dark:text-slate-300 font-bold border-b border-slate-200/80 dark:border-slate-800">
                        <th class="py-3 px-4 w-12 text-center">#</th>
                        <th class="py-3 px-4">Garment / Product Description</th>
                        <th class="py-3 px-4 font-mono">SKU / Barcode</th>
                        <th class="py-3 px-4 text-center">
                            <div>Source Live Stock</div>
                            <div class="text-[10px] font-normal text-slate-400">({{ $transfer->sourceStore->name }})</div>
                        </th>
                        <th class="py-3 px-4 text-right">
                            <div>Requested Qty</div>
                            @if($transfer->status === 'requested' && auth()->user()?->isSuperAdmin())
                                <div class="text-[9px] font-semibold text-blue-600 dark:text-blue-400 uppercase">Editable by Admin</div>
                            @endif
                        </th>
                        <th class="py-3 px-4 text-right">Dispatched</th>
                        <th class="py-3 px-4 text-right text-emerald-600 dark:text-emerald-400">Confirmed Rec.</th>
                        <th class="py-3 px-4 text-right text-rose-600 dark:text-rose-400">Discrepancy</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @foreach($transfer->items as $idx => $it)
                        @php
                            $stock = $sourceStocks[$it->product_variant_id] ?? ['on_hand' => 0, 'available' => 0];
                            $avail = (int) ($stock['available'] ?? 0);
                            $onHand = (int) ($stock['on_hand'] ?? 0);
                            $isShort = ($transfer->status === 'requested') && ($avail < $it->quantity_requested);
                        @endphp
                        <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition {{ $isShort ? 'bg-rose-50/20 dark:bg-rose-950/20' : '' }}">
                            <td class="py-3 px-4 text-center font-mono text-slate-400 font-medium">{{ $idx + 1 }}</td>
                            <td class="py-3 px-4">
                                <div class="font-bold text-slate-900 dark:text-white text-sm">
                                    {{ $it->productVariant?->product?->name ?? 'Product' }}
                                </div>
                                <div class="flex items-center gap-1.5 mt-1">
                                    @if($it->productVariant?->color)
                                        <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300">
                                            Color: {{ $it->productVariant->color }}
                                        </span>
                                    @endif
                                    @if($it->productVariant?->size)
                                        <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300">
                                            Size: {{ $it->productVariant->size }}
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="py-3 px-4 font-mono text-[11px] text-slate-500">
                                <div>{{ $it->productVariant->sku ?? 'N/A' }}</div>
                                @if($it->productVariant?->barcode)
                                    <div class="text-[10px] text-slate-400">{{ $it->productVariant->barcode }}</div>
                                @endif
                            </td>

                            <!-- Live Warehouse / Source Stock -->
                            <td class="py-3 px-4 text-center">
                                <div class="inline-flex flex-col items-center gap-0.5">
                                    <span class="font-mono text-xs font-bold {{ $isShort ? 'text-rose-600 dark:text-rose-400' : 'text-emerald-600 dark:text-emerald-400' }}">
                                        {{ $avail }} pcs avail
                                    </span>
                                    <span class="text-[10px] text-slate-400">
                                        ({{ $onHand }} on hand)
                                    </span>
                                    @if($isShort)
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-black bg-rose-100 text-rose-700 dark:bg-rose-950 dark:text-rose-300">
                                            ⚠️ Short: {{ $it->quantity_requested - $avail }} pcs
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-semibold bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300">
                                            ✓ In Stock
                                        </span>
                                    @endif
                                </div>
                            </td>

                            <!-- Requested Quantity (Editable ONLY for Super Admin before approval) -->
                            <td class="py-3 px-4 text-right font-mono font-bold">
                                @if($transfer->status === 'requested' && auth()->user()?->isSuperAdmin())
                                    <div class="flex items-center justify-end gap-1.5">
                                        <input type="number" form="adminTransferForm" name="quantities[{{ $it->id }}]" value="{{ old('quantities.'.$it->id, $it->quantity_requested) }}" min="1" required
                                               class="w-20 text-center text-xs font-mono font-bold rounded-lg border-2 border-blue-400 dark:border-blue-600 bg-white dark:bg-slate-900 text-blue-900 dark:text-blue-100 py-1 px-1.5 focus:ring-2 focus:ring-blue-500 shadow-xs">
                                        <span class="text-[10px] text-slate-400">pcs</span>
                                    </div>
                                    <div class="text-[9px] text-blue-600 dark:text-blue-400 font-sans mt-0.5 font-normal">Super Admin Can Edit</div>
                                @else
                                    <span class="bg-slate-100 dark:bg-slate-800 px-2 py-1 rounded text-xs text-slate-700 dark:text-slate-300">
                                        {{ $it->quantity_requested }} pcs
                                    </span>
                                    @if($transfer->status === 'requested')
                                        <div class="text-[9px] text-slate-400 font-sans mt-0.5 flex items-center justify-end gap-0.5">
                                            <svg class="w-2.5 h-2.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                            <span>Locked</span>
                                        </div>
                                    @endif
                                @endif
                            </td>

                            <td class="py-3 px-4 text-right font-mono font-bold text-slate-900 dark:text-white">
                                <span class="bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 px-2 py-1 rounded text-xs">
                                    {{ $it->quantity_dispatched }} pcs
                                </span>
                            </td>
                            <td class="py-3 px-4 text-right font-mono font-bold text-emerald-600 dark:text-emerald-400">
                                <span class="bg-emerald-50 dark:bg-emerald-950/60 px-2 py-1 rounded text-xs">
                                    {{ $it->quantity_received }} pcs
                                </span>
                            </td>
                            <td class="py-3 px-4 text-right font-mono text-rose-600 dark:text-rose-400">
                                @if(($it->quantity_discrepancy ?? 0) != 0)
                                    <span class="bg-rose-50 dark:bg-rose-950/60 px-2 py-1 rounded text-xs font-bold">
                                        {{ $it->quantity_discrepancy }} pcs
                                    </span>
                                @else
                                    <span class="text-slate-300 dark:text-slate-700 font-bold">&mdash;</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <!-- Summary Totals Footer -->
                <tfoot class="bg-slate-50/90 dark:bg-slate-800/80 font-bold border-t-2 border-slate-200 dark:border-slate-700">
                    <tr>
                        <td colspan="4" class="py-3 px-4 text-right text-slate-600 dark:text-slate-300 uppercase tracking-wider text-[11px]">
                            Total Sum:
                        </td>
                        <td class="py-3 px-4 text-right font-mono text-slate-800 dark:text-slate-200 text-xs">
                            {{ $transfer->items->sum('quantity_requested') }} pcs
                        </td>
                        <td class="py-3 px-4 text-right font-mono text-indigo-700 dark:text-indigo-300 text-xs">
                            {{ $transfer->items->sum('quantity_dispatched') }} pcs
                        </td>
                        <td class="py-3 px-4 text-right font-mono text-emerald-600 dark:text-emerald-400 text-xs">
                            {{ $transfer->items->sum('quantity_received') }} pcs
                        </td>
                        <td class="py-3 px-4 text-right font-mono text-rose-600 dark:text-rose-400 text-xs">
                            {{ $transfer->items->sum('quantity_discrepancy') }} pcs
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- Chain of Custody & Audit Footprint -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm p-6 space-y-6">
        <div>
            <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                <span>Chain of Custody & Official Signatures (দায়িত্বশীল কর্মকর্তাদের স্বাক্ষর ও ট্র্যাকিং)</span>
            </h3>
            <p class="text-xs text-slate-400 mt-0.5">Every step is verified and permanently linked to the respective officer's identity.</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- 1. Creator -->
            <div class="p-4 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/30 flex items-start gap-3">
                <div class="w-9 h-9 rounded-full bg-slate-200 dark:bg-slate-700 flex items-center justify-center font-bold text-xs text-slate-700 dark:text-slate-200 shrink-0">
                    {{ strtoupper(substr($transfer->creator->name ?? 'U', 0, 2)) }}
                </div>
                <div class="min-w-0 space-y-0.5">
                    <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">1. Requisition Prepared</div>
                    <div class="text-sm font-bold text-slate-900 dark:text-white truncate">{{ $transfer->creator->name ?? 'Requester' }}</div>
                    <div class="text-xs text-slate-500">{{ $transfer->creator?->role_name ?? 'Staff' }}</div>
                    <div class="text-[10px] text-slate-400 font-mono">{{ $transfer->created_at->format('d M Y, h:i A') }}</div>
                </div>
            </div>

            <!-- 2. Super Admin Approval -->
            <div class="p-4 rounded-xl border {{ $transfer->approver ? 'border-blue-200 dark:border-blue-900/60 bg-blue-50/40 dark:bg-blue-950/20' : 'border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/30' }} flex items-start gap-3">
                <div class="w-9 h-9 rounded-full {{ $transfer->approver ? 'bg-blue-600 text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-400' }} flex items-center justify-center font-bold text-xs shrink-0">
                    @if($transfer->approver)
                        {{ strtoupper(substr($transfer->approver->name ?? 'SA', 0, 2)) }}
                    @else
                        🔒
                    @endif
                </div>
                <div class="min-w-0 space-y-0.5">
                    <div class="text-[10px] font-bold uppercase tracking-wider {{ $transfer->approver ? 'text-blue-600 dark:text-blue-400' : 'text-slate-400' }}">2. Super Admin Approval</div>
                    <div class="text-sm font-bold text-slate-900 dark:text-white truncate">
                        {{ $transfer->approver->name ?? 'Awaiting Super Admin' }}
                    </div>
                    <div class="text-xs text-slate-500">
                        {{ $transfer->approver ? 'Super Admin' : 'Super Admin Only' }}
                    </div>
                    <div class="text-[10px] text-slate-400 font-mono">
                        {{ $transfer->approved_at ? $transfer->approved_at->format('d M Y, h:i A') : 'Pending' }}
                    </div>
                </div>
            </div>

            <!-- 3. Dispatcher -->
            <div class="p-4 rounded-xl border {{ $transfer->dispatcher ? 'border-indigo-200 dark:border-indigo-900/60 bg-indigo-50/40 dark:bg-indigo-950/20' : 'border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/30' }} flex items-start gap-3">
                <div class="w-9 h-9 rounded-full {{ $transfer->dispatcher ? 'bg-indigo-600 text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-400' }} flex items-center justify-center font-bold text-xs shrink-0">
                    {{ $transfer->dispatcher ? strtoupper(substr($transfer->dispatcher->name, 0, 2)) : '⏳' }}
                </div>
                <div class="min-w-0 space-y-0.5">
                    <div class="text-[10px] font-bold uppercase tracking-wider {{ $transfer->dispatcher ? 'text-indigo-600 dark:text-indigo-400' : 'text-slate-400' }}">3. Stock Dispatcher</div>
                    <div class="text-sm font-bold text-slate-900 dark:text-white truncate">
                        {{ $transfer->dispatcher->name ?? 'Pending Dispatch' }}
                    </div>
                    <div class="text-xs text-slate-500">
                        {{ $transfer->dispatcher?->role_name ?? 'Source Store' }}
                    </div>
                    <div class="text-[10px] text-slate-400 font-mono">
                        {{ $transfer->dispatched_at ? $transfer->dispatched_at->format('d M Y, h:i A') : 'Pending' }}
                    </div>
                </div>
            </div>

            <!-- 4. Verified Receiver -->
            <div class="p-4 rounded-xl border {{ $transfer->receiver ? 'border-emerald-300 dark:border-emerald-800 bg-emerald-50/60 dark:bg-emerald-950/30 ring-2 ring-emerald-500/20' : 'border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/30' }} flex items-start gap-3">
                <div class="w-9 h-9 rounded-full {{ $transfer->receiver ? 'bg-emerald-600 text-white shadow-sm' : 'bg-slate-200 dark:bg-slate-700 text-slate-400' }} flex items-center justify-center font-bold text-xs shrink-0">
                    {{ $transfer->receiver ? strtoupper(substr($transfer->receiver->name, 0, 2)) : '📥' }}
                </div>
                <div class="min-w-0 space-y-0.5">
                    <div class="text-[10px] font-bold uppercase tracking-wider {{ $transfer->receiver ? 'text-emerald-700 dark:text-emerald-400' : 'text-slate-400' }} flex items-center gap-1">
                        <span>4. Destination Receiver</span>
                        @if($transfer->receiver)
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                        @endif
                    </div>
                    <div class="text-sm font-black text-slate-900 dark:text-white truncate">
                        {{ $transfer->receiver->name ?? 'Pending Arrival' }}
                    </div>
                    <div class="text-xs text-slate-500">
                        {{ $transfer->receiver ? $transfer->receiver->role_name : $transfer->destinationStore->name . ' Staff' }}
                    </div>
                    <div class="text-[10px] text-slate-400 font-mono">
                        {{ $transfer->received_at ? $transfer->received_at->format('d M Y, h:i A') : 'Awaiting receipt' }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Event Audit Trail Log -->
        @if($transfer->auditLogs && $transfer->auditLogs->count() > 0)
            <div class="pt-4 border-t border-slate-100 dark:border-slate-800">
                <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-3 flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span>System Audit Trail History</span>
                </h4>
                <div class="overflow-x-auto rounded-xl border border-slate-100 dark:border-slate-800">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 dark:bg-slate-800/60 text-slate-600 dark:text-slate-300 font-bold border-b border-slate-100 dark:border-slate-800">
                            <tr>
                                <th class="p-2.5">Event Action</th>
                                <th class="p-2.5">Officer / User</th>
                                <th class="p-2.5">Designation</th>
                                <th class="p-2.5">Timestamp</th>
                                <th class="p-2.5">Details</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @foreach($transfer->auditLogs as $log)
                                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition">
                                    <td class="p-2.5 font-mono">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold
                                            @if(str_contains($log->action, 'received')) bg-emerald-100 text-emerald-800 dark:bg-emerald-950/70 dark:text-emerald-300
                                            @elseif(str_contains($log->action, 'approved')) bg-blue-100 text-blue-800 dark:bg-blue-950/70 dark:text-blue-300
                                            @elseif(str_contains($log->action, 'dispatched')) bg-indigo-100 text-indigo-800 dark:bg-indigo-950/70 dark:text-indigo-300
                                            @else bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 @endif">
                                            {{ $log->action }}
                                        </span>
                                    </td>
                                    <td class="p-2.5 font-semibold text-slate-800 dark:text-slate-200">
                                        {{ $log->user->name ?? 'System' }}
                                    </td>
                                    <td class="p-2.5 text-slate-500">
                                        {{ $log->user?->role_name ?? 'N/A' }}
                                    </td>
                                    <td class="p-2.5 font-mono text-slate-400">
                                        {{ $log->created_at->format('d M Y, h:i:s A') }}
                                    </td>
                                    <td class="p-2.5 text-slate-600 dark:text-slate-300">
                                        {{ $log->reason ?? '-' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>

</div>
@endsection
