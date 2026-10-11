<?php

namespace App\Traits;

use Carbon\CarbonImmutable;
use DateTimeInterface;

// The database keeps times in UTC, but tickets, check-ins and reports use the park's day (Asia/Colombo).
// These helpers keep that change in one place.
trait UsesParkTime
{
    // Today's date in the park, for example "2026-11-08".
    protected function parkToday(): string
    {
        return CarbonImmutable::now(config('wonderland.park_timezone'))->toDateString();
    }

    // "10:32" in park time, for messages shown to staff.
    protected function parkClockText(DateTimeInterface $moment): string
    {
        return CarbonImmutable::instance($moment)->setTimezone(config('wonderland.park_timezone'))->format('H:i');
    }

    // "Sun 8 Nov" for a park date like "2026-11-08".
    protected function parkDayText(string $parkDate): string
    {
        return CarbonImmutable::parse($parkDate)->format('D j M');
    }

    // Start and end of park days as UTC "Y-m-d H:i:s" strings, for queries on occurred_at.
    protected function parkDaysToUtcRange(string $fromParkDate, string $toParkDate): array
    {
        $timezone = config('wonderland.park_timezone');

        return [
            CarbonImmutable::parse($fromParkDate, $timezone)->startOfDay()->utc()->format('Y-m-d H:i:s'),
            CarbonImmutable::parse($toParkDate, $timezone)->endOfDay()->utc()->format('Y-m-d H:i:s'),
        ];
    }
}
