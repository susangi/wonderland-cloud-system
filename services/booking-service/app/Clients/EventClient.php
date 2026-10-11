<?php

namespace App\Clients;

use App\Traits\CallsInternalServices;
use RuntimeException;

// Talks to event-service. Used by the outbox to deliver TICKET_PURCHASE events.
class EventClient
{
    use CallsInternalServices;

    // Throws when the event could not be delivered. The outbox saves the error and tries again later.
    public function sendTicketPurchase(array $payload): void
    {
        $response = $this->internalHttp(config('wonderland.services.event_url'))->post('/api/internal/events/purchase', $payload);

        if (! $response->successful()) {
            throw new RuntimeException("event-service answered HTTP {$response->status()}");
        }
    }
}
