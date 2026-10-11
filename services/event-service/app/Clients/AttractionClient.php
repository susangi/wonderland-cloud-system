<?php

namespace App\Clients;

use App\Exceptions\AttractionServiceUnavailableException;
use App\Traits\CallsInternalServices;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;

// Talks to attraction-service. We never read attraction_db directly (one schema per service).
class AttractionClient
{
    use CallsInternalServices;

    // Ride details change rarely, so we keep them for 10 minutes.
    // This makes ride scan bursts cheap and keeps scans working for a while if attraction-service goes down.
    private const CACHE_SECONDS = 600;

    // Returns ['id', 'name', 'status', ...] or null when the ride does not exist.
    public function getAttraction(int $attractionId): ?array
    {
        $cacheKey = "attraction:{$attractionId}";
        $cachedAttraction = Cache::get($cacheKey);

        if ($cachedAttraction !== null) {
            return $cachedAttraction;
        }

        $response = $this->send(fn () => $this->internalHttp($this->baseUrl(), retries: 1)->get("/api/attractions/{$attractionId}"));

        if ($response->notFound()) {
            return null;
        }

        $attraction = $response->json();
        Cache::put($cacheKey, $attraction, self::CACHE_SECONDS);

        return $attraction;
    }

    // Active rides, used by the simulator.
    public function listActiveAttractions(): array
    {
        return $this->send(fn () => $this->internalHttp($this->baseUrl(), retries: 1)->get('/api/attractions'))->json();
    }

    // Turns "no answer" and 5xx into one exception that callers can catch.
    private function send(callable $request)
    {
        try {
            $response = $request();
        } catch (ConnectionException) {
            throw new AttractionServiceUnavailableException('attraction-service is not reachable');
        }

        if ($response->serverError()) {
            throw new AttractionServiceUnavailableException("attraction-service answered HTTP {$response->status()}");
        }

        return $response;
    }

    private function baseUrl(): string
    {
        return config('wonderland.services.attraction_url');
    }
}
