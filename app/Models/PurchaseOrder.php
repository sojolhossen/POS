<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'po_number',
        'supplier_id',
        'destination_store_id',
        'status',
        'total_amount',
        'additional_costs',
        'landed_cost_allocation_basis',
        'notes',
        'created_by',
        'approved_by',
        'approved_at',
        'completed_at',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'additional_costs' => 'decimal:2',
        'approved_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function destinationStore()
    {
        return $this->belongsTo(Store::class, 'destination_store_id');
    }

    public function items()
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function receivings()
    {
        return $this->hasMany(PurchaseReceiving::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function isApproved(): bool
    {
        return in_array($this->status, ['approved', 'partially_received', 'completed']);
    }

    public function canBeApproved(): bool
    {
        return in_array($this->status, ['draft', 'submitted']);
    }

    public function getTotalReceivedQtyAttribute(): int
    {
        return (int) $this->items()->sum('received_quantity');
    }

    public function getTotalAcceptedQtyAttribute(): int
    {
        return (int) $this->items()->sum('accepted_quantity');
    }
}
