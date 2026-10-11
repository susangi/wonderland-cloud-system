<?php

namespace App\Exceptions;

// Thrown when an attraction id does not exist.
class AttractionNotFoundException extends ApiException
{
    public function __construct(int $attractionId)
    {
        parent::__construct('ATTRACTION_NOT_FOUND', "Attraction {$attractionId} was not found.", 404);
    }
}
