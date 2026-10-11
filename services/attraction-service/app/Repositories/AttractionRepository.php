<?php

namespace App\Repositories;

use App\Models\Attraction;
use Illuminate\Database\Eloquent\Collection;

// All database queries for attractions are kept here.
class AttractionRepository
{
    // List attractions with optional category and thrill level filters.
    // Retired ones are left out unless an admin asks for them.
    public function list(array $filters, bool $includeRetired): Collection
    {
        $query = Attraction::query();

        if (! $includeRetired) {
            $query->where('status', Attraction::STATUS_ACTIVE);
        }

        if (! empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (! empty($filters['thrill_level'])) {
            $query->where('thrill_level', $filters['thrill_level']);
        }

        return $query->orderBy('name')->get();
    }

    public function findById(int $attractionId): ?Attraction
    {
        return Attraction::find($attractionId);
    }

    public function create(array $attributes): Attraction
    {
        return Attraction::create($attributes);
    }

    public function update(Attraction $attraction, array $attributes): Attraction
    {
        $attraction->update($attributes);

        return $attraction;
    }

    public function updateStatus(Attraction $attraction, string $status): Attraction
    {
        $attraction->update(['status' => $status]);

        return $attraction;
    }
}
