<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupplierReturnItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'supplier_return_id',
        'product_variant_id',
        'quantity',
        'unit_cost',
        'condition',
        'item_reason',
    ];

    protected $casts = [
        'unit_cost' => 'decimal:2',
    ];

    public function supplierReturn()
    {
        return $this->belongsTo(SupplierReturn::class);
    }

    public function productVariant()
    {
        return $this->belongsTo(ProductVariant::class);
    }
}
