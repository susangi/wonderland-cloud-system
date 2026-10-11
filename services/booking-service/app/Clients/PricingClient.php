<?php

namespace App\Clients;

use App\Exceptions\PricingUnavailableException;
use App\Traits\CallsInternalServices;
use Illuminate\Http\Client\ConnectionException;

// Talks to pricing-service. We never read pricing_db directly (one schema per service).
class PricingClient
{
    use CallsInternalServices;

    // Returns the tier as an array, or null when the tier id does not exist.
    public function getTier(int $tierId): ?array
    {
        try {
            $response = $this->internalHttp(config('wonderland.services.pricing_url'))->get("/api/tiers/{$tierId}");
        } catch (ConnectionException) {
            throw new PricingUnavailableException;
        }

        if ($response->notFound()) {
            return null;
        }

        if (! $response->successful()) {
            throw new PricingUnavailableException;
        }

        return $response->json();
    }
}
