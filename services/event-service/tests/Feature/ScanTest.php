<?php

namespace Tests\Feature;

use App\Models\ParkEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ScanTest extends TestCase
{
    use EventTestHelpers, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fixClockAndFakeAttractions();
    }

    private function checkIn(string $reference = 'WL-20261108-AB12CD')
    {
        return $this->postJson('/api/checkins', ['booking_reference' => $reference], $this->adminHeaders());
    }

    private function rideScan(int $attractionId = 1, string $reference = 'WL-20261108-AB12CD')
    {
        return $this->postJson('/api/ride-scans', ['booking_reference' => $reference, 'attraction_id' => $attractionId], $this->adminHeaders());
    }

    public function test_check_in_on_visit_date_succeeds(): void
    {
        $this->createTicket();

        $this->checkIn()
            ->assertOk()
            ->assertJsonPath('guests', 4)
            ->assertJsonPath('message', 'Welcome, Family ticket – 4 guests');

        $this->assertDatabaseHas('park_events', ['event_type' => 'GATE_CHECKIN', 'booking_reference' => 'WL-20261108-AB12CD', 'guests' => 4]);
    }

    public function test_typed_reference_in_small_letters_still_works(): void
    {
        $this->createTicket();

        $this->checkIn(' wl-20261108-ab12cd ')->assertOk();
    }

    public function test_check_in_on_wrong_date_returns_422(): void
    {
        $this->createTicket(['visit_date' => '2026-11-09']);

        $this->checkIn()
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'WRONG_DATE')
            ->assertJsonPath('error.message', 'This ticket is for Mon 9 Nov.');
    }

    public function test_second_check_in_returns_409_with_time(): void
    {
        $this->createTicket();
        $this->checkIn()->assertOk();

        $this->checkIn()
            ->assertStatus(409)
            ->assertJsonPath('error.code', 'ALREADY_CHECKED_IN')
            ->assertJsonPath('error.message', 'Already checked in at 10:30.');

        $this->assertSame(1, ParkEvent::where('event_type', 'GATE_CHECKIN')->count());
    }

    public function test_unknown_ticket_returns_404(): void
    {
        $this->checkIn('WL-20261108-NOPE00')
            ->assertNotFound()
            ->assertJsonPath('error.code', 'TICKET_NOT_FOUND');
    }

    public function test_visitor_cannot_check_in(): void
    {
        $this->createTicket();

        $this->postJson('/api/checkins', ['booking_reference' => 'WL-20261108-AB12CD'], $this->visitorHeaders())
            ->assertForbidden();
    }

    public function test_ride_scan_without_check_in_returns_422(): void
    {
        $this->createTicket();

        $this->rideScan()
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'NOT_CHECKED_IN');
    }

    public function test_ride_scan_after_check_in_records_ride_usage(): void
    {
        $this->createTicket(['checked_in_at' => now()]);

        $this->rideScan()
            ->assertCreated()
            ->assertJsonPath('attraction_name', 'Thunder Coaster')
            ->assertJsonPath('tier_code', 'FAMILY')
            ->assertJsonPath('warning', null);

        $this->assertDatabaseHas('park_events', ['event_type' => 'RIDE_USAGE', 'attraction_id' => 1, 'attraction_name' => 'Thunder Coaster', 'user_id' => 'local-visitor-1']);
    }

    public function test_attraction_name_is_cached_between_scans(): void
    {
        $this->createTicket(['checked_in_at' => now()]);

        $this->rideScan()->assertCreated();
        $this->rideScan()->assertCreated();

        $this->assertCount(1, Http::recorded(fn (Request $request) => str_contains($request->url(), 'attraction.test')));
    }

    public function test_attraction_service_down_still_saves_scan_as_unknown(): void
    {
        $this->createTicket(['checked_in_at' => now()]);
        $this->attractionServiceIsUp = false;

        $this->rideScan(5)
            ->assertCreated()
            ->assertJsonPath('attraction_name', 'Unknown (id 5)')
            ->assertJsonPath('warning', 'Attraction service is not available. The ride was saved without its name.');

        $this->assertDatabaseHas('park_events', ['event_type' => 'RIDE_USAGE', 'attraction_name' => 'Unknown (id 5)']);
    }

    public function test_retired_or_unknown_ride_returns_422(): void
    {
        $this->createTicket(['checked_in_at' => now()]);

        $this->rideScan(7)->assertStatus(422)->assertJsonPath('error.code', 'ATTRACTION_NOT_AVAILABLE');
        $this->rideScan(99)->assertStatus(422)->assertJsonPath('error.code', 'ATTRACTION_NOT_AVAILABLE');
    }

    public function test_visitor_sees_only_own_visits(): void
    {
        $this->createTicket(['checked_in_at' => now()]);
        $this->checkIn('WL-20261108-AB12CD');
        $this->rideScan();
        ParkEvent::create(['event_type' => 'RIDE_USAGE', 'booking_reference' => 'WL-OTHER', 'user_id' => 'someone-else', 'attraction_id' => 1, 'attraction_name' => 'Thunder Coaster', 'occurred_at' => now()]);

        $this->getJson('/api/visits/me', $this->visitorHeaders())
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.event_type', 'RIDE_USAGE')
            ->assertJsonPath('0.attraction_name', 'Thunder Coaster');
    }
}
