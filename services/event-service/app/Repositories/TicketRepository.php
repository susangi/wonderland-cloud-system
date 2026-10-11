<?php

namespace App\Repositories;

use App\Models\Ticket;
use Illuminate\Database\Eloquent\Collection;

// All database queries for the local ticket copy are kept here.
class TicketRepository
{
    public function exists(string $bookingReference): bool
    {
        return Ticket::whereKey($bookingReference)->exists();
    }

    public function findByReference(string $bookingReference): ?Ticket
    {
        return Ticket::find($bookingReference);
    }

    // SELECT ... FOR UPDATE. Must be called inside a transaction.
    // If two gates scan the same ticket at the same moment, the second one waits here
    // and then sees the first check-in, so the ticket is not checked in twice.
    public function findForUpdate(string $bookingReference): ?Ticket
    {
        return Ticket::whereKey($bookingReference)->lockForUpdate()->first();
    }

    public function create(array $attributes): Ticket
    {
        return Ticket::create($attributes);
    }

    public function markCheckedIn(Ticket $ticket): Ticket
    {
        $ticket->update(['checked_in_at' => now()]);

        return $ticket;
    }

    // Simulated tickets for one day that are already checked in (used by simulate:burst).
    public function listSimulatedCheckedIn(string $visitDate): Collection
    {
        return Ticket::where('visit_date', $visitDate)
            ->where('booking_reference', 'like', Ticket::SIMULATED_PREFIX.'%')
            ->whereNotNull('checked_in_at')
            ->get();
    }
}
