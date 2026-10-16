<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoyaltyPointLedger extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'event_type',
        'points',
        'balance_after',
        'reference_type',
        'reference_id',
        'notes',
        'created_by',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
