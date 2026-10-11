<?php

namespace App\Services;

use App\Exceptions\AlreadyCheckedInException;
use App\Exceptions\TicketNotFoundException;
use App\Exceptions\WrongDateException;
use App\Models\ParkEvent;
use App\Models\Ticket;
use App\Repositories\ParkEventRepository;
use App\Repositories\TicketRepository;
use App\Traits\UsesParkTime;
use Illuminate\Support\Facades\DB;

// Gate check-in (design 6.5). Uses only the local tickets table,
// so it keeps working when booking-service is down (NFR-FT-01).
class CheckInService
{
    use UsesParkTime;

    public function __construct(
        private TicketRepository $ticketRepository,
        private ParkEventRepository $parkEventRepository,
    ) {}

    public function checkIn(string $bookingReference): Ticket
    {
        return DB::transaction(function () use ($bookingReference) {
            $ticket = $this->ticketRepository->findForUpdate($bookingReference);

            if ($ticket === null) {
                throw new TicketNotFoundException($bookingReference);
            }

            if ($ticket->visit_date !== $this->parkToday()) {
                throw new WrongDateException($this->parkDayText($ticket->visit_date));
            }

            if ($ticket->checked_in_at !== null) {
                throw new AlreadyCheckedInException($this->parkClockText($ticket->checked_in_at));
            }

            $this->ticketRepository->markCheckedIn($ticket);

            $this->parkEventRepository->create([
                'event_type' => ParkEvent::TYPE_GATE_CHECKIN,
                'booking_reference' => $ticket->booking_reference,
                'user_id' => $ticket->user_id,
                'tier_code' => $ticket->tier_code,
                'guests' => $ticket->guests,
                'occurred_at' => $ticket->checked_in_at,
                'source' => $ticket->isSimulated() ? ParkEvent::SOURCE_SIMULATED : ParkEvent::SOURCE_LIVE,
            ]);

            return $ticket;
        });
    }
}
