<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseReceivingItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_receiving_id',
        'purchase_order_item_id',
        'product_variant_id',
        'scanned_barcode',
        'quantity_received',
        'quantity_accepted',
        'quantity_shortage',
        'quantity_excess',
        'quantity_damaged',
        'unit_cost',
        'allocated_additional_cost',
        'landed_unit_cost',
        'notes',
    ];

    protected $casts = [
        'unit_cost' => 'decimal:2',
        'allocated_additional_cost' => 'decimal:2',
        'landed_unit_cost' => 'decimal:2',
    ];

    public function purchaseReceiving()
    {
        return $this->belongsTo(PurchaseReceiving::class);
    }

    public function purchaseOrderItem()
    {
        return $this->belongsTo(PurchaseOrderItem::class);
    }

    public function productVariant()
    {
        return $this->belongsTo(ProductVariant::class);
    }
}
