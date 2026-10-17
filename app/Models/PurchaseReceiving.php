<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseReceiving extends Model
{
    use HasFactory;

    protected $fillable = [
        'receiving_number',
        'purchase_order_id',
        'supplier_id',
        'store_id',
        'supplier_bill_number',
        'bill_amount',
        'additional_costs',
        'notes',
        'received_by',
        'received_at',
    ];

    protected $casts = [
        'bill_amount' => 'decimal:2',
        'additional_costs' => 'decimal:2',
        'received_at' => 'datetime',
    ];

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function items()
    {
        return $this->hasMany(PurchaseReceivingItem::class);
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function getReceivingDateAttribute()
    {
        return $this->received_at ?? $this->created_at;
    }

    public function getAllocatedAdditionalCostsAttribute()
    {
        return $this->additional_costs;
    }
}
