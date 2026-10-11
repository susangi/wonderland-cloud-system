<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AnalyticsSummaryRequest;
use App\Services\AnalyticsService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

// Numbers for the admin monitoring view.
class AnalyticsController extends Controller
{
    use ApiResponse;

    public function __construct(private AnalyticsService $analyticsService) {}

    public function summary(AnalyticsSummaryRequest $request): JsonResponse
    {
        $summary = $this->analyticsService->summary($request->validated('from'), $request->validated('to'));

        return $this->successResponse($summary);
    }
}
