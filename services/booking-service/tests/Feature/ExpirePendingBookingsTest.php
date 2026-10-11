<?php

namespace Tests\Feature;

use App\Models\Booking;
use Database\Seeders\TimeSlotSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpirePendingBookingsTest extends TestCase
{
    use FakesOtherServices, RefreshDatabase;

    public function test_unpaid_bookings_older_than_15_minutes_are_expired(): void
    {
        $this->seed(TimeSlotSeeder::class);

        $this->travel(-20)->minutes();
        $oldPending = $this->createBookingFor('a');
        $oldFailed = $this->createBookingFor('b', ['status' => Booking::STATUS_PAYMENT_FAILED]);
        $oldConfirmed = $this->createBookingFor('c', ['status' => Booking::STATUS_CONFIRMED]);
        $this->travelBack();
        $recentPending = $this->createBookingFor('d');

        $this->artisan('bookings:expire-pending')
            ->expectsOutput('Expired 2 unpaid booking(s).')
            ->assertSuccessful();

        $this->assertSame('EXPIRED', $oldPending->fresh()->status);
        $this->assertSame('EXPIRED', $oldFailed->fresh()->status);
        $this->assertSame('CONFIRMED', $oldConfirmed->fresh()->status);
        $this->assertSame('PENDING_PAYMENT', $recentPending->fresh()->status);
    }
}
