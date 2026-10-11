<?php

namespace App\Exceptions;

// payment-service is down or the circuit breaker is open. The booking stays as it is so the visitor can try again.
class PaymentUnavailableException extends ApiException
{
    public function __construct()
    {
        parent::__construct('PAYMENT_UNAVAILABLE', 'Payment service is busy, please try again in a minute.', 503);
    }
}
