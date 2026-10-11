<?php

namespace App\Services;

use App\Exceptions\AttractionNotFoundException;
use App\Models\Attraction;
use App\Repositories\AttractionRepository;
use Illuminate\Database\Eloquent\Collection;

// Business rules for rides and activities.
class AttractionService
{
    public function __construct(private AttractionRepository $attractionRepository) {}

    public function listAttractions(array $filters, bool $includeRetired): Collection
    {
        return $this->attractionRepository->list($filters, $includeRetired);
    }

    // Retired attractions are still returned here, because event-service looks them up
    // by id for ride scans and checks the status itself.
    public function getAttraction(int $attractionId): Attraction
    {
        $attraction = $this->attractionRepository->findById($attractionId);

        if ($attraction === null) {
            throw new AttractionNotFoundException($attractionId);
        }

        return $attraction;
    }

    public function createAttraction(array $attributes): Attraction
    {
        return $this->attractionRepository->create($attributes);
    }

    public function updateAttraction(int $attractionId, array $attributes): Attraction
    {
        $attraction = $this->getAttraction($attractionId);

        return $this->attractionRepository->update($attraction, $attributes);
    }

    // We never delete. Retired rides are hidden from visitors but stay for history and analytics.
    public function retireAttraction(int $attractionId): Attraction
    {
        $attraction = $this->getAttraction($attractionId);

        return $this->attractionRepository->updateStatus($attraction, Attraction::STATUS_RETIRED);
    }

    // Bring a retired ride back, for example after maintenance.
    public function activateAttraction(int $attractionId): Attraction
    {
        $attraction = $this->getAttraction($attractionId);

        return $this->attractionRepository->updateStatus($attraction, Attraction::STATUS_ACTIVE);
    }
}
