<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PosSaleItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'pos_sale_id',
        'product_variant_id',
        'barcode',
        'product_name',
        'size',
        'color',
        'quantity',
        'unit_price',
        'discount_amount',
        'tax_amount',
        'line_total',
        'quantity_returned',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    public function posSale()
    {
        return $this->belongsTo(PosSale::class);
    }

    public function sale()
    {
        return $this->belongsTo(PosSale::class, 'pos_sale_id');
    }

    public function productVariant()
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function returnItems()
    {
        return $this->hasMany(PosReturnItem::class, 'original_pos_sale_item_id');
    }

    public function returnableQuantity(): int
    {
        return max(0, $this->quantity - $this->quantity_returned);
    }
}
