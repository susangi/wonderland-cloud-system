<?php

namespace App\Services;

use App\Support\CircuitBreaker;
use Illuminate\Support\Facades\DB;
use Throwable;

// Checks used by GET /api/health (for diagnostics and the demo, not for Kubernetes probes).
class HealthService
{
    // Try to open a database connection. Returns "ok" or "down".
    public function databaseStatus(): string
    {
        try {
            DB::connection()->getPdo();

            return 'ok';
        } catch (Throwable) {
            return 'down';
        }
    }

    // CLOSED, OPEN or HALF_OPEN. Shown in the viva to prove the breaker opened.
    public function paymentCircuitState(): string
    {
        try {
            return (new CircuitBreaker('payment'))->state();
        } catch (Throwable) {
            return 'UNKNOWN';
        }
    }
}
