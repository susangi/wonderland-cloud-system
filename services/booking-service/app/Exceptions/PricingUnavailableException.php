<?php

namespace App\Exceptions;

// pricing-service did not answer, so we cannot get the price. We fail fast instead of guessing a price.
class PricingUnavailableException extends ApiException
{
    public function __construct()
    {
        parent::__construct('PRICING_UNAVAILABLE', 'Ticket prices cannot be loaded right now. Please try again shortly.', 503);
    }
}
