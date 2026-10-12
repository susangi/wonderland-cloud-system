<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\OutboxEvent;
use Database\Seeders\TimeSlotSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class PaymentFlowTest extends TestCase
{
    use FakesOtherServices, RefreshDatabase;

    private Booking $booking;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(TimeSlotSeeder::class);
        $this->fakeOtherServices();
        $this->booking = $this->createBookingFor('local-visitor-1');
    }

    private function card(string $cardNumber = '4242 4242 4242 4242'): array
    {
        return ['card_number' => $cardNumber, 'expiry' => '12/28', 'cvc' => '123', 'card_holder' => 'Nimal Perera'];
    }

    private function pay(array $card, string $userId = 'local-visitor-1')
    {
        return $this->postJson("/api/bookings/{$this->booking->booking_reference}/pay", $card, $this->visitorHeaders($userId));
    }

    public function test_successful_payment_confirms_booking_and_sends_ticket_event(): void
    {
        $this->pay($this->card())
            ->assertOk()
            ->assertJsonPath('status', 'CONFIRMED')
            ->assertJsonPath('payment_id', 'MOCK-TXN-TEST123456');

        $this->assertNotNull($this->booking->fresh()->confirmed_at);

        $outboxEvent = OutboxEvent::first();
        $this->assertSame('TICKET_PURCHASE', $outboxEvent->type);
        $this->assertSame($this->booking->booking_reference, $outboxEvent->payload['booking_reference']);
        $this->assertSame('Morning 09:00–12:00', $outboxEvent->payload['time_slot_label']);
        $this->assertNotNull($outboxEvent->sent_at);

        Http::assertSent(fn (Request $request) => str_contains($request->url(), 'event.test/api/internal/events/purchase')
            && $request->hasHeader('X-Internal-Token', 'test-internal-token'));
    }

    public function test_card_details_are_sent_to_payment_service_but_not_to_event_service(): void
    {
        $this->pay($this->card())->assertOk();

        Http::assertSent(fn (Request $request) => str_contains($request->url(), 'payment.test') && $request['amount'] == 140);
        Http::assertNotSent(fn (Request $request) => str_contains($request->url(), 'event.test') && str_contains($request->body(), '4242'));
    }

    public function test_declined_card_sets_payment_failed_and_returns_402(): void
    {
        $this->pay($this->card('4000 0000 0000 0002'))
            ->assertStatus(402)
            ->assertJsonPath('error.code', 'PAYMENT_DECLINED');

        $this->assertSame('PAYMENT_FAILED', $this->booking->fresh()->status);
        $this->assertSame(0, OutboxEvent::count());
    }

    public function test_visitor_can_retry_after_declined_card(): void
    {
        $this->pay($this->card('4000 0000 0000 0002'))->assertStatus(402);

        $this->pay($this->card())->assertOk()->assertJsonPath('status', 'CONFIRMED');
    }

    public function test_invalid_card_returns_422_and_keeps_booking_pending(): void
    {
        $this->pay($this->card('1234 5678 1234 5678'))
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'INVALID_CARD');

        $this->assertSame('PENDING_PAYMENT', $this->booking->fresh()->status);
    }

    public function test_payment_service_down_returns_503_and_booking_stays_pending(): void
    {
        $this->paymentIsUp = false;

        $this->pay($this->card())
            ->assertStatus(503)
            ->assertJsonPath('error.code', 'PAYMENT_UNAVAILABLE')
            ->assertJsonPath('error.message', 'Payment service is busy, please try again in a minute.');

        $this->assertSame('PENDING_PAYMENT', $this->booking->fresh()->status);
        // One call plus two retries.
        $this->assertSame(3, $this->paymentCallCount());
    }

    public function test_circuit_breaker_opens_after_5_failures_and_skips_payment_calls(): void
    {
        Log::spy();
        $this->paymentIsUp = false;

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->pay($this->card())->assertStatus(503);
        }
        $callsBeforeOpen = $this->paymentCallCount();

        // Breaker is open now: answer is 503 at once, without calling payment-service.
        $this->pay($this->card())->assertStatus(503)->assertJsonPath('error.code', 'PAYMENT_UNAVAILABLE');

        $this->assertSame($callsBeforeOpen, $this->paymentCallCount());
        $this->getJson('/api/health')->assertJsonPath('payment_circuit', 'OPEN');
        Log::shouldHaveReceived('warning')->with('circuit_breaker_state_change', \Mockery::on(fn ($context) => $context['to'] === 'OPEN'));
    }

    public function test_breaker_lets_one_trial_through_after_30_seconds_and_closes_on_success(): void
    {
        $this->paymentIsUp = false;
        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->pay($this->card())->assertStatus(503);
        }

        $this->travel(31)->seconds();
        $this->paymentIsUp = true;

        $this->pay($this->card())->assertOk()->assertJsonPath('status', 'CONFIRMED');
        $this->getJson('/api/health')->assertJsonPath('payment_circuit', 'CLOSED');
    }

    public function test_event_service_down_booking_still_confirmed_and_outbox_sent_later(): void
    {
        $this->eventIsUp = false;

        $this->pay($this->card())->assertOk()->assertJsonPath('status', 'CONFIRMED');

        $outboxEvent = OutboxEvent::first();
        $this->assertNull($outboxEvent->sent_at);
        $this->assertSame(1, $outboxEvent->attempts);
        $this->assertStringContainsString('500', $outboxEvent->last_error);

        // event-service comes back. The scheduled flush delivers the event.
        $this->eventIsUp = true;
        $this->artisan('outbox:flush')
            ->expectsOutput('Outbox flush finished. Sent: 1, failed: 0.')
            ->assertSuccessful();

        $this->assertNotNull($outboxEvent->fresh()->sent_at);
        $this->assertNull($outboxEvent->fresh()->last_error);
    }

    public function test_outbox_stops_after_10_attempts(): void
    {
        OutboxEvent::create(['type' => 'TICKET_PURCHASE', 'payload' => ['booking_reference' => 'X'], 'attempts' => 10]);

        $this->artisan('outbox:flush')->expectsOutput('Outbox flush finished. Sent: 0, failed: 0.');
    }

    public function test_other_user_cannot_pay_booking(): void
    {
        $this->pay($this->card(), 'local-visitor-2')->assertForbidden();

        $this->assertSame(0, $this->paymentCallCount());
    }

    public function test_confirmed_booking_cannot_be_paid_again(): void
    {
        $this->pay($this->card())->assertOk();

        $this->pay($this->card())->assertStatus(409)->assertJsonPath('error.code', 'BOOKING_ALREADY_PAID');
    }

    public function test_booking_older_than_15_minutes_cannot_be_paid(): void
    {
        $this->travel(16)->minutes();

        $this->pay($this->card())->assertStatus(422)->assertJsonPath('error.code', 'BOOKING_EXPIRED');

        $this->assertSame('EXPIRED', $this->booking->fresh()->status);
        $this->assertSame(0, $this->paymentCallCount());
    }

    public function test_pay_needs_card_details(): void
    {
        $this->pay([])->assertStatus(422)->assertJsonValidationErrors(['card_number', 'expiry', 'cvc', 'card_holder']);
    }
}
