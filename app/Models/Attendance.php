<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    protected $fillable = [
        'user_id',
        'shift_id',
        'date',
        'check_in',
        'check_out',
        'status',
        'notes',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }
}

