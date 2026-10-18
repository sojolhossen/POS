<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Promotion extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'discount_percentage',
        'starts_at',
        'ends_at',
        'is_active',
        'category_id',
        'product_id',
        'min_spend',
        'description',
        'created_by',
    ];

    protected $casts = [
        'discount_percentage' => 'decimal:2',
        'min_spend' => 'decimal:2',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isCurrentlyActive(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $now = now();
        return $now->between($this->starts_at, $this->ends_at);
    }

    public function scopeActive($query)
    {
        $now = now();
        return $query->where('is_active', true)
                     ->where('starts_at', '<=', $now)
                     ->where('ends_at', '>=', $now);
    }
}
