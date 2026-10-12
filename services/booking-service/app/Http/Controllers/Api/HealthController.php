<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\HealthService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

// GET /api/health. Kubernetes probes use /up instead, so a short database problem
// does not make Kubernetes remove every pod.
// booking-service also shows the payment circuit breaker state (design 7.2) for the demo.
class HealthController extends Controller
{
    use ApiResponse;

    public function __construct(private HealthService $healthService) {}

    public function show(): JsonResponse
    {
        return $this->successResponse([
            'service' => config('wonderland.service_name'),
            'status' => 'ok',
            'db' => $this->healthService->databaseStatus(),
            'payment_circuit' => $this->healthService->paymentCircuitState(),
        ]);
    }
}
