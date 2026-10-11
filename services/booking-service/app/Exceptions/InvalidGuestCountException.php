<?php

namespace App\Exceptions;

// Guests must be inside the tier limits, for example Family allows 1 to 4.
class InvalidGuestCountException extends ApiException
{
    public function __construct(string $tierName, int $minGuests, int $maxGuests)
    {
        parent::__construct('INVALID_GUESTS', "{$tierName} tickets allow {$minGuests} to {$maxGuests} guests.", 422);
    }
}
