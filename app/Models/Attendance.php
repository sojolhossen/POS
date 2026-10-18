<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'store_id',
        'date',
        'status',
        'in_time',
        'out_time',
        'notes',
        'correction_reason',
        'recorded_by',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function getAttendanceDateAttribute()
    {
        return $this->date ? $this->date->format('Y-m-d') : null;
    }

    public function getCheckInAttribute()
    {
        return $this->in_time;
    }

    public function getCheckOutAttribute()
    {
        return $this->out_time;
    }
}
