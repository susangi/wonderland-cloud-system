<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RecordPurchaseRequest;
use App\Services\PurchaseEventService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

// Internal endpoint for booking-service's outbox.
class PurchaseEventController extends Controller
{
    use ApiResponse;

    public function __construct(private PurchaseEventService $purchaseEventService) {}

    // 201 for a new purchase, 200 for a duplicate. Both are success, so the outbox marks the row as sent.
    public function store(RecordPurchaseRequest $request): JsonResponse
    {
        $isNew = $this->purchaseEventService->recordPurchase($request->validated());

        return $this->successResponse([
            'booking_reference' => $request->validated('booking_reference'),
            'status' => $isNew ? 'recorded' : 'duplicate',
        ], $isNew ? 201 : 200);
    }
}
