<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinancialAccountTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'financial_account_id',
        'transaction_type',
        'amount',
        'fee_amount',
        'balance_after',
        'related_account_id',
        'reference_type',
        'reference_id',
        'reference_number',
        'description',
        'reconciliation_status',
        'reconciled_at',
        'reconciled_by',
        'user_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'fee_amount' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'reconciled_at' => 'datetime',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(FinancialAccount::class, 'financial_account_id');
    }

    public function relatedAccount(): BelongsTo
    {
        return $this->belongsTo(FinancialAccount::class, 'related_account_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reconciler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reconciled_by');
    }
}
