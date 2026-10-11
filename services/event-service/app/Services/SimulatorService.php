<?php

namespace App\Services;

use App\Clients\AttractionClient;
use App\Models\ParkEvent;
use App\Models\Ticket;
use App\Repositories\ParkEventRepository;
use App\Repositories\TicketRepository;
use App\Traits\UsesParkTime;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use Throwable;

// Creates realistic SAMPLE data so the dashboards are meaningful (FR-M-08, design 12.2).
// Every row is marked source = 'simulated' and every reference starts with "SIM-",
// so sample data is always honest and never mixed up with real visitors.
class SimulatorService
{
    use UsesParkTime;

    // Tier mix from design 12.2, with the brief's guest limits and prices.
    private const TIERS = [
        'INDIVIDUAL' => ['weight' => 25, 'min_guests' => 1, 'max_guests' => 1, 'price' => 40],
        'COUPLE' => ['weight' => 25, 'min_guests' => 2, 'max_guests' => 2, 'price' => 75],
        'FAMILY' => ['weight' => 35, 'min_guests' => 1, 'max_guests' => 4, 'price' => 140],
        'GROUP' => ['weight' => 15, 'min_guests' => 1, 'max_guests' => 5, 'price' => 165],
    ];

    // Check-ins peak 09:00 to 11:00. Ride use peaks 11:00 to 16:00.
    private const CHECKIN_HOUR_WEIGHTS = [9 => 30, 10 => 28, 11 => 16, 12 => 8, 13 => 6, 14 => 5, 15 => 4, 16 => 2, 18 => 1];
    private const RIDE_HOUR_WEIGHTS = [10 => 5, 11 => 12, 12 => 15, 13 => 16, 14 => 16, 15 => 14, 16 => 10, 17 => 6, 18 => 4, 19 => 2];

    // Popular rides get more scans. Rides not in this list get weight 5.
    private const RIDE_POPULARITY = [
        'Thunder Coaster' => 20, 'Splash Mountain' => 18, 'Dragon Loop' => 15, 'Pirate Ship' => 12,
        'Bumper Cars' => 12, 'Laser Maze' => 9, 'Lazy River' => 8, 'Teacup Twirl' => 6,
    ];

    // Used only if attraction-service is not running when the simulator starts.
    private const FALLBACK_RIDES = [
        ['id' => 1, 'name' => 'Thunder Coaster'], ['id' => 2, 'name' => 'Dragon Loop'], ['id' => 3, 'name' => 'Splash Mountain'],
        ['id' => 4, 'name' => 'Lazy River'], ['id' => 5, 'name' => 'Teacup Twirl'], ['id' => 6, 'name' => 'Laser Maze'],
        ['id' => 7, 'name' => 'Pirate Ship'], ['id' => 8, 'name' => 'Bumper Cars'],
    ];

    private const SLOT_LABELS = ['Morning 09:00–12:00', 'Midday 12:00–15:00', 'Afternoon 15:00–18:00', 'Evening 18:00–21:00'];

    public function __construct(
        private ParkEventRepository $parkEventRepository,
        private TicketRepository $ticketRepository,
        private AttractionClient $attractionClient,
    ) {}

    // simulate:history – purchases, check-ins and ride uses for the past $days park days (today not included).
    // Writes straight into park_events. Returns counts per event type.
    public function generateHistory(int $days): array
    {
        $rides = $this->activeRides();
        $rows = [];

        for ($daysAgo = $days; $daysAgo >= 1; $daysAgo--) {
            $parkDay = CarbonImmutable::parse($this->parkToday())->subDays($daysAgo);
            // Weekends are busier than weekdays.
            $ticketCount = $parkDay->isWeekend() ? random_int(150, 220) : random_int(70, 120);

            for ($ticketNumber = 1; $ticketNumber <= $ticketCount; $ticketNumber++) {
                array_push($rows, ...$this->sampleVisitRows($parkDay, $rides));
            }
        }

        $this->parkEventRepository->insertMany($rows);

        return array_count_values(array_column($rows, 'event_type'));
    }

    // simulate:tickets-today – checked in sample tickets for today, needed by simulate:burst.
    public function createCheckedInTicketsForToday(int $count): int
    {
        $parkToday = CarbonImmutable::parse($this->parkToday());
        $rows = [];

        for ($ticketNumber = 1; $ticketNumber <= $count; $ticketNumber++) {
            $tierCode = $this->weightedPick(array_map(fn ($tier) => $tier['weight'], self::TIERS));
            $tier = self::TIERS[$tierCode];
            $guests = random_int($tier['min_guests'], $tier['max_guests']);
            $reference = $this->sampleReference($parkToday);
            $checkedInAt = now()->subMinutes(random_int(1, 60));

            $this->ticketRepository->create([
                'booking_reference' => $reference,
                'user_id' => 'simulated-visitor',
                'tier_code' => $tierCode,
                'guests' => $guests,
                'visit_date' => $parkToday->toDateString(),
                'time_slot_label' => self::SLOT_LABELS[array_rand(self::SLOT_LABELS)],
                'checked_in_at' => $checkedInAt,
            ]);

            $rows[] = $this->eventRow(ParkEvent::TYPE_TICKET_PURCHASE, $reference, $tierCode, $guests, now()->subDays(random_int(1, 7)), ['amount' => $tier['price']]);
            $rows[] = $this->eventRow(ParkEvent::TYPE_GATE_CHECKIN, $reference, $tierCode, $guests, $checkedInAt);
        }

        $this->parkEventRepository->insertMany($rows);

        return $count;
    }

    // Active rides from attraction-service, or the seeded list if it is not running.
    public function activeRides(): array
    {
        try {
            $rides = $this->attractionClient->listActiveAttractions();

            return empty($rides) ? self::FALLBACK_RIDES : $rides;
        } catch (Throwable) {
            return self::FALLBACK_RIDES;
        }
    }

    // One sample ticket: a purchase, usually a check-in, then a few rides per guest.
    private function sampleVisitRows(CarbonImmutable $parkDay, array $rides): array
    {
        $tierCode = $this->weightedPick(array_map(fn ($tier) => $tier['weight'], self::TIERS));
        $tier = self::TIERS[$tierCode];
        $guests = random_int($tier['min_guests'], $tier['max_guests']);
        $reference = $this->sampleReference($parkDay);

        // Tickets are bought online 0 to 14 days before the visit.
        $purchasedAt = $this->parkMomentToUtc($parkDay->subDays(random_int(0, 14)), random_int(8, 22));
        $rows = [$this->eventRow(ParkEvent::TYPE_TICKET_PURCHASE, $reference, $tierCode, $guests, $purchasedAt, ['amount' => $tier['price']])];

        // About 1 in 10 visitors do not turn up.
        if (random_int(1, 100) > 90) {
            return $rows;
        }

        $checkInHour = $this->weightedPick(self::CHECKIN_HOUR_WEIGHTS);
        $rows[] = $this->eventRow(ParkEvent::TYPE_GATE_CHECKIN, $reference, $tierCode, $guests, $this->parkMomentToUtc($parkDay, $checkInHour));

        $rideCount = $guests * random_int(1, 3);
        $rideWeights = array_map(fn ($ride) => self::RIDE_POPULARITY[$ride['name']] ?? 5, $rides);

        for ($rideNumber = 1; $rideNumber <= $rideCount; $rideNumber++) {
            $ride = $rides[$this->weightedPick($rideWeights)];
            // A ride cannot happen before the gate check-in.
            $rideHour = max($checkInHour, $this->weightedPick(self::RIDE_HOUR_WEIGHTS));
            $rows[] = $this->eventRow(ParkEvent::TYPE_RIDE_USAGE, $reference, $tierCode, $guests, $this->parkMomentToUtc($parkDay, $rideHour), [
                'attraction_id' => $ride['id'],
                'attraction_name' => $ride['name'],
            ]);
        }

        return $rows;
    }

    // A full park_events row for bulk insert (insert() does not fill defaults or timestamps).
    private function eventRow(string $eventType, string $reference, string $tierCode, int $guests, $occurredAt, array $extra = []): array
    {
        return array_merge([
            'event_type' => $eventType,
            'booking_reference' => $reference,
            'user_id' => 'simulated-visitor',
            'tier_code' => $tierCode,
            'amount' => null,
            'guests' => $guests,
            'attraction_id' => null,
            'attraction_name' => null,
            'occurred_at' => CarbonImmutable::instance($occurredAt)->utc()->format('Y-m-d H:i:s'),
            'source' => ParkEvent::SOURCE_SIMULATED,
            'created_at' => now(),
            'updated_at' => now(),
        ], $extra);
    }

    // A random minute inside the given park hour, changed to UTC.
    private function parkMomentToUtc(CarbonImmutable $parkDay, int $parkHour): CarbonImmutable
    {
        return CarbonImmutable::parse($parkDay->toDateString(), config('wonderland.park_timezone'))
            ->setTime($parkHour, random_int(0, 59), random_int(0, 59))
            ->utc();
    }

    private function sampleReference(CarbonImmutable $parkDay): string
    {
        return Ticket::SIMULATED_PREFIX.$parkDay->format('Ymd').'-'.strtoupper(Str::random(6));
    }

    // Picks a key from [key => weight]. Bigger weight = picked more often.
    private function weightedPick(array $weights): int|string
    {
        $randomPoint = random_int(1, array_sum($weights));

        foreach ($weights as $key => $weight) {
            $randomPoint -= $weight;
            if ($randomPoint <= 0) {
                return $key;
            }
        }

        return array_key_first($weights);
    }
}
