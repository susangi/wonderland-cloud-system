<?php

namespace Tests\Feature;

use App\Models\ParkEvent;
use App\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalyticsAndSimulatorTest extends TestCase
{
    use EventTestHelpers, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fixClockAndFakeAttractions();
    }

    private function event(string $type, string $occurredAtUtc, array $changes = []): void
    {
        ParkEvent::create(array_merge([
            'event_type' => $type,
            'booking_reference' => 'WL-'.uniqid(),
            'tier_code' => 'FAMILY',
            'guests' => 4,
            'occurred_at' => $occurredAtUtc,
        ], $changes));
    }

    // Small fixture with known answers. UTC times; Sri Lanka is UTC+05:30.
    private function createFixture(): void
    {
        $this->event('TICKET_PURCHASE', '2026-11-01 04:00:00', ['amount' => 140]);
        $this->event('TICKET_PURCHASE', '2026-11-01 05:00:00', ['amount' => 140]);
        $this->event('TICKET_PURCHASE', '2026-11-02 05:00:00', ['tier_code' => 'COUPLE', 'guests' => 2, 'amount' => 75]);
        // 03:45 UTC = 09:15 park time on 7 Nov.
        $this->event('GATE_CHECKIN', '2026-11-07 03:45:00', ['guests' => 4]);
        // 20:00 UTC on 6 Nov = 01:30 park time on 7 Nov, so it must count for 7 Nov, not 6 Nov.
        $this->event('GATE_CHECKIN', '2026-11-06 20:00:00', ['guests' => 2]);
        // 06:00 UTC = 11:30 park time.
        $this->event('RIDE_USAGE', '2026-11-07 06:00:00', ['attraction_id' => 1, 'attraction_name' => 'Thunder Coaster']);
        $this->event('RIDE_USAGE', '2026-11-07 06:10:00', ['attraction_id' => 1, 'attraction_name' => 'Thunder Coaster']);
        $this->event('RIDE_USAGE', '2026-11-07 06:20:00', ['attraction_id' => 3, 'attraction_name' => 'Splash Mountain']);
        // Outside the asked range.
        $this->event('GATE_CHECKIN', '2026-10-01 05:00:00', ['guests' => 99]);
    }

    public function test_analytics_numbers_are_correct_for_a_small_fixture(): void
    {
        $this->createFixture();

        $this->getJson('/api/analytics/summary?from=2026-11-01&to=2026-11-08', $this->adminHeaders())
            ->assertOk()
            ->assertJsonPath('range', ['from' => '2026-11-01', 'to' => '2026-11-08'])
            ->assertJsonPath('visitors_per_day', [['date' => '2026-11-07', 'visitors' => 6]])
            ->assertJsonPath('tickets_by_tier', [
                ['tier_code' => 'FAMILY', 'tickets' => 2, 'revenue' => 280],
                ['tier_code' => 'COUPLE', 'tickets' => 1, 'revenue' => 75],
            ])
            ->assertJsonPath('top_rides', [
                ['attraction_name' => 'Thunder Coaster', 'uses' => 2],
                ['attraction_name' => 'Splash Mountain', 'uses' => 1],
            ])
            ->assertJsonPath('activity_by_hour', [
                ['hour' => 1, 'checkins' => 1, 'ride_uses' => 0],
                ['hour' => 9, 'checkins' => 1, 'ride_uses' => 0],
                ['hour' => 11, 'checkins' => 0, 'ride_uses' => 3],
            ])
            ->assertJsonPath('totals', ['visitors' => 6, 'tickets' => 3, 'revenue' => 355])
            ->assertJsonPath('includes_simulated', false);
    }

    public function test_analytics_default_range_is_last_30_days(): void
    {
        $this->getJson('/api/analytics/summary', $this->adminHeaders())
            ->assertOk()
            ->assertJsonPath('range', ['from' => '2026-10-10', 'to' => '2026-11-08']);
    }

    public function test_analytics_is_admin_only(): void
    {
        $this->getJson('/api/analytics/summary', $this->visitorHeaders())->assertForbidden();
        $this->getJson('/api/analytics/summary')->assertUnauthorized();
    }

    public function test_simulate_history_adds_only_simulated_rows(): void
    {
        $this->artisan('simulate:history', ['--days' => 3])->assertSuccessful();

        $this->assertGreaterThan(0, ParkEvent::where('event_type', 'TICKET_PURCHASE')->count());
        $this->assertGreaterThan(0, ParkEvent::where('event_type', 'GATE_CHECKIN')->count());
        $this->assertGreaterThan(0, ParkEvent::where('event_type', 'RIDE_USAGE')->count());
        $this->assertSame(0, ParkEvent::where('source', '!=', 'simulated')->count());
        $this->assertSame(0, ParkEvent::where('booking_reference', 'not like', 'SIM-%')->count());

        // Today is not filled by history, only past days.
        $this->assertSame(0, ParkEvent::where('event_type', 'GATE_CHECKIN')->where('occurred_at', '>=', '2026-11-07 18:30:00')->count());

        $this->getJson('/api/analytics/summary', $this->adminHeaders())->assertJsonPath('includes_simulated', true);
    }

    public function test_simulate_tickets_today_creates_checked_in_sample_tickets(): void
    {
        $this->artisan('simulate:tickets-today', ['--count' => 5])
            ->expectsOutput('Created 5 checked in sample tickets for today.')
            ->assertSuccessful();

        $this->assertSame(5, Ticket::where('visit_date', '2026-11-08')->whereNotNull('checked_in_at')->where('booking_reference', 'like', 'SIM-%')->count());
        $this->assertSame(5, ParkEvent::where('event_type', 'GATE_CHECKIN')->where('source', 'simulated')->count());
    }

    public function test_ride_scan_of_a_sample_ticket_is_marked_simulated(): void
    {
        $this->createTicket(['booking_reference' => 'SIM-20261108-ABCDEF', 'checked_in_at' => now()]);

        $this->postJson('/api/ride-scans', ['booking_reference' => 'SIM-20261108-ABCDEF', 'attraction_id' => 1], $this->adminHeaders())
            ->assertCreated();

        $this->assertDatabaseHas('park_events', ['event_type' => 'RIDE_USAGE', 'source' => 'simulated']);
    }

    public function test_simulate_burst_sends_ride_scans_through_the_api(): void
    {
        $this->artisan('simulate:tickets-today', ['--count' => 3]);

        $this->artisan('simulate:burst', ['--count' => 20, '--concurrency' => 5, '--base-url' => 'http://burst.test'])
            ->expectsOutput('Success: 20, failed: 0.')
            ->assertSuccessful();
    }

    public function test_simulate_burst_needs_sample_tickets(): void
    {
        $this->artisan('simulate:burst', ['--count' => 5])->assertFailed();
    }
}
