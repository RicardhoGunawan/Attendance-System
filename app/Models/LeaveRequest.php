<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveRequest extends Model
{
    protected $fillable = [
        'user_id',
        'start_date',
        'end_date',
        'reason',
        'status',
        'admin_notes',
    ];

    // Relasi user
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Set user_id otomatis
    public static function boot()
    {
        parent::boot();

        static::creating(function ($leaveRequest) {
            // Jika user sedang login, user_id otomatis diatur
            if (auth()->check()) {
                $leaveRequest->user_id = auth()->id(); // Menetapkan user_id yang sedang login
            }
        });
    }
}