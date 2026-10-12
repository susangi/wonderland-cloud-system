<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// One daily visit slot, for example "Morning 09:00–12:00" for 200 guests.
class TimeSlot extends Model
{
    protected $fillable = [
        'label',
        'start_time',
        'end_time',
        'capacity',
        'is_active',
    ];

    protected $casts = [
        'capacity' => 'integer',
        'is_active' => 'boolean',
    ];
}
