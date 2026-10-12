<?php

namespace Tests\Feature;

use App\Models\ParkEvent;
use App\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseEventTest extends TestCase
{
    use EventTestHelpers, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fixClockAndFakeAttractions();
    }

    public function test_purchase_creates_ticket_copy_and_purchase_event(): void
    {
        $this->postJson('/api/internal/events/purchase', $this->purchasePayload(), $this->internalHeaders())
            ->assertCreated()
            ->assertJsonPath('status', 'recorded');

        $this->assertDatabaseHas('tickets', ['booking_reference' => 'WL-20261108-AB12CD', 'guests' => 4, 'checked_in_at' => null]);
        $this->assertDatabaseHas('park_events', ['event_type' => 'TICKET_PURCHASE', 'tier_code' => 'FAMILY', 'amount' => 140, 'source' => 'live']);
    }

    public function test_same_purchase_twice_is_saved_only_once(): void
    {
        $this->postJson('/api/internal/events/purchase', $this->purchasePayload(), $this->internalHeaders())->assertCreated();

        $this->postJson('/api/internal/events/purchase', $this->purchasePayload(), $this->internalHeaders())
            ->assertOk()
            ->assertJsonPath('status', 'duplicate');

        $this->assertSame(1, Ticket::count());
        $this->assertSame(1, ParkEvent::count());
    }

    public function test_purchase_needs_internal_token(): void
    {
        $this->postJson('/api/internal/events/purchase', $this->purchasePayload())
            ->assertUnauthorized()
            ->assertJsonPath('error.code', 'INVALID_INTERNAL_TOKEN');
    }

    public function test_purchase_with_missing_fields_returns_422(): void
    {
        $this->postJson('/api/internal/events/purchase', ['booking_reference' => 'WL-X'], $this->internalHeaders())
            ->assertStatus(422)
            ->assertJsonValidationErrors(['user_id', 'tier_code', 'guests', 'visit_date', 'occurred_at']);
    }
}
