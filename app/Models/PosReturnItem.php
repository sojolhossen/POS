<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PosReturnItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'pos_return_id',
        'original_pos_sale_item_id',
        'product_variant_id',
        'quantity',
        'unit_price',
        'line_total',
        'condition',
        'reason_description',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    public function posReturn()
    {
        return $this->belongsTo(PosReturn::class);
    }

    public function originalSaleItem()
    {
        return $this->belongsTo(PosSaleItem::class, 'original_pos_sale_item_id');
    }

    public function productVariant()
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }
}
