<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateTierRequest;
use App\Http\Requests\ListTiersRequest;
use App\Http\Requests\UpdateTierRequest;
use App\Http\Resources\TicketTierResource;
use App\Services\TicketTierService;
use App\Traits\ApiResponse;
use App\Traits\ReadsAuthUser;
use Illuminate\Http\JsonResponse;

// Handles ticket tier requests. Business rules are in TicketTierService.
class TicketTierController extends Controller
{
    use ApiResponse, ReadsAuthUser;

    public function __construct(private TicketTierService $ticketTierService) {}

    // Public list. Only admins can see inactive tiers with include_inactive=1.
    public function index(ListTiersRequest $request): JsonResponse
    {
        $includeInactive = $request->boolean('include_inactive') && $this->currentUserIsAdmin();

        $tiers = $this->ticketTierService->listTiers($includeInactive);

        return $this->successResponse(TicketTierResource::collection($tiers));
    }

    public function show(int $id): JsonResponse
    {
        $tier = $this->ticketTierService->getTier($id);

        return $this->successResponse(new TicketTierResource($tier));
    }

    public function store(CreateTierRequest $request): JsonResponse
    {
        $tier = $this->ticketTierService->createTier($request->validated());

        return $this->successResponse(new TicketTierResource($tier), 201);
    }

    public function update(UpdateTierRequest $request, int $id): JsonResponse
    {
        $tier = $this->ticketTierService->updateTier($id, $request->validated());

        return $this->successResponse(new TicketTierResource($tier));
    }
}
