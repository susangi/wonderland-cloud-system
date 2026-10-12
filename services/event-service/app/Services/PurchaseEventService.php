<?php

namespace App\Services;

use App\Models\ParkEvent;
use App\Repositories\ParkEventRepository;
use App\Repositories\TicketRepository;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

// Receives TICKET_PURCHASE events from booking-service's outbox.
class PurchaseEventService
{
    public function __construct(
        private TicketRepository $ticketRepository,
        private ParkEventRepository $parkEventRepository,
    ) {}

    // Idempotent: the outbox may send the same event again (retry, flush after a timeout).
    // A booking reference is saved only once, so revenue is never counted twice.
    // Returns true when the purchase was new, false when we already had it.
    public function recordPurchase(array $purchase): bool
    {
        if ($this->ticketRepository->exists($purchase['booking_reference'])) {
            return false;
        }

        try {
            DB::transaction(function () use ($purchase) {
                $this->ticketRepository->create([
                    'booking_reference' => $purchase['booking_reference'],
                    'user_id' => $purchase['user_id'],
                    'tier_code' => $purchase['tier_code'],
                    'guests' => $purchase['guests'],
                    'visit_date' => $purchase['visit_date'],
                    'time_slot_label' => $purchase['time_slot_label'],
                ]);

                $this->parkEventRepository->create([
                    'event_type' => ParkEvent::TYPE_TICKET_PURCHASE,
                    'booking_reference' => $purchase['booking_reference'],
                    'user_id' => $purchase['user_id'],
                    'tier_code' => $purchase['tier_code'],
                    'amount' => $purchase['amount'],
                    'guests' => $purchase['guests'],
                    'occurred_at' => $purchase['occurred_at'],
                ]);
            });
        } catch (UniqueConstraintViolationException) {
            // Two copies arrived at the same moment and the other one won. Same result as a duplicate.
            return false;
        }

        return true;
    }
}
