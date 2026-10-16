<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExpenseApprovalRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'role',
        'max_amount',
        'store_id',
    ];

    protected $casts = [
        'max_amount' => 'decimal:2',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
