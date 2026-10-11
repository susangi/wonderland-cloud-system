<?php

namespace App\Traits;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Throwable;

// Same HTTP settings for every call to another Wonderland service (design 7.1, retry pattern).
// Used by PricingClient, PaymentClient and EventClient.
trait CallsInternalServices
{
    // 3 second timeout. One call plus 2 retries, waiting 200 ms then 400 ms.
    // We retry only connection errors and 5xx. A 4xx answer (bad input) will be the same next time, so no retry.
    protected function internalHttp(string $baseUrl): PendingRequest
    {
        return Http::baseUrl($baseUrl)
            ->acceptJson()
            ->timeout(3)
            ->withHeaders(['X-Internal-Token' => (string) config('wonderland.internal_token')])
            ->retry(3, fn (int $attempt) => $attempt * 200, fn (Throwable $exception) => $this->isWorthRetrying($exception), throw: false);
    }

    private function isWorthRetrying(Throwable $exception): bool
    {
        if ($exception instanceof ConnectionException) {
            return true;
        }

        return $exception instanceof RequestException && $exception->response->serverError();
    }
}
