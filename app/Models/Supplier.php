<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'phone',
        'email',
        'address',
        'contact_person',
        'bank_details',
        'is_active',
        'outstanding_balance',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'outstanding_balance' => 'decimal:2',
    ];

    public function purchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function payments()
    {
        return $this->hasMany(SupplierPayment::class);
    }

    public function returns()
    {
        return $this->hasMany(SupplierReturn::class);
    }

    public function getTotalPaidAttribute(): float
    {
        return (float) $this->payments()->sum('amount');
    }

    public function getTotalPurchasesAttribute(): float
    {
        return (float) $this->purchaseOrders()->whereIn('status', ['approved', 'partially_received', 'completed'])->sum('total_amount');
    }
}
