<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

// Circuit breaker for calls from booking-service to payment-service (design 7.2).
//
// CLOSED    = normal. Calls go through. Failures are counted for 60 seconds.
// OPEN      = payment service failed 5 times, so we stop calling it for 30 seconds.
//             This gives it time to recover and the visitor gets a quick message instead of waiting.
// HALF_OPEN = 30 seconds passed. We let only one trial call through.
//             Success closes the breaker again. Failure opens it for another 30 seconds.
//
// State is kept in the database cache, so every booking-service pod sees the same state.
class CircuitBreaker
{
    public const CLOSED = 'CLOSED';
    public const OPEN = 'OPEN';
    public const HALF_OPEN = 'HALF_OPEN';

    private const MAX_FAILURES = 5;
    private const FAILURE_WINDOW_SECONDS = 60;
    private const OPEN_SECONDS = 30;
    // If the trial call crashes without reporting back, another trial is allowed after this time.
    private const TRIAL_LOCK_SECONDS = 15;

    public function __construct(private string $name) {}

    public function state(): string
    {
        $openedAt = Cache::get($this->key('opened_at'));

        if ($openedAt === null) {
            return self::CLOSED;
        }

        return now()->timestamp - $openedAt < self::OPEN_SECONDS ? self::OPEN : self::HALF_OPEN;
    }

    // Ask before every call. In HALF_OPEN only the pod that gets the lock may try.
    public function allowsCall(): bool
    {
        return match ($this->state()) {
            self::CLOSED => true,
            self::OPEN => false,
            self::HALF_OPEN => Cache::lock($this->key('trial'), self::TRIAL_LOCK_SECONDS)->get(),
        };
    }

    public function recordSuccess(): void
    {
        $previousState = $this->state();

        Cache::forget($this->key('failures'));
        Cache::forget($this->key('opened_at'));
        Cache::lock($this->key('trial'))->forceRelease();

        if ($previousState !== self::CLOSED) {
            $this->logStateChange($previousState, self::CLOSED);
        }
    }

    public function recordFailure(): void
    {
        $currentState = $this->state();

        // The trial call failed, so the service is still sick. Open again.
        if ($currentState === self::HALF_OPEN) {
            $this->open($currentState);

            return;
        }

        // Count failures for 60 seconds. Old failures do not count forever.
        Cache::add($this->key('failures'), 0, self::FAILURE_WINDOW_SECONDS);
        $failures = Cache::increment($this->key('failures'));

        if ($currentState === self::CLOSED && $failures >= self::MAX_FAILURES) {
            $this->open($currentState);
        }
    }

    private function open(string $fromState): void
    {
        Cache::forever($this->key('opened_at'), now()->timestamp);
        Cache::forget($this->key('failures'));
        Cache::lock($this->key('trial'))->forceRelease();

        $this->logStateChange($fromState, self::OPEN);
    }

    // One structured log line per change, so it is easy to find in CloudWatch.
    private function logStateChange(string $fromState, string $toState): void
    {
        Log::warning('circuit_breaker_state_change', [
            'circuit' => $this->name,
            'from' => $fromState,
            'to' => $toState,
        ]);
    }

    private function key(string $suffix): string
    {
        return "cb:{$this->name}:{$suffix}";
    }
}
