<?php

namespace App\Repositories;

use App\Models\ParkEvent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

// All database queries for park events, including the analytics numbers (design 6.5 and 12.1).
// $fromUtc and $toUtc are the start and end of the asked park days, already changed to UTC.
class ParkEventRepository
{
    private const INSERT_CHUNK_SIZE = 500;

    public function create(array $attributes): ParkEvent
    {
        return ParkEvent::create($attributes);
    }

    // Fast bulk insert for the simulator. Rows must already have every column set.
    public function insertMany(array $rows): void
    {
        foreach (array_chunk($rows, self::INSERT_CHUNK_SIZE) as $chunk) {
            ParkEvent::insert($chunk);
        }
    }

    // Gate check-ins and ride uses of one visitor, newest first.
    public function listVisitsForUser(string $userId): Collection
    {
        return ParkEvent::where('user_id', $userId)
            ->whereIn('event_type', [ParkEvent::TYPE_GATE_CHECKIN, ParkEvent::TYPE_RIDE_USAGE])
            ->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->get();
    }

    // Q1: visitors per park day. Visitors = guests on gate check-ins (one check-in per booking, many people).
    public function visitorsPerDay(string $fromUtc, string $toUtc): array
    {
        $dayExpression = $this->parkDayExpression();

        return $this->inRange(ParkEvent::TYPE_GATE_CHECKIN, $fromUtc, $toUtc)
            ->selectRaw("{$dayExpression} as day, SUM(guests) as visitors")
            ->groupByRaw($dayExpression)
            ->orderBy('day')
            ->get()
            ->map(fn ($row) => ['date' => $row->day, 'visitors' => (int) $row->visitors])
            ->all();
    }

    // Q2: tickets and revenue by tier.
    public function ticketsByTier(string $fromUtc, string $toUtc): array
    {
        return $this->inRange(ParkEvent::TYPE_TICKET_PURCHASE, $fromUtc, $toUtc)
            ->selectRaw('tier_code, COUNT(*) as tickets, SUM(amount) as revenue')
            ->groupBy('tier_code')
            ->orderByDesc('revenue')
            ->get()
            ->map(fn ($row) => ['tier_code' => $row->tier_code, 'tickets' => (int) $row->tickets, 'revenue' => round((float) $row->revenue, 2)])
            ->all();
    }

    // Q3: most used rides.
    public function topRides(string $fromUtc, string $toUtc, int $limit = 10): array
    {
        return $this->inRange(ParkEvent::TYPE_RIDE_USAGE, $fromUtc, $toUtc)
            ->selectRaw('attraction_name, COUNT(*) as uses')
            ->groupBy('attraction_name')
            ->orderByDesc('uses')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => ['attraction_name' => $row->attraction_name, 'uses' => (int) $row->uses])
            ->all();
    }

    // Q4: check-ins and ride uses per hour of the park day, to find peak and quiet hours.
    public function activityByHour(string $fromUtc, string $toUtc): array
    {
        $hourExpression = $this->parkHourExpression();

        return ParkEvent::query()
            ->whereIn('event_type', [ParkEvent::TYPE_GATE_CHECKIN, ParkEvent::TYPE_RIDE_USAGE])
            ->whereBetween('occurred_at', [$fromUtc, $toUtc])
            ->selectRaw("{$hourExpression} as hour_of_day")
            ->selectRaw("SUM(CASE WHEN event_type = 'GATE_CHECKIN' THEN 1 ELSE 0 END) as checkins")
            ->selectRaw("SUM(CASE WHEN event_type = 'RIDE_USAGE' THEN 1 ELSE 0 END) as ride_uses")
            ->groupByRaw($hourExpression)
            ->orderBy('hour_of_day')
            ->get()
            ->map(fn ($row) => ['hour' => (int) $row->hour_of_day, 'checkins' => (int) $row->checkins, 'ride_uses' => (int) $row->ride_uses])
            ->all();
    }

    public function totals(string $fromUtc, string $toUtc): array
    {
        $purchases = $this->inRange(ParkEvent::TYPE_TICKET_PURCHASE, $fromUtc, $toUtc);

        return [
            'visitors' => (int) $this->inRange(ParkEvent::TYPE_GATE_CHECKIN, $fromUtc, $toUtc)->sum('guests'),
            'tickets' => (clone $purchases)->count(),
            'revenue' => round((float) $purchases->sum('amount'), 2),
        ];
    }

    public function hasSimulatedRows(string $fromUtc, string $toUtc): bool
    {
        return ParkEvent::whereBetween('occurred_at', [$fromUtc, $toUtc])
            ->where('source', ParkEvent::SOURCE_SIMULATED)
            ->exists();
    }

    private function inRange(string $eventType, string $fromUtc, string $toUtc): Builder
    {
        return ParkEvent::where('event_type', $eventType)->whereBetween('occurred_at', [$fromUtc, $toUtc]);
    }

    // Times are saved in UTC but the park runs on Sri Lanka time (UTC+05:30).
    // MySQL and SQLite (used only by tests) write this change differently.
    private function parkTimeExpression(): string
    {
        return DB::connection()->getDriverName() === 'sqlite'
            ? "datetime(occurred_at, '+5 hours', '+30 minutes')"
            : "CONVERT_TZ(occurred_at, '+00:00', '+05:30')";
    }

    private function parkDayExpression(): string
    {
        return 'DATE('.$this->parkTimeExpression().')';
    }

    private function parkHourExpression(): string
    {
        return DB::connection()->getDriverName() === 'sqlite'
            ? 'CAST(strftime(\'%H\', '.$this->parkTimeExpression().') AS INTEGER)'
            : 'HOUR('.$this->parkTimeExpression().')';
    }
}
