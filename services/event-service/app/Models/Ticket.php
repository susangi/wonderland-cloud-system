<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// A confirmed ticket copied from booking-service. Used for gate check-in and ride scans.
class Ticket extends Model
{
    // Simulator tickets use this prefix instead of "WL-", so they are never mixed up with real ones.
    public const SIMULATED_PREFIX = 'SIM-';

    protected $primaryKey = 'booking_reference';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'booking_reference',
        'user_id',
        'tier_code',
        'guests',
        'visit_date',
        'time_slot_label',
        'checked_in_at',
    ];

    // visit_date is kept as a plain "Y-m-d" string on purpose, so it compares the same way in MySQL and SQLite.
    protected $casts = [
        'guests' => 'integer',
        'checked_in_at' => 'datetime',
    ];

    public function isSimulated(): bool
    {
        return str_starts_with($this->booking_reference, self::SIMULATED_PREFIX);
    }

    // "FAMILY" becomes "Family" for messages shown to staff.
    public function tierName(): string
    {
        return ucfirst(strtolower($this->tier_code));
    }
}
