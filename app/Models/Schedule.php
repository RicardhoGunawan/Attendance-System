<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    protected $fillable = [
        'name',
        'working_days',
        'start_time',
        'end_time',
    ];

    protected $casts = [
        'working_days' => 'array',
    ];
}
