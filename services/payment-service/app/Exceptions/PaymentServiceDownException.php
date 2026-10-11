<?php

namespace App\Exceptions;

// Used only when PAYMENT_SIMULATE_DOWN=true, to act like a broken payment provider.
// booking-service sees the 503, counts it as a failure and opens the circuit breaker.
class PaymentServiceDownException extends ApiException
{
    public function __construct()
    {
        parent::__construct('PAYMENT_SERVICE_DOWN', 'The payment service is not available right now.', 503);
    }
}
