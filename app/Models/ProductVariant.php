<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductVariant extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'product_id',
        'size',
        'color',
        'barcode',
        'purchase_price',
        'selling_price',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'purchase_price' => 'decimal:2',
            'selling_price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function stockBalances(): HasMany
    {
        return $this->hasMany(StockBalance::class);
    }

    public function stockLedgers(): HasMany
    {
        return $this->hasMany(StockLedger::class);
    }

    public function priceHistories(): HasMany
    {
        return $this->hasMany(PriceHistory::class);
    }

    protected function effectiveSellingPrice(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->selling_price !== null ? (float) $this->selling_price : (float) $this->product->base_selling_price
        );
    }

    protected function effectivePurchasePrice(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->purchase_price !== null ? (float) $this->purchase_price : (float) $this->product->base_purchase_price
        );
    }

    protected function fullName(): Attribute
    {
        return Attribute::make(
            get: fn () => "{$this->product->name} ({$this->color} - {$this->size})"
        );
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
