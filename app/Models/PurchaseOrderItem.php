<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_order_id',
        'product_variant_id',
        'ordered_quantity',
        'unit_cost',
        'additional_unit_cost',
        'landed_unit_cost',
        'received_quantity',
        'accepted_quantity',
        'shortage_quantity',
        'excess_quantity',
        'damaged_quantity',
    ];

    protected $casts = [
        'unit_cost' => 'decimal:2',
        'additional_unit_cost' => 'decimal:2',
        'landed_unit_cost' => 'decimal:2',
    ];

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function productVariant()
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function remainingQuantityToReceive(): int
    {
        return max(0, $this->ordered_quantity - $this->accepted_quantity);
    }
}
