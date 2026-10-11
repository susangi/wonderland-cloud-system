<?php

namespace App\Exceptions;

// Thrown when a ticket tier id does not exist.
class TierNotFoundException extends ApiException
{
    public function __construct(int $tierId)
    {
        parent::__construct('TIER_NOT_FOUND', "Ticket tier {$tierId} was not found.", 404);
    }
}
