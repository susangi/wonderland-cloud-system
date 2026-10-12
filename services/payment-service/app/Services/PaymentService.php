<?php

namespace App\Services;

use App\Exceptions\PaymentNotFoundException;
use App\Exceptions\PaymentServiceDownException;
use App\Models\Payment;
use App\Repositories\PaymentRepository;
use Illuminate\Support\Str;

// Mock payment provider (design 6.4). The lecturer confirmed a real gateway is not needed.
// It still behaves like one: some cards are declined and it can be "down" for the demo.
class PaymentService
{
    public function __construct(private PaymentRepository $paymentRepository) {}

    // Decide if the card is accepted and save the attempt.
    public function processPayment(array $paymentDetails): Payment
    {
        $this->actLikeRealProvider();

        $cardNumber = $paymentDetails['card_number'];
        $isDeclined = str_starts_with($cardNumber, '4000');

        // We only keep the last 4 digits of the card. Full card numbers and CVC must never be saved.
        return $this->paymentRepository->create([
            'payment_id' => $this->newPaymentId(),
            'booking_reference' => $paymentDetails['booking_reference'],
            'amount' => $paymentDetails['amount'],
            'currency' => strtoupper($paymentDetails['currency']),
            'card_last4' => substr($cardNumber, -4),
            'status' => $isDeclined ? Payment::STATUS_FAILED : Payment::STATUS_SUCCESS,
            'failure_reason' => $isDeclined ? 'Card declined' : null,
        ]);
    }

    public function getPayment(string $paymentId): Payment
    {
        $payment = $this->paymentRepository->findByPaymentId($paymentId);

        if ($payment === null) {
            throw new PaymentNotFoundException($paymentId);
        }

        return $payment;
    }

    // Demo switches from env: a slow provider (PAYMENT_DELAY_MS) or a broken one (PAYMENT_SIMULATE_DOWN).
    private function actLikeRealProvider(): void
    {
        $delayMs = (int) config('wonderland.payment.delay_ms');

        if ($delayMs > 0) {
            usleep($delayMs * 1000);
        }

        if (config('wonderland.payment.simulate_down')) {
            throw new PaymentServiceDownException;
        }
    }

    // Example: MOCK-TXN-8F3A2C9D1B
    private function newPaymentId(): string
    {
        return 'MOCK-TXN-'.strtoupper(Str::random(10));
    }
}
