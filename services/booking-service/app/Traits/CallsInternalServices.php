<?php

namespace App\Traits;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Throwable;

// Same HTTP settings for every call to another Wonderland service (design 7.1, retry pattern).
trait CallsInternalServices
{
    // 3 second timeout. One call plus $retries retries, waiting 200 ms, 400 ms ...
    // We retry only connection errors and 5xx. A 4xx answer (bad input) will be the same next time, so no retry.
    protected function internalHttp(string $baseUrl, int $retries = 2): PendingRequest
    {
        return Http::baseUrl($baseUrl)
            ->acceptJson()
            ->timeout(3)
            ->withHeaders(['X-Internal-Token' => (string) config('wonderland.internal_token')])
            ->retry($retries + 1, fn (int $attempt) => $attempt * 200, fn (Throwable $exception) => $this->isWorthRetrying($exception), throw: false);
    }

    private function isWorthRetrying(Throwable $exception): bool
    {
        if ($exception instanceof ConnectionException) {
            return true;
        }

        return $exception instanceof RequestException && $exception->response->serverError();
    }
}
