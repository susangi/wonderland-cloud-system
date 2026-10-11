<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateBookingRequest;
use App\Http\Requests\PayBookingRequest;
use App\Http\Resources\BookingResource;
use App\Services\BookingService;
use App\Services\PaymentFlowService;
use App\Traits\ApiResponse;
use App\Traits\ReadsAuthUser;
use Illuminate\Http\JsonResponse;

// Handles booking requests from visitors. Business rules are in BookingService and PaymentFlowService.
class BookingController extends Controller
{
    use ApiResponse, ReadsAuthUser;

    public function __construct(
        private BookingService $bookingService,
        private PaymentFlowService $paymentFlowService,
    ) {}

    // Create a new booking. It stays as PENDING_PAYMENT until the visitor pays.
    public function store(CreateBookingRequest $request): JsonResponse
    {
        $booking = $this->bookingService->createBooking($this->currentUserId(), $request->validated());

        return $this->successResponse(new BookingResource($booking), 201);
    }

    public function pay(PayBookingRequest $request, string $reference): JsonResponse
    {
        $booking = $this->paymentFlowService->payBooking($this->currentUserId(), $reference, $request->validated());

        return $this->successResponse(new BookingResource($booking));
    }

    // Booking history of the logged in visitor.
    public function mine(): JsonResponse
    {
        $bookings = $this->bookingService->listBookingsForUser($this->currentUserId());

        return $this->successResponse(BookingResource::collection($bookings));
    }

    public function show(string $reference): JsonResponse
    {
        $booking = $this->bookingService->getBookingForViewer($reference, $this->currentUserId(), $this->currentUserIsAdmin());

        return $this->successResponse(new BookingResource($booking));
    }
}
