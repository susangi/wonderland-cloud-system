<?php

namespace App\Services;

use App\Clients\EventClient;
use App\Models\Booking;
use App\Models\OutboxEvent;
use App\Repositories\OutboxEventRepository;
use Illuminate\Support\Facades\Log;
use Throwable;

// Transactional outbox (design 7.3). Makes sure every confirmed booking reaches event-service,
// even when event-service is down at the moment of payment.
class OutboxService
{
    private const FLUSH_BATCH_SIZE = 50;

    public function __construct(
        private OutboxEventRepository $outboxEventRepository,
        private EventClient $eventClient,
    ) {}

    // Save the TICKET_PURCHASE event. Call this inside the same transaction that confirms the booking,
    // so we never have a confirmed booking without its event (or an event without a booking).
    public function recordTicketPurchase(Booking $booking): OutboxEvent
    {
        return $this->outboxEventRepository->create(OutboxEvent::TYPE_TICKET_PURCHASE, [
            'booking_reference' => $booking->booking_reference,
            'user_id' => $booking->user_id,
            'tier_code' => $booking->tier_code,
            'guests' => $booking->guests,
            'amount' => (float) $booking->price_paid,
            'currency' => $booking->currency,
            'visit_date' => $booking->visit_date,
            'time_slot_label' => $booking->timeSlot->label,
            'occurred_at' => $booking->confirmed_at->toIso8601String(),
        ]);
    }

    // Try to deliver one event now. If it fails we only save the error. The visitor is not affected.
    public function send(OutboxEvent $event): bool
    {
        try {
            $this->eventClient->sendTicketPurchase($event->payload);
            $this->outboxEventRepository->markSent($event);

            return true;
        } catch (Throwable $exception) {
            $this->outboxEventRepository->markFailed($event, $exception->getMessage());
            Log::warning('outbox_send_failed', ['outbox_event_id' => $event->id, 'error' => $exception->getMessage()]);

            return false;
        }
    }

    // Resend everything still unsent. Run every minute by "outbox:flush".
    // Returns ['sent' => n, 'failed' => n].
    public function flushUnsent(): array
    {
        $result = ['sent' => 0, 'failed' => 0];

        foreach ($this->outboxEventRepository->listUnsent(self::FLUSH_BATCH_SIZE) as $event) {
            $this->send($event) ? $result['sent']++ : $result['failed']++;
        }

        return $result;
    }
}
