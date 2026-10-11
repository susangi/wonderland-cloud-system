<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// One analytics event: ticket purchase, gate check-in or ride use.
class ParkEvent extends Model
{
    public const TYPE_TICKET_PURCHASE = 'TICKET_PURCHASE';
    public const TYPE_GATE_CHECKIN = 'GATE_CHECKIN';
    public const TYPE_RIDE_USAGE = 'RIDE_USAGE';

    public const SOURCE_LIVE = 'live';
    public const SOURCE_SIMULATED = 'simulated';

    protected $fillable = [
        'event_type',
        'booking_reference',
        'user_id',
        'tier_code',
        'amount',
        'guests',
        'attraction_id',
        'attraction_name',
        'occurred_at',
        'source',
    ];

    protected $attributes = [
        'source' => self::SOURCE_LIVE,
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'guests' => 'integer',
        'attraction_id' => 'integer',
        'occurred_at' => 'datetime',
    ];
}
