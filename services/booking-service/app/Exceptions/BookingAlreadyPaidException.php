<?php

namespace App\Exceptions;

// A confirmed booking cannot be paid again.
class BookingAlreadyPaidException extends ApiException
{
    public function __construct()
    {
        parent::__construct('BOOKING_ALREADY_PAID', 'This booking is already paid.', 409);
    }
}
