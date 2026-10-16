<?php

namespace App\Models;

use App\Enums\StockAdjustmentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockAdjustment extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_id',
        'product_variant_id',
        'old_quantity',
        'proposed_quantity',
        'adjustment_quantity',
        'reason',
        'status',
        'requested_by',
        'approved_by',
        'approved_at',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'status' => StockAdjustmentStatus::class,
            'old_quantity' => 'integer',
            'proposed_quantity' => 'integer',
            'adjustment_quantity' => 'integer',
            'approved_at' => 'datetime',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function isPending(): bool
    {
        return $this->status === StockAdjustmentStatus::PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === StockAdjustmentStatus::APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->status === StockAdjustmentStatus::REJECTED;
    }
}
