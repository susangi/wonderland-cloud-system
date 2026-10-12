<?php

namespace App\Services;

use App\Repositories\BookingRepository;
use App\Repositories\TimeSlotRepository;
use Illuminate\Database\Eloquent\Collection;

// Time slots with the free places left for one day.
class TimeSlotService
{
    public function __construct(
        private TimeSlotRepository $timeSlotRepository,
        private BookingRepository $bookingRepository,
    ) {}

    // Each slot gets a "remaining" value, so the frontend can disable full slots (FR-V-09).
    public function listForDate(string $visitDate): Collection
    {
        $slots = $this->timeSlotRepository->listActive();
        $bookedGuestsPerSlot = $this->bookingRepository->bookedGuestsPerSlot($visitDate);

        foreach ($slots as $slot) {
            $bookedGuests = $bookedGuestsPerSlot[$slot->id] ?? 0;
            $slot->remaining = max(0, $slot->capacity - $bookedGuests);
        }

        return $slots;
    }
}
