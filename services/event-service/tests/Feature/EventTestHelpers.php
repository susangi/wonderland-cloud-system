<?php

namespace Tests\Feature;

use App\Models\Ticket;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;

// Shared helpers for event-service tests.
// The clock is fixed at 2026-11-08 05:00 UTC = 10:30 in Sri Lanka (a Sunday).
trait EventTestHelpers
{
    protected bool $attractionServiceIsUp = true;

    protected function fixClockAndFakeAttractions(): void
    {
        $this->travelTo('2026-11-08 05:00:00');
        Sleep::fake();

        Http::fake(function (Request $request) {
            if (! str_contains($request->url(), 'attraction.test')) {
                return Http::response(['status' => 'recorded'], 201);
            }

            if (! $this->attractionServiceIsUp) {
                return Http::response('down', 503);
            }

            return match (parse_url($request->url(), PHP_URL_PATH)) {
                '/api/attractions/1' => Http::response(['id' => 1, 'name' => 'Thunder Coaster', 'status' => 'active']),
                '/api/attractions/7' => Http::response(['id' => 7, 'name' => 'Pirate Ship', 'status' => 'retired']),
                '/api/attractions' => Http::response([['id' => 1, 'name' => 'Thunder Coaster'], ['id' => 3, 'name' => 'Splash Mountain']]),
                default => Http::response(['error' => ['code' => 'ATTRACTION_NOT_FOUND']], 404),
            };
        });
    }

    protected function purchasePayload(array $changes = []): array
    {
        return array_merge([
            'booking_reference' => 'WL-20261108-AB12CD',
            'user_id' => 'local-visitor-1',
            'tier_code' => 'FAMILY',
            'guests' => 4,
            'amount' => 140,
            'currency' => 'USD',
            'visit_date' => '2026-11-08',
            'time_slot_label' => 'Morning 09:00–12:00',
            'occurred_at' => '2026-11-01T08:00:00+00:00',
        ], $changes);
    }

    protected function createTicket(array $changes = []): Ticket
    {
        return Ticket::create(array_merge([
            'booking_reference' => 'WL-20261108-AB12CD',
            'user_id' => 'local-visitor-1',
            'tier_code' => 'FAMILY',
            'guests' => 4,
            'visit_date' => '2026-11-08',
            'time_slot_label' => 'Morning 09:00–12:00',
        ], $changes));
    }

    protected function internalHeaders(): array
    {
        return ['X-Internal-Token' => 'test-internal-token'];
    }
}
