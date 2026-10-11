<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// One booking made by a visitor. It starts as PENDING_PAYMENT and becomes CONFIRMED after payment.
class Booking extends Model
{
    public const STATUS_PENDING_PAYMENT = 'PENDING_PAYMENT';
    public const STATUS_CONFIRMED = 'CONFIRMED';
    public const STATUS_PAYMENT_FAILED = 'PAYMENT_FAILED';
    public const STATUS_EXPIRED = 'EXPIRED';

    // An unpaid booking holds its seats for this long. After that it expires and frees the seats.
    public const PAYMENT_HOLD_MINUTES = 15;

    protected $fillable = [
        'booking_reference',
        'user_id',
        'tier_id',
        'tier_code',
        'tier_name',
        'guests',
        'price_paid',
        'currency',
        'visit_date',
        'time_slot_id',
        'status',
        'payment_id',
        'confirmed_at',
    ];

    // visit_date is kept as a plain "Y-m-d" string on purpose, so queries compare it the same way in MySQL and SQLite.
    protected $casts = [
        'tier_id' => 'integer',
        'guests' => 'integer',
        'time_slot_id' => 'integer',
        'price_paid' => 'decimal:2',
        'confirmed_at' => 'datetime',
    ];

    public function timeSlot(): BelongsTo
    {
        return $this->belongsTo(TimeSlot::class);
    }

    // Unpaid bookings older than the hold time can no longer be paid.
    public function isPaymentHoldOver(): bool
    {
        return $this->created_at->lt(now()->subMinutes(self::PAYMENT_HOLD_MINUTES));
    }
}
