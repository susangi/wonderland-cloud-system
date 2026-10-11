<?php

namespace App\Exceptions;

// payment-service said the card details are wrong (for example the Luhn check failed).
class InvalidCardException extends ApiException
{
    public function __construct(string $message)
    {
        parent::__construct('INVALID_CARD', $message, 422);
    }
}
