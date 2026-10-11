<?php

namespace App\Exceptions;

// The ride does not exist or an admin retired it.
class AttractionNotAvailableException extends ApiException
{
    public function __construct(int $attractionId)
    {
        parent::__construct('ATTRACTION_NOT_AVAILABLE', "Attraction {$attractionId} is not available for ride scans.", 422);
    }
}
