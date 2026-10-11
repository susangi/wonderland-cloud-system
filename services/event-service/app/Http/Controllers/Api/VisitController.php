<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\VisitResource;
use App\Services\VisitService;
use App\Traits\ApiResponse;
use App\Traits\ReadsAuthUser;
use Illuminate\Http\JsonResponse;

// Visit history of the logged in visitor (check-ins and rides used), newest first.
class VisitController extends Controller
{
    use ApiResponse, ReadsAuthUser;

    public function __construct(private VisitService $visitService) {}

    public function mine(): JsonResponse
    {
        $visits = $this->visitService->listVisitsForUser($this->currentUserId());

        return $this->successResponse(VisitResource::collection($visits));
    }
}
