<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// One mock payment attempt for a booking. A booking can have many attempts (failed then success).
class Payment extends Model
{
    public const STATUS_SUCCESS = 'SUCCESS';
    public const STATUS_FAILED = 'FAILED';

    protected $fillable = [
        'payment_id',
        'booking_reference',
        'amount',
        'currency',
        'card_last4',
        'status',
        'failure_reason',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];
}
