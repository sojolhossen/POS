<?php

namespace App\Models;

use App\Enums\StockTransactionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockLedger extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'transaction_type',
        'product_variant_id',
        'store_id',
        'related_store_id',
        'quantity',
        'balance_after',
        'unit_cost',
        'reference_number',
        'notes',
        'user_id',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'transaction_type' => StockTransactionType::class,
            'quantity' => 'integer',
            'balance_after' => 'integer',
            'unit_cost' => 'decimal:2',
            'created_at' => 'datetime',
        ];
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function relatedStore(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'related_store_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
