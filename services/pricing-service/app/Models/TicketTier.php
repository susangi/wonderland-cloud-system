<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// One ticket tier, for example Family: 1 to 4 guests for USD 140.
class TicketTier extends Model
{
    protected $fillable = [
        'code',
        'name',
        'min_guests',
        'max_guests',
        'price',
        'currency',
        'is_active',
    ];

    // Same defaults as the table, so a new model shows them in the response.
    protected $attributes = [
        'currency' => 'USD',
        'is_active' => true,
    ];

    protected $casts = [
        'min_guests' => 'integer',
        'max_guests' => 'integer',
        'price' => 'decimal:2',
        'is_active' => 'boolean',
    ];
}
