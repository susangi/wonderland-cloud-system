<?php

namespace App\Exceptions;

// The tier does not exist in pricing-service or an admin made it inactive.
class TierNotAvailableException extends ApiException
{
    public function __construct(int $tierId)
    {
        parent::__construct('TIER_NOT_AVAILABLE', "Ticket tier {$tierId} is not available for booking.", 422);
    }
}
