<?php

namespace App\Services;

use App\Clients\AttractionClient;
use App\Exceptions\AttractionNotAvailableException;
use App\Exceptions\AttractionServiceUnavailableException;
use App\Exceptions\NotCheckedInException;
use App\Models\ParkEvent;
use App\Models\Ticket;
use App\Repositories\ParkEventRepository;
use App\Repositories\TicketRepository;
use App\Traits\UsesParkTime;
use Illuminate\Support\Facades\Log;

// Records ride usage (design 6.5). Kept cheap (one lookup + one insert) because rides send bursts of scans.
class RideScanService
{
    use UsesParkTime;

    public function __construct(
        private TicketRepository $ticketRepository,
        private ParkEventRepository $parkEventRepository,
        private AttractionClient $attractionClient,
    ) {}

    // Returns ['ticket' => Ticket, 'event' => ParkEvent, 'warning' => string|null].
    public function recordRideScan(string $bookingReference, int $attractionId): array
    {
        $ticket = $this->ticketRepository->findByReference($bookingReference);

        if (! $this->isCheckedInToday($ticket)) {
            throw new NotCheckedInException;
        }

        [$attractionName, $warning] = $this->attractionNameFor($attractionId);

        $event = $this->parkEventRepository->create([
            'event_type' => ParkEvent::TYPE_RIDE_USAGE,
            'booking_reference' => $ticket->booking_reference,
            'user_id' => $ticket->user_id,
            'tier_code' => $ticket->tier_code,
            'guests' => $ticket->guests,
            'attraction_id' => $attractionId,
            'attraction_name' => $attractionName,
            'occurred_at' => now(),
            'source' => $ticket->isSimulated() ? ParkEvent::SOURCE_SIMULATED : ParkEvent::SOURCE_LIVE,
        ]);

        return ['ticket' => $ticket, 'event' => $event, 'warning' => $warning];
    }

    private function isCheckedInToday(?Ticket $ticket): bool
    {
        return $ticket !== null
            && $ticket->checked_in_at !== null
            && $ticket->visit_date === $this->parkToday();
    }

    // Returns [name, warning]. Graceful degradation: if attraction-service is down and the name is not
    // in the cache, we still save the scan as "Unknown (id N)" instead of losing it.
    private function attractionNameFor(int $attractionId): array
    {
        try {
            $attraction = $this->attractionClient->getAttraction($attractionId);
        } catch (AttractionServiceUnavailableException $exception) {
            Log::warning('ride_scan_degraded', ['attraction_id' => $attractionId, 'error' => $exception->getMessage()]);

            return ["Unknown (id {$attractionId})", 'Attraction service is not available. The ride was saved without its name.'];
        }

        if ($attraction === null || $attraction['status'] !== 'active') {
            throw new AttractionNotAvailableException($attractionId);
        }

        return [$attraction['name'], null];
    }
}
