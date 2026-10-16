<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PosSale extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_number',
        'store_id',
        'cashier_id',
        'customer_id',
        'customer_name',
        'customer_phone',
        'subtotal_amount',
        'discount_amount',
        'promotion_id',
        'tax_rate',
        'tax_amount',
        'rounding_adjustment',
        'total_payable',
        'total_paid',
        'change_returned',
        'loyalty_points_earned',
        'loyalty_points_redeemed',
        'loyalty_discount_amount',
        'status',
        'idempotency_key',
        'void_reason',
        'voided_by',
        'voided_at',
        'notes',
    ];

    protected $casts = [
        'subtotal_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'rounding_adjustment' => 'decimal:2',
        'total_payable' => 'decimal:2',
        'total_paid' => 'decimal:2',
        'change_returned' => 'decimal:2',
        'loyalty_discount_amount' => 'decimal:2',
        'voided_at' => 'datetime',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function cashier()
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function promotion()
    {
        return $this->belongsTo(Promotion::class);
    }

    public function items()
    {
        return $this->hasMany(PosSaleItem::class);
    }

    public function payments()
    {
        return $this->hasMany(PosSalePayment::class);
    }

    public function returns()
    {
        return $this->hasMany(PosReturn::class, 'original_pos_sale_id');
    }

    public function voidedByUser()
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    public function getSubtotalAttribute(): float
    {
        return (float) $this->subtotal_amount;
    }

    public function getTotalAmountAttribute(): float
    {
        return (float) $this->total_payable;
    }

    public function isWithinReturnWindow(int $days = 7): bool
    {
        return $this->created_at->diffInDays(now()) <= $days;
    }
}
