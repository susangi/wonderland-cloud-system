<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateAttractionRequest;
use App\Http\Requests\ListAttractionsRequest;
use App\Http\Requests\UpdateAttractionRequest;
use App\Http\Resources\AttractionResource;
use App\Services\AttractionService;
use App\Traits\ApiResponse;
use App\Traits\ReadsAuthUser;
use Illuminate\Http\JsonResponse;

// Handles attraction requests. Business rules are in AttractionService.
class AttractionController extends Controller
{
    use ApiResponse, ReadsAuthUser;

    public function __construct(private AttractionService $attractionService) {}

    // Public list. Only admins can see retired rides with include_retired=1.
    public function index(ListAttractionsRequest $request): JsonResponse
    {
        $includeRetired = $request->boolean('include_retired') && $this->currentUserIsAdmin();

        $attractions = $this->attractionService->listAttractions($request->validated(), $includeRetired);

        return $this->successResponse(AttractionResource::collection($attractions));
    }

    public function show(int $id): JsonResponse
    {
        $attraction = $this->attractionService->getAttraction($id);

        return $this->successResponse(new AttractionResource($attraction));
    }

    public function store(CreateAttractionRequest $request): JsonResponse
    {
        $attraction = $this->attractionService->createAttraction($request->validated());

        return $this->successResponse(new AttractionResource($attraction), 201);
    }

    public function update(UpdateAttractionRequest $request, int $id): JsonResponse
    {
        $attraction = $this->attractionService->updateAttraction($id, $request->validated());

        return $this->successResponse(new AttractionResource($attraction));
    }

    public function retire(int $id): JsonResponse
    {
        $attraction = $this->attractionService->retireAttraction($id);

        return $this->successResponse(new AttractionResource($attraction));
    }

    public function activate(int $id): JsonResponse
    {
        $attraction = $this->attractionService->activateAttraction($id);

        return $this->successResponse(new AttractionResource($attraction));
    }
}
