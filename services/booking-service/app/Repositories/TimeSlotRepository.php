<?php

namespace App\Repositories;

use App\Models\TimeSlot;
use Illuminate\Database\Eloquent\Collection;

// All database queries for time slots are kept here.
class TimeSlotRepository
{
    public function listActive(): Collection
    {
        return TimeSlot::where('is_active', true)->orderBy('start_time')->get();
    }

    // SELECT ... FOR UPDATE. Must be called inside a transaction.
    // Other bookings for the same slot wait here until we finish, so two people cannot take the last seats.
    public function findForUpdate(int $timeSlotId): TimeSlot
    {
        return TimeSlot::whereKey($timeSlotId)->lockForUpdate()->firstOrFail();
    }
}
