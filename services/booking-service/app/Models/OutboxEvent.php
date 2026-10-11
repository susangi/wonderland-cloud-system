<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// An event waiting to be sent to event-service (transactional outbox, design 7.3).
class OutboxEvent extends Model
{
    public const TYPE_TICKET_PURCHASE = 'TICKET_PURCHASE';

    // After this many failed tries we stop. The row and last_error stay for checking.
    public const MAX_ATTEMPTS = 10;

    protected $fillable = [
        'type',
        'payload',
        'attempts',
        'sent_at',
        'last_error',
    ];

    protected $attributes = [
        'attempts' => 0,
    ];

    protected $casts = [
        'payload' => 'array',
        'attempts' => 'integer',
        'sent_at' => 'datetime',
    ];
}
