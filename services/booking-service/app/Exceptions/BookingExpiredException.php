<?php

namespace App\Exceptions;

// The visitor waited too long to pay, so the seats were released.
class BookingExpiredException extends ApiException
{
    public function __construct()
    {
        parent::__construct('BOOKING_EXPIRED', 'This booking has expired because it was not paid within 15 minutes. Please make a new booking.', 422);
    }
}
