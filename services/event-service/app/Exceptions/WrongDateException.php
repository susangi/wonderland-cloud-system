<?php

namespace App\Exceptions;

// A ticket is valid at the gate only on its visit date (assumption A7).
class WrongDateException extends ApiException
{
    public function __construct(string $visitDayText)
    {
        parent::__construct('WRONG_DATE', "This ticket is for {$visitDayText}.", 422);
    }
}
