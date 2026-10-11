<?php

namespace App\Services;

use App\Repositories\ParkEventRepository;
use Illuminate\Database\Eloquent\Collection;

// Visit history for the "My visits" page (FR-V-14).
class VisitService
{
    public function __construct(private ParkEventRepository $parkEventRepository) {}

    public function listVisitsForUser(string $userId): Collection
    {
        return $this->parkEventRepository->listVisitsForUser($userId);
    }
}
