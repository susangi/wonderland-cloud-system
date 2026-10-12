<?php

namespace App\Exceptions;

// Thrown when a visitor tries to open or pay somebody else's booking.
class BookingAccessDeniedException extends ApiException
{
    public function __construct()
    {
        parent::__construct('FORBIDDEN', 'This booking belongs to another visitor.', 403);
    }
}
