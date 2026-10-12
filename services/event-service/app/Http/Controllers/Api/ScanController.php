<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CheckInRequest;
use App\Http\Requests\RideScanRequest;
use App\Http\Resources\CheckInResource;
use App\Services\CheckInService;
use App\Services\RideScanService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

// Gate check-in and ride scan for park staff (admins). Rules are in CheckInService and RideScanService.
class ScanController extends Controller
{
    use ApiResponse;

    public function __construct(
        private CheckInService $checkInService,
        private RideScanService $rideScanService,
    ) {}

    public function checkIn(CheckInRequest $request): JsonResponse
    {
        $ticket = $this->checkInService->checkIn($request->validated('booking_reference'));

        return $this->successResponse(new CheckInResource($ticket));
    }

    public function rideScan(RideScanRequest $request): JsonResponse
    {
        $result = $this->rideScanService->recordRideScan(
            $request->validated('booking_reference'),
            $request->validated('attraction_id'),
        );

        return $this->successResponse([
            'booking_reference' => $result['ticket']->booking_reference,
            'tier_code' => $result['ticket']->tier_code,
            'guests' => $result['ticket']->guests,
            'attraction_id' => $result['event']->attraction_id,
            'attraction_name' => $result['event']->attraction_name,
            'occurred_at' => $result['event']->occurred_at->toIso8601String(),
            // Only set when attraction-service was down (graceful degradation).
            'warning' => $result['warning'],
        ], 201);
    }
}
