<?php

namespace App\Exceptions;

// Thrown when a booking reference does not exist.
class BookingNotFoundException extends ApiException
{
    public function __construct(string $bookingReference)
    {
        parent::__construct('BOOKING_NOT_FOUND', "Booking {$bookingReference} was not found.", 404);
    }
}
