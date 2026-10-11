<?php

namespace App\Services;

use App\Clients\PaymentClient;
use App\Exceptions\BookingAlreadyPaidException;
use App\Exceptions\BookingExpiredException;
use App\Exceptions\InvalidCardException;
use App\Exceptions\PaymentDeclinedException;
use App\Exceptions\PaymentUnavailableException;
use App\Models\Booking;
use App\Repositories\BookingRepository;
use App\Support\CircuitBreaker;
use Illuminate\Support\Facades\DB;

// Pays for a booking through payment-service (design 6.3, pay steps 1 to 5).
// Uses the circuit breaker so a broken payment service does not make every visitor wait.
class PaymentFlowService
{
    private CircuitBreaker $paymentBreaker;

    public function __construct(
        private BookingService $bookingService,
        private BookingRepository $bookingRepository,
        private PaymentClient $paymentClient,
        private OutboxService $outboxService,
    ) {
        $this->paymentBreaker = new CircuitBreaker('payment');
    }

    // $cardDetails go straight to payment-service. They are never saved or logged here.
    public function payBooking(string $userId, string $bookingReference, array $cardDetails): Booking
    {
        $booking = $this->bookingService->getBookingOwnedBy($bookingReference, $userId);
        $this->ensureBookingCanBePaid($booking);

        // Breaker is open: answer at once instead of waiting for timeouts. Booking stays as it is.
        if (! $this->paymentBreaker->allowsCall()) {
            throw new PaymentUnavailableException;
        }

        $paymentResult = $this->chargeCard($booking, $cardDetails);

        if ($paymentResult['status'] === 'SUCCESS') {
            return $this->confirmBooking($booking, $paymentResult['payment_id']);
        }

        // Declined. The visitor can retry the same booking (FR-V-12).
        $this->bookingRepository->updateStatus($booking, Booking::STATUS_PAYMENT_FAILED);

        throw new PaymentDeclinedException($paymentResult['failure_reason'] ?? 'Card declined');
    }

    private function ensureBookingCanBePaid(Booking $booking): void
    {
        if ($booking->status === Booking::STATUS_CONFIRMED) {
            throw new BookingAlreadyPaidException;
        }

        // The seats are no longer held for this booking, so paying now could oversell the slot.
        if ($booking->status === Booking::STATUS_EXPIRED || $booking->isPaymentHoldOver()) {
            $this->bookingRepository->updateStatus($booking, Booking::STATUS_EXPIRED);

            throw new BookingExpiredException;
        }
    }

    // Call payment-service and tell the breaker how it went.
    private function chargeCard(Booking $booking, array $cardDetails): array
    {
        $paymentRequest = [
            'booking_reference' => $booking->booking_reference,
            'amount' => (float) $booking->price_paid,
            'currency' => $booking->currency,
            'card_number' => $cardDetails['card_number'],
            'expiry' => $cardDetails['expiry'],
            'cvc' => $cardDetails['cvc'],
            'card_holder' => $cardDetails['card_holder'],
        ];

        try {
            $paymentResult = $this->paymentClient->charge($paymentRequest);
        } catch (PaymentUnavailableException $exception) {
            $this->paymentBreaker->recordFailure();
            throw $exception;
        } catch (InvalidCardException $exception) {
            // Wrong card details are the visitor's mistake. The payment service itself is healthy.
            $this->paymentBreaker->recordSuccess();
            throw $exception;
        }

        $this->paymentBreaker->recordSuccess();

        return $paymentResult;
    }

    // Confirm the booking and save its outbox event in ONE transaction, then try to send the event.
    // If event-service is down the visitor still gets the ticket. outbox:flush sends the event later.
    private function confirmBooking(Booking $booking, string $paymentId): Booking
    {
        $outboxEvent = DB::transaction(function () use ($booking, $paymentId) {
            $this->bookingRepository->markConfirmed($booking, $paymentId);

            return $this->outboxService->recordTicketPurchase($booking);
        });

        $this->outboxService->send($outboxEvent);

        return $booking;
    }
}
