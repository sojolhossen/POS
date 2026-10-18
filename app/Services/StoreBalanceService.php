<?php

namespace App\Services;

use App\Models\DailyCashRegister;
use App\Models\PosSale;
use App\Models\Store;
use App\Models\User;

class StoreBalanceService
{
    /**
     * Compute header live balance and breakdown scoped by user role and store assignments.
     */
    public function getHeaderBalanceData(?User $user): array
    {
        if (!$user) {
            return [
                'liveBalance' => 0.0,
                'todaySales' => 0.0,
                'scopeName' => 'Guest',
                'storeBreakdown' => [],
                'isAllStores' => false,
            ];
        }

        $today = now()->toDateString();

        if ($user->isSuperAdmin()) {
            $stores = Store::where('is_active', true)->orderBy('is_warehouse')->orderBy('name')->get();
            $scopeName = 'All Stores Today';
            $isAllStores = true;
        } elseif ($user->isStoreManager()) {
            $stores = Store::where('is_active', true)
                ->where(function ($q) use ($user) {
                    $q->where('manager_id', $user->id);
                    if ($user->store_id) {
                        $q->orWhere('id', $user->store_id);
                    }
                })
                ->orderBy('name')
                ->get();
            $count = $stores->count();
            $scopeName = $count > 1 ? "{$count} Stores (Assigned)" : ($stores->first()?->name ?? 'Assigned Store');
            $isAllStores = false;
        } else {
            // Cashier, Warehouse Staff, Accountant, etc.
            if ($user->store_id) {
                $stores = Store::where('id', $user->store_id)->where('is_active', true)->get();
                $scopeName = $stores->first()?->name ?? 'Assigned Store';
            } else {
                $stores = collect();
                $scopeName = 'Unassigned Store';
            }
            $isAllStores = false;
        }

        $storeIds = $stores->pluck('id')->toArray();

        // 1. Daily cash registers today (or currently open session)
        $registers = empty($storeIds)
            ? collect()
            : DailyCashRegister::whereIn('store_id', $storeIds)
                ->where(function ($q) use ($today) {
                    $q->where('business_date', $today)
                      ->orWhere('status', 'open');
                })
                ->get()
                ->groupBy('store_id');

        // 2. Today's sales across all payment tenders
        $todaySales = empty($storeIds)
            ? 0.0
            : (float) PosSale::whereIn('store_id', $storeIds)
                ->whereDate('created_at', $today)
                ->where('status', 'completed')
                ->sum('total_payable');

        $totalLiveBalance = 0.0;
        $storeBreakdown = [];

        foreach ($stores as $st) {
            $storeRegs = $registers->get($st->id, collect());
            if ($storeRegs->isNotEmpty()) {
                // Store balance is sum of expected cash in active/today's registers
                $storeBalance = (float) $storeRegs->sum('cash_in_hand_expected');
            } else {
                // If not opened yet today, fallback to latest register closing balance
                $latestRegister = DailyCashRegister::where('store_id', $st->id)->latest('business_date')->first();
                $storeBalance = $latestRegister ? (float) ($latestRegister->closing_cash_counted ?? $latestRegister->cash_in_hand_expected) : 0.0;
            }
            $totalLiveBalance += $storeBalance;

            $hasOpenRegister = $storeRegs->contains(fn($r) => $r->isOpen());

            $storeBreakdown[] = [
                'id' => $st->id,
                'name' => $st->name,
                'code' => $st->code,
                'is_warehouse' => $st->is_warehouse,
                'balance' => $storeBalance,
                'has_open_register' => $hasOpenRegister,
            ];
        }

        return [
            'liveBalance' => $totalLiveBalance,
            'todaySales' => $todaySales,
            'scopeName' => $scopeName,
            'storeBreakdown' => $storeBreakdown,
            'isAllStores' => $isAllStores,
        ];
    }
}
