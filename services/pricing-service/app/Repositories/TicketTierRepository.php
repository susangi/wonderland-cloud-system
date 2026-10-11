<?php

namespace App\Repositories;

use App\Models\TicketTier;
use Illuminate\Database\Eloquent\Collection;

// All database queries for ticket tiers are kept here.
class TicketTierRepository
{
    // Active tiers only, unless an admin asks for inactive ones too. Cheapest first.
    public function list(bool $includeInactive): Collection
    {
        $query = TicketTier::query();

        if (! $includeInactive) {
            $query->where('is_active', true);
        }

        return $query->orderBy('price')->get();
    }

    public function findById(int $tierId): ?TicketTier
    {
        return TicketTier::find($tierId);
    }

    public function create(array $attributes): TicketTier
    {
        return TicketTier::create($attributes);
    }

    public function update(TicketTier $tier, array $attributes): TicketTier
    {
        $tier->update($attributes);

        return $tier;
    }
}
