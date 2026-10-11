<?php

namespace App\Exceptions;

// One gate check-in per booking. All guests enter together (assumption A7).
class AlreadyCheckedInException extends ApiException
{
    public function __construct(string $checkedInTime)
    {
        parent::__construct('ALREADY_CHECKED_IN', "Already checked in at {$checkedInTime}.", 409);
    }
}
