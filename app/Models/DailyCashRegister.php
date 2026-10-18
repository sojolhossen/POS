<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyCashRegister extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_id',
        'business_date',
        'opening_cash',
        'opening_discrepancy_amount',
        'opening_discrepancy_reason',
        'opened_by',
        'opened_at',
        'cash_sales_amount',
        'cash_refunds_amount',
        'cash_expenses_amount',
        'cash_transfers_amount',
        'cash_in_hand_expected',
        'closing_cash_counted',
        'discrepancy_amount',
        'discrepancy_reason',
        'status',
        'closed_by',
        'closed_at',
        'reopened_by',
        'reopened_at',
        'reopen_reason',
    ];

    protected $casts = [
        'business_date' => 'date',
        'opening_cash' => 'decimal:2',
        'opening_discrepancy_amount' => 'decimal:2',
        'cash_sales_amount' => 'decimal:2',
        'cash_refunds_amount' => 'decimal:2',
        'cash_expenses_amount' => 'decimal:2',
        'cash_transfers_amount' => 'decimal:2',
        'cash_in_hand_expected' => 'decimal:2',
        'closing_cash_counted' => 'decimal:2',
        'discrepancy_amount' => 'decimal:2',
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
        'reopened_at' => 'datetime',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function opener()
    {
        return $this->belongsTo(User::class, 'opened_by');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'opened_by');
    }

    public function closer()
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function reopener()
    {
        return $this->belongsTo(User::class, 'reopened_by');
    }

    public function cashTransfers()
    {
        return $this->hasMany(StoreCashTransfer::class, 'daily_cash_register_id');
    }

    public function isOpen(): bool
    {
        return in_array($this->status, ['open', 'reopened']);
    }

    public function getCashSalesAttribute(): float
    {
        return (float) $this->cash_sales_amount;
    }

    public function getCashRefundsAttribute(): float
    {
        return (float) $this->cash_refunds_amount;
    }

    public function getCashExpensesAttribute(): float
    {
        return (float) $this->cash_expenses_amount;
    }

    public function getCashTransfersAttribute(): float
    {
        return (float) $this->cash_transfers_amount;
    }

    public function getExpectedClosingCashAttribute(): float
    {
        return (float) $this->cash_in_hand_expected;
    }

    public function getCashDiscrepancyAttribute(): float
    {
        return (float) $this->discrepancy_amount;
    }

    public function isClosed(): bool
    {
        return $this->status === 'closed';
    }
}
