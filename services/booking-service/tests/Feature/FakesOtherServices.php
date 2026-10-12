<?php

namespace Tests\Feature;

use App\Models\Booking;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;

// Fake pricing, payment and event services for booking tests (Http::fake).
// Switch a service "down" by setting its flag to false.
trait FakesOtherServices
{
    protected bool $pricingIsUp = true;
    protected bool $paymentIsUp = true;
    protected bool $eventIsUp = true;

    protected function fakeOtherServices(): void
    {
        // Retry waits (200 ms, 400 ms) are skipped so tests stay fast.
        Sleep::fake();

        Http::fake(function (Request $request) {
            return match (parse_url($request->url(), PHP_URL_HOST)) {
                'pricing.test' => $this->fakePricingResponse($request),
                'payment.test' => $this->fakePaymentResponse($request),
                'event.test' => $this->eventIsUp ? Http::response(['status' => 'recorded'], 201) : Http::response('down', 500),
            };
        });
    }

    protected function familyTier(): array
    {
        return ['id' => 3, 'code' => 'FAMILY', 'name' => 'Family', 'min_guests' => 1, 'max_guests' => 4, 'price' => 140, 'currency' => 'USD', 'is_active' => true];
    }

    protected function visitDate(int $daysFromToday = 7): string
    {
        return now('Asia/Colombo')->addDays($daysFromToday)->toDateString();
    }

    protected function createBookingFor(string $userId, array $overrides = []): Booking
    {
        return Booking::create(array_merge([
            'booking_reference' => 'WL-'.str_replace('-', '', $this->visitDate()).'-'.strtoupper(substr(md5(uniqid()), 0, 6)),
            'user_id' => $userId,
            'tier_id' => 3,
            'tier_code' => 'FAMILY',
            'tier_name' => 'Family',
            'guests' => 4,
            'price_paid' => 140,
            'currency' => 'USD',
            'visit_date' => $this->visitDate(),
            'time_slot_id' => 1,
            'status' => Booking::STATUS_PENDING_PAYMENT,
        ], $overrides));
    }

    protected function paymentCallCount(): int
    {
        return count(Http::recorded(fn (Request $request) => str_contains($request->url(), 'payment.test')));
    }

    private function fakePricingResponse(Request $request)
    {
        if (! $this->pricingIsUp) {
            return Http::response(['error' => ['code' => 'SERVER_ERROR']], 500);
        }

        return match (basename(parse_url($request->url(), PHP_URL_PATH))) {
            '3' => Http::response($this->familyTier()),
            '9' => Http::response(array_merge($this->familyTier(), ['id' => 9, 'code' => 'OLD', 'is_active' => false])),
            default => Http::response(['error' => ['code' => 'TIER_NOT_FOUND']], 404),
        };
    }

    // Same rules as the real mock payment service: 4000... declined, 1234... invalid, others accepted.
    private function fakePaymentResponse(Request $request)
    {
        if (! $this->paymentIsUp) {
            return Http::response(['error' => ['code' => 'PAYMENT_SERVICE_DOWN']], 503);
        }

        $cardNumber = str_replace(' ', '', $request['card_number']);

        if (str_starts_with($cardNumber, '1234')) {
            return Http::response(['message' => 'The card number is not valid.', 'errors' => ['card_number' => ['The card number is not valid.']]], 422);
        }

        $isDeclined = str_starts_with($cardNumber, '4000');

        return Http::response([
            'payment_id' => 'MOCK-TXN-TEST123456',
            'status' => $isDeclined ? 'FAILED' : 'SUCCESS',
            'card_last4' => substr($cardNumber, -4),
            'failure_reason' => $isDeclined ? 'Card declined' : null,
        ], 201);
    }
}
