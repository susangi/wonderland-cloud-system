<?php

namespace App\Clients;

use App\Exceptions\InvalidCardException;
use App\Exceptions\PaymentUnavailableException;
use App\Traits\CallsInternalServices;
use Illuminate\Http\Client\ConnectionException;

// Talks to payment-service. The card details pass straight through.
// We never save or log them here.
class PaymentClient
{
    use CallsInternalServices;

    // Returns the payment result: ['payment_id' => ..., 'status' => 'SUCCESS' or 'FAILED', 'failure_reason' => ...].
    public function charge(array $paymentRequest): array
    {
        try {
            $response = $this->internalHttp(config('wonderland.services.payment_url'))->post('/api/internal/payments', $paymentRequest);
        } catch (ConnectionException) {
            throw new PaymentUnavailableException;
        }

        if ($response->serverError()) {
            throw new PaymentUnavailableException;
        }

        // 422 means the card details are wrong. We show the payment service message to the visitor.
        if ($response->status() === 422) {
            throw new InvalidCardException($response->json('message', 'The card details are not valid.'));
        }

        if (! $response->successful()) {
            throw new PaymentUnavailableException;
        }

        return $response->json();
    }
}
