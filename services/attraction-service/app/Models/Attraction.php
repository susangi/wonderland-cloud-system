<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// One ride or fun activity in the park.
class Attraction extends Model
{
    // Allowed values, same as the ENUM columns. Requests use these for validation.
    public const CATEGORIES = ['rollercoaster', 'water', 'kids', 'interactive'];
    public const THRILL_LEVELS = ['low', 'medium', 'high', 'extreme'];

    public const STATUS_ACTIVE = 'active';
    public const STATUS_RETIRED = 'retired';

    protected $fillable = [
        'name',
        'description',
        'category',
        'thrill_level',
        'min_age',
        'min_height_cm',
        'status',
    ];

    // Same defaults as the table. Without these a newly created model would show null
    // for these fields in the response, because Eloquent does not reload database defaults.
    protected $attributes = [
        'min_age' => 0,
        'min_height_cm' => 0,
        'status' => self::STATUS_ACTIVE,
    ];

    protected $casts = [
        'min_age' => 'integer',
        'min_height_cm' => 'integer',
    ];
}
