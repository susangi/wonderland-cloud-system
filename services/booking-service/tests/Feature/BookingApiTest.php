<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\TimeSlot;
use Database\Seeders\TimeSlotSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BookingApiTest extends TestCase
{
    use FakesOtherServices, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(TimeSlotSeeder::class);
        $this->fakeOtherServices();
    }

    private function newBooking(array $changes = []): array
    {
        return array_merge(['tier_id' => 3, 'guests' => 4, 'visit_date' => $this->visitDate(), 'time_slot_id' => 1], $changes);
    }

    public function test_time_slots_show_remaining_places_for_a_date(): void
    {
        $this->createBookingFor('someone', ['status' => Booking::STATUS_CONFIRMED, 'guests' => 4]);

        $this->getJson('/api/time-slots?date='.$this->visitDate())
            ->assertOk()
            ->assertJsonCount(4)
            ->assertJsonPath('0.label', 'Morning 09:00–12:00')
            ->assertJsonPath('0.start_time', '09:00')
            ->assertJsonPath('0.remaining', 196)
            ->assertJsonPath('0.is_full', false)
            ->assertJsonPath('1.remaining', 200);
    }

    public function test_time_slots_need_a_valid_date(): void
    {
        $this->getJson('/api/time-slots')->assertStatus(422)->assertJsonValidationErrors('date');
        $this->getJson('/api/time-slots?date=08-11-2026')->assertStatus(422);
    }

    public function test_visitor_can_create_booking(): void
    {
        $response = $this->postJson('/api/bookings', $this->newBooking(), $this->visitorHeaders());

        $response->assertCreated()
            ->assertJsonPath('status', 'PENDING_PAYMENT')
            ->assertJsonPath('tier_code', 'FAMILY')
            ->assertJsonPath('tier_name', 'Family')
            ->assertJsonPath('price_paid', 140)
            ->assertJsonPath('guests', 4)
            ->assertJsonPath('user_id', 'local-visitor-1')
            ->assertJsonPath('time_slot_label', 'Morning 09:00–12:00');

        $datePart = str_replace('-', '', $this->visitDate());
        $this->assertMatchesRegularExpression("/^WL-{$datePart}-[A-Z0-9]{6}$/", $response->json('booking_reference'));
    }

    public function test_create_booking_without_login_returns_401(): void
    {
        $this->postJson('/api/bookings', $this->newBooking())->assertUnauthorized();
    }

    public function test_full_slot_returns_409(): void
    {
        TimeSlot::whereKey(1)->update(['capacity' => 5]);
        $this->createBookingFor('someone', ['status' => Booking::STATUS_CONFIRMED, 'guests' => 3]);

        $this->postJson('/api/bookings', $this->newBooking(['guests' => 4]), $this->visitorHeaders())
            ->assertStatus(409)
            ->assertJsonPath('error.code', 'SLOT_FULL');

        $this->assertSame(1, Booking::count());
    }

    public function test_recent_unpaid_booking_holds_seats(): void
    {
        TimeSlot::whereKey(1)->update(['capacity' => 5]);
        $this->createBookingFor('someone', ['status' => Booking::STATUS_PENDING_PAYMENT, 'guests' => 3]);

        $this->postJson('/api/bookings', $this->newBooking(['guests' => 4]), $this->visitorHeaders())
            ->assertStatus(409);
    }

    public function test_unpaid_booking_older_than_15_minutes_does_not_hold_seats(): void
    {
        TimeSlot::whereKey(1)->update(['capacity' => 5]);
        $this->travel(-20)->minutes();
        $this->createBookingFor('someone', ['status' => Booking::STATUS_PENDING_PAYMENT, 'guests' => 3]);
        $this->travelBack();

        $this->postJson('/api/bookings', $this->newBooking(['guests' => 4]), $this->visitorHeaders())
            ->assertCreated();
    }

    public function test_guests_over_tier_limit_returns_422(): void
    {
        $this->postJson('/api/bookings', $this->newBooking(['guests' => 5]), $this->visitorHeaders())
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'INVALID_GUESTS')
            ->assertJsonPath('error.message', 'Family tickets allow 1 to 4 guests.');
    }

    public function test_inactive_or_unknown_tier_returns_422(): void
    {
        $this->postJson('/api/bookings', $this->newBooking(['tier_id' => 9]), $this->visitorHeaders())
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'TIER_NOT_AVAILABLE');

        $this->postJson('/api/bookings', $this->newBooking(['tier_id' => 77]), $this->visitorHeaders())
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'TIER_NOT_AVAILABLE');
    }

    public function test_pricing_down_returns_503_after_retries(): void
    {
        $this->pricingIsUp = false;

        $this->postJson('/api/bookings', $this->newBooking(), $this->visitorHeaders())
            ->assertStatus(503)
            ->assertJsonPath('error.code', 'PRICING_UNAVAILABLE');

        // One call plus two retries.
        Http::assertSentCount(3);
        $this->assertSame(0, Booking::count());
    }

    public function test_visit_date_must_be_from_today_to_90_days_ahead(): void
    {
        $this->postJson('/api/bookings', $this->newBooking(['visit_date' => $this->visitDate(-1)]), $this->visitorHeaders())
            ->assertStatus(422)->assertJsonValidationErrors('visit_date');

        $this->postJson('/api/bookings', $this->newBooking(['visit_date' => $this->visitDate(91)]), $this->visitorHeaders())
            ->assertStatus(422)->assertJsonValidationErrors('visit_date');

        $this->postJson('/api/bookings', $this->newBooking(['visit_date' => $this->visitDate(0)]), $this->visitorHeaders())
            ->assertCreated();
    }

    public function test_inactive_time_slot_returns_422(): void
    {
        TimeSlot::whereKey(1)->update(['is_active' => false]);

        $this->postJson('/api/bookings', $this->newBooking(), $this->visitorHeaders())
            ->assertStatus(422)
            ->assertJsonValidationErrors('time_slot_id');
    }

    public function test_my_bookings_shows_only_my_bookings_newest_first(): void
    {
        $older = $this->createBookingFor('local-visitor-1');
        $this->travel(1)->minutes();
        $newer = $this->createBookingFor('local-visitor-1');
        $this->createBookingFor('other-visitor');

        $this->getJson('/api/bookings/me', $this->visitorHeaders())
            ->assertOk()
            ->assertJsonCount(2)
            ->assertJsonPath('0.booking_reference', $newer->booking_reference)
            ->assertJsonPath('1.booking_reference', $older->booking_reference);
    }

    public function test_owner_and_admin_can_view_booking(): void
    {
        $booking = $this->createBookingFor('local-visitor-1');

        $this->getJson("/api/bookings/{$booking->booking_reference}", $this->visitorHeaders())
            ->assertOk()->assertJsonPath('booking_reference', $booking->booking_reference);

        $this->getJson("/api/bookings/{$booking->booking_reference}", $this->adminHeaders())
            ->assertOk();
    }

    public function test_other_user_cannot_view_booking(): void
    {
        $booking = $this->createBookingFor('local-visitor-1');

        $this->getJson("/api/bookings/{$booking->booking_reference}", $this->visitorHeaders('local-visitor-2'))
            ->assertForbidden()
            ->assertJsonPath('error.code', 'FORBIDDEN');
    }

    public function test_unknown_booking_returns_404(): void
    {
        $this->getJson('/api/bookings/WL-20260101-NOPE00', $this->visitorHeaders())
            ->assertNotFound()
            ->assertJsonPath('error.code', 'BOOKING_NOT_FOUND');
    }

    public function test_admin_can_list_bookings_for_a_date(): void
    {
        $this->createBookingFor('a');
        $this->createBookingFor('b');
        $this->createBookingFor('c', ['visit_date' => $this->visitDate(8)]);

        $this->getJson('/api/admin/bookings?date='.$this->visitDate(), $this->adminHeaders())
            ->assertOk()
            ->assertJsonCount(2);
    }

    public function test_visitor_cannot_list_admin_bookings(): void
    {
        $this->getJson('/api/admin/bookings', $this->visitorHeaders())->assertForbidden();
    }
}
