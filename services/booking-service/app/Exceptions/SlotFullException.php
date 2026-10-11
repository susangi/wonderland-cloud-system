<?php

namespace App\Exceptions;

// Thrown when the chosen time slot does not have enough free places (design 6.3 step 4).
class SlotFullException extends ApiException
{
    public function __construct()
    {
        parent::__construct('SLOT_FULL', 'This time slot is full. Please choose another slot.', 409);
    }
}
