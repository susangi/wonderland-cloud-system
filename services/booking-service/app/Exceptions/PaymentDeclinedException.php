<?php

namespace App\Exceptions;

// The card was declined by the payment service. The visitor can retry with another card.
class PaymentDeclinedException extends ApiException
{
    public function __construct(string $reason)
    {
        parent::__construct('PAYMENT_DECLINED', "Payment failed: {$reason}. Please try again or use another card.", 402);
    }
}
