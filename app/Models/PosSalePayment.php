<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PosSalePayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'pos_sale_id',
        'payment_method',
        'amount',
        'tendered_amount',
        'change_amount',
        'card_last_four',
        'transaction_reference',
        'status',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'tendered_amount' => 'decimal:2',
        'change_amount' => 'decimal:2',
    ];

    public function posSale()
    {
        return $this->belongsTo(PosSale::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
