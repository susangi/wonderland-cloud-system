<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ListAdminBookingsRequest;
use App\Http\Resources\BookingResource;
use App\Services\BookingService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

// Admin view of all bookings for one visit date.
class AdminBookingController extends Controller
{
    use ApiResponse;

    public function __construct(private BookingService $bookingService) {}

    public function index(ListAdminBookingsRequest $request): JsonResponse
    {
        $bookings = $this->bookingService->listBookingsForDate($request->visitDate());

        return $this->successResponse(BookingResource::collection($bookings));
    }
}
