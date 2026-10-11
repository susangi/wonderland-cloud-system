<?php

namespace App\Exceptions;

// No confirmed ticket with this reference. The booking may not be paid yet.
class TicketNotFoundException extends ApiException
{
    public function __construct(string $bookingReference)
    {
        parent::__construct('TICKET_NOT_FOUND', "Ticket {$bookingReference} was not found. The ticket may not be confirmed yet.", 404);
    }
}
