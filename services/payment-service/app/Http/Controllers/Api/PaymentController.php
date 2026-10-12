<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProcessPaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Services\PaymentService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

// Handles payment requests. Business rules are in PaymentService.
class PaymentController extends Controller
{
    use ApiResponse;

    public function __construct(private PaymentService $paymentService) {}

    // Internal: booking-service sends the card here. A declined card is still a normal answer (201 with status FAILED),
    // so booking-service does not retry it. Only 5xx and connection errors are retried.
    public function store(ProcessPaymentRequest $request): JsonResponse
    {
        $payment = $this->paymentService->processPayment($request->validated());

        return $this->successResponse(new PaymentResource($payment), 201);
    }

    // Admin: look up one payment.
    public function show(string $paymentId): JsonResponse
    {
        $payment = $this->paymentService->getPayment($paymentId);

        return $this->successResponse(new PaymentResource($payment));
    }
}
