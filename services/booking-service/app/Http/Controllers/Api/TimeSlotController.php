<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ListTimeSlotsRequest;
use App\Http\Resources\TimeSlotResource;
use App\Services\TimeSlotService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

// Public list of time slots with free places for a date.
class TimeSlotController extends Controller
{
    use ApiResponse;

    public function __construct(private TimeSlotService $timeSlotService) {}

    public function index(ListTimeSlotsRequest $request): JsonResponse
    {
        $slots = $this->timeSlotService->listForDate($request->validated('date'));

        return $this->successResponse(TimeSlotResource::collection($slots));
    }
}
