<?php

namespace App\Exceptions;

// Thrown when a payment id does not exist.
class PaymentNotFoundException extends ApiException
{
    public function __construct(string $paymentId)
    {
        parent::__construct('PAYMENT_NOT_FOUND', "Payment {$paymentId} was not found.", 404);
    }
}
