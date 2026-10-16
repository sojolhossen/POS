<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'phone',
        'email',
        'address',
        'total_spend',
        'loyalty_points',
    ];

    protected $casts = [
        'total_spend' => 'decimal:2',
        'loyalty_points' => 'integer',
    ];

    public function getTotalSpentAttribute(): float
    {
        return (float) $this->total_spend;
    }

    public function sales()
    {
        return $this->hasMany(PosSale::class);
    }

    public function returns()
    {
        return $this->hasMany(PosReturn::class);
    }

    public function pointLedgers()
    {
        return $this->hasMany(LoyaltyPointLedger::class);
    }
}
