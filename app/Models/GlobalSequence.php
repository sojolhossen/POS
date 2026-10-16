<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GlobalSequence extends Model
{
    use HasFactory;

    protected $fillable = [
        'sequence_key',
        'current_serial',
    ];

    protected $casts = [
        'current_serial' => 'integer',
    ];
}
