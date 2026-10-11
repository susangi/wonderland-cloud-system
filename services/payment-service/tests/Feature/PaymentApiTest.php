<?php

namespace Tests\Feature;

use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PaymentApiTest extends TestCase
{
    use RefreshDatabase;

    private function internalHeaders(): array
    {
        return ['X-Internal-Token' => 'test-internal-token'];
    }

    private function paymentFor(string $cardNumber): array
    {
        return [
            'booking_reference' => 'WL-20261108-AB12CD',
            'amount' => 140.00,
            'currency' => 'USD',
            'card_number' => $cardNumber,
            'expiry' => '12/28',
            'cvc' => '123',
            'card_holder' => 'Nimal Perera',
        ];
    }

    public function test_card_4242_is_accepted(): void
    {
        $this->postJson('/api/internal/payments', $this->paymentFor('4242424242424242'), $this->internalHeaders())
            ->assertCreated()
            ->assertJsonPath('status', 'SUCCESS')
            ->assertJsonPath('card_last4', '4242')
            ->assertJsonPath('failure_reason', null);

        $this->assertStringStartsWith('MOCK-TXN-', Payment::first()->payment_id);
    }

    public function test_card_typed_with_spaces_is_accepted(): void
    {
        $this->postJson('/api/internal/payments', $this->paymentFor('4242 4242 4242 4242'), $this->internalHeaders())
            ->assertCreated()
            ->assertJsonPath('status', 'SUCCESS');
    }

    public function test_card_4000_is_declined(): void
    {
        $this->postJson('/api/internal/payments', $this->paymentFor('4000000000000002'), $this->internalHeaders())
            ->assertCreated()
            ->assertJsonPath('status', 'FAILED')
            ->assertJsonPath('failure_reason', 'Card declined')
            ->assertJsonPath('card_last4', '0002');
    }

    public function test_other_valid_card_is_accepted(): void
    {
        // 5555 5555 5555 4444 is a well known test number that passes the Luhn check.
        $this->postJson('/api/internal/payments', $this->paymentFor('5555555555554444'), $this->internalHeaders())
            ->assertCreated()
            ->assertJsonPath('status', 'SUCCESS');
    }

    public function test_card_failing_luhn_check_returns_422(): void
    {
        $this->postJson('/api/internal/payments', $this->paymentFor('4242424242424241'), $this->internalHeaders())
            ->assertStatus(422)
            ->assertJsonValidationErrors('card_number');

        $this->assertSame(0, Payment::count());
    }

    public function test_card_without_16_digits_returns_422(): void
    {
        $this->postJson('/api/internal/payments', $this->paymentFor('42424242'), $this->internalHeaders())
            ->assertStatus(422)
            ->assertJsonValidationErrors('card_number');
    }

    public function test_missing_internal_token_returns_401(): void
    {
        $this->postJson('/api/internal/payments', $this->paymentFor('4242424242424242'))
            ->assertUnauthorized()
            ->assertJsonPath('error.code', 'INVALID_INTERNAL_TOKEN');
    }

    public function test_wrong_internal_token_returns_401(): void
    {
        $this->postJson('/api/internal/payments', $this->paymentFor('4242424242424242'), ['X-Internal-Token' => 'guess'])
            ->assertUnauthorized();
    }

    public function test_full_card_number_and_cvc_are_never_stored(): void
    {
        $this->postJson('/api/internal/payments', $this->paymentFor('4242424242424242'), $this->internalHeaders())
            ->assertCreated();

        $row = (array) DB::table('payments')->first();

        $this->assertSame('4242', $row['card_last4']);
        $this->assertArrayNotHasKey('card_number', $row);
        $this->assertArrayNotHasKey('cvc', $row);
        // Search every column value: the full number must not appear anywhere.
        $this->assertStringNotContainsString('4242424242424242', json_encode($row));
    }

    public function test_simulate_down_returns_503(): void
    {
        config(['wonderland.payment.simulate_down' => true]);

        $this->postJson('/api/internal/payments', $this->paymentFor('4242424242424242'), $this->internalHeaders())
            ->assertStatus(503)
            ->assertJsonPath('error.code', 'PAYMENT_SERVICE_DOWN');

        $this->assertSame(0, Payment::count());
    }

    public function test_admin_can_view_payment(): void
    {
        $paymentId = $this->postJson('/api/internal/payments', $this->paymentFor('4242424242424242'), $this->internalHeaders())
            ->json('payment_id');

        $this->getJson("/api/payments/{$paymentId}", $this->adminHeaders())
            ->assertOk()
            ->assertJsonPath('payment_id', $paymentId)
            ->assertJsonPath('booking_reference', 'WL-20261108-AB12CD')
            ->assertJsonPath('amount', 140);
    }

    public function test_visitor_cannot_view_payment(): void
    {
        $this->getJson('/api/payments/MOCK-TXN-ANY', $this->visitorHeaders())
            ->assertForbidden();
    }

    public function test_unknown_payment_returns_404(): void
    {
        $this->getJson('/api/payments/MOCK-TXN-NOPE', $this->adminHeaders())
            ->assertNotFound()
            ->assertJsonPath('error.code', 'PAYMENT_NOT_FOUND');
    }
}
