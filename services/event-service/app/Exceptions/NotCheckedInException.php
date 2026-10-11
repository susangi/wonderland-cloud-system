<?php

namespace App\Exceptions;

// Ride use needs a gate check-in on the same day (assumption A7).
class NotCheckedInException extends ApiException
{
    public function __construct()
    {
        parent::__construct('NOT_CHECKED_IN', 'Not checked in today. Send the guest to the gate first.', 422);
    }
}
