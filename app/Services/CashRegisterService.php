<?php

namespace App\Services;

use App\Models\DailyCashRegister;
use App\Models\Store;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;

class CashRegisterService
{
    public function __construct(
        protected AuditService $auditService
    ) {}

    /**
     * Open daily cash register for store for current business day.
     */
    public function openRegister(Store $store, User $user, float $openingCash): DailyCashRegister
    {
        $today = now()->format('Y-m-d');

        return DB::transaction(function () use ($store, $user, $openingCash, $today) {
            $existing = DailyCashRegister::where('store_id', $store->id)
                ->where('business_date', $today)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                if ($existing->isOpen()) {
                    throw new Exception("Cash register is already open for {$store->name} for today ({$today}).");
                }
                throw new Exception("Cash register for {$today} is already closed. Please request an authorized supervisor to reopen.");
            }

            $register = DailyCashRegister::create([
                'store_id' => $store->id,
                'business_date' => $today,
                'opening_cash' => $openingCash,
                'opened_by' => $user->id,
                'opened_at' => now(),
                'cash_sales_amount' => 0.00,
                'cash_refunds_amount' => 0.00,
                'cash_in_hand_expected' => $openingCash,
                'status' => 'open',
            ]);

            AuditService::log(
                action: 'cash_register.opened',
                auditable: $register,
                oldValues: null,
                newValues: ['store_id' => $store->id, 'opening_cash' => $openingCash, 'date' => $today],
                reason: "Opened daily cash register for {$store->name} on {$today}"
            );

            return $register;
        });
    }

    /**
     * Close the register and lock the business day.
     */
    public function closeRegister(
        DailyCashRegister $register,
        User $user,
        float $closingCashCounted,
        ?string $discrepancyReason = null
    ): DailyCashRegister {
        if (!$register->isOpen()) {
            throw new Exception("Cash register is already {$register->status}.");
        }

        return DB::transaction(function () use ($register, $user, $closingCashCounted, $discrepancyReason) {
            $expected = (float) $register->cash_in_hand_expected;
            $discrepancy = round($closingCashCounted - $expected, 2);

            if ($discrepancy != 0.0 && empty($discrepancyReason)) {
                throw new Exception("Cash discrepancy of ৳{$discrepancy} detected. A discrepancy reason is mandatory to close the register.");
            }

            $register->update([
                'closing_cash_counted' => $closingCashCounted,
                'discrepancy_amount' => $discrepancy,
                'discrepancy_reason' => $discrepancyReason,
                'status' => 'closed',
                'closed_by' => $user->id,
                'closed_at' => now(),
            ]);

            AuditService::log(
                action: 'cash_register.closed',
                auditable: $register,
                oldValues: ['status' => 'open'],
                newValues: ['status' => 'closed', 'counted' => $closingCashCounted, 'discrepancy' => $discrepancy],
                reason: "Closed daily cash register for {$register->store->name} on {$register->business_date->format('Y-m-d')}"
            );

            return $register;
        });
    }

    /**
     * Reopen a closed cash register (Store Manager or Super Admin only).
     */
    public function reopenRegister(
        DailyCashRegister $register,
        User $user,
        string $reason
    ): DailyCashRegister {
        if (!$user->isSuperAdmin() && !$user->isStoreManager()) {
            throw new Exception("Only a Store Manager or Super Admin can reopen a closed cash register.");
        }

        if ($register->isOpen()) {
            throw new Exception("Cash register is already open.");
        }

        return DB::transaction(function () use ($register, $user, $reason) {
            $register->update([
                'status' => 'reopened',
                'reopened_by' => $user->id,
                'reopened_at' => now(),
                'reopen_reason' => $reason,
            ]);

            AuditService::log(
                action: 'cash_register.reopened',
                auditable: $register,
                oldValues: ['status' => 'closed'],
                newValues: ['status' => 'reopened', 'reason' => $reason],
                reason: "Reopened daily cash register for {$register->store->name}"
            );

            return $register;
        });
    }

    /**
     * Record cash sale amount in the daily register.
     */
    public function recordCashSale(Store $store, float $amount): void
    {
        $today = now()->format('Y-m-d');
        $register = DailyCashRegister::where('store_id', $store->id)
            ->where('business_date', $today)
            ->first();

        if ($register && $register->isOpen()) {
            $register->increment('cash_sales_amount', $amount);
            $register->increment('cash_in_hand_expected', $amount);
        }
    }

    /**
     * Record cash refund amount in the daily register.
     */
    public function recordCashRefund(Store $store, float $amount): void
    {
        $today = now()->format('Y-m-d');
        $register = DailyCashRegister::where('store_id', $store->id)
            ->where('business_date', $today)
            ->first();

        if ($register && $register->isOpen()) {
            $register->increment('cash_refunds_amount', $amount);
            $register->decrement('cash_in_hand_expected', $amount);
        }
    }
}
