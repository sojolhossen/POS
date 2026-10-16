<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupplierReturn extends Model
{
    use HasFactory;

    protected $fillable = [
        'return_number',
        'supplier_id',
        'store_id',
        'purchase_order_id',
        'status',
        'total_amount',
        'settlement_type',
        'settlement_reference',
        'reason',
        'created_by',
        'actioned_by',
        'actioned_at',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'actioned_at' => 'datetime',
    ];

    public function getTotalCreditAmountAttribute(): float
    {
        return (float) $this->total_amount;
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function items()
    {
        return $this->hasMany(SupplierReturnItem::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function actioner()
    {
        return $this->belongsTo(User::class, 'actioned_by');
    }
}
