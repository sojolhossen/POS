<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DataImport extends Model
{
    use HasFactory;

    protected $fillable = [
        'import_batch_id',
        'dataset_type',
        'file_name',
        'file_path',
        'status',
        'total_rows',
        'successful_rows',
        'failed_rows',
        'validation_errors',
        'idempotency_key',
        'created_by',
        'confirmed_by',
        'confirmed_at',
    ];

    protected $casts = [
        'validation_errors' => 'array',
        'confirmed_at' => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function confirmer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }
}
