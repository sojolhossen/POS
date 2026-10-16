<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PosReturn extends Model
{
    use HasFactory;

    protected $fillable = [
        'return_number',
        'original_pos_sale_id',
        'replacement_pos_sale_id',
        'store_id',
        'customer_id',
        'processed_by',
        'approved_by',
        'is_receiptless',
        'return_type',
        'total_returned_value',
        'replacement_value',
        'net_settlement_amount',
        'settlement_method',
        'refund_reference',
        'reason',
    ];

    protected $casts = [
        'is_receiptless' => 'boolean',
        'total_returned_value' => 'decimal:2',
        'replacement_value' => 'decimal:2',
        'net_settlement_amount' => 'decimal:2',
    ];

    public function getTotalRefundAmountAttribute(): float
    {
        return (float) $this->total_returned_value;
    }

    public function originalSale()
    {
        return $this->belongsTo(PosSale::class, 'original_pos_sale_id');
    }

    public function replacementSale()
    {
        return $this->belongsTo(PosSale::class, 'replacement_pos_sale_id');
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function items()
    {
        return $this->hasMany(PosReturnItem::class);
    }

    public function processedBy()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
