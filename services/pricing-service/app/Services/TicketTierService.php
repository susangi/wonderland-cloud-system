<?php

namespace App\Services;

use App\Exceptions\TierNotFoundException;
use App\Models\TicketTier;
use App\Repositories\TicketTierRepository;
use Illuminate\Database\Eloquent\Collection;

// Business rules for ticket tiers and prices.
class TicketTierService
{
    public function __construct(private TicketTierRepository $ticketTierRepository) {}

    public function listTiers(bool $includeInactive): Collection
    {
        return $this->ticketTierRepository->list($includeInactive);
    }

    // Inactive tiers are still returned here. booking-service reads is_active
    // and refuses new bookings for an inactive tier.
    public function getTier(int $tierId): TicketTier
    {
        $tier = $this->ticketTierRepository->findById($tierId);

        if ($tier === null) {
            throw new TierNotFoundException($tierId);
        }

        return $tier;
    }

    public function createTier(array $attributes): TicketTier
    {
        return $this->ticketTierRepository->create($attributes);
    }

    // A price change affects only new bookings. Old bookings keep the price they paid (snapshot in booking-service).
    public function updateTier(int $tierId, array $attributes): TicketTier
    {
        $tier = $this->getTier($tierId);

        return $this->ticketTierRepository->update($tier, $attributes);
    }
}
