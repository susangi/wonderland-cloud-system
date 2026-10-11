<?php

namespace App\Services;

use App\Clients\PricingClient;
use App\Exceptions\BookingAccessDeniedException;
use App\Exceptions\BookingNotFoundException;
use App\Exceptions\InvalidGuestCountException;
use App\Exceptions\SlotFullException;
use App\Exceptions\TierNotAvailableException;
use App\Models\Booking;
use App\Repositories\BookingRepository;
use App\Repositories\TimeSlotRepository;
use App\Traits\GeneratesBookingReference;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

// Business rules for creating and reading bookings (design 6.3).
class BookingService
{
    use GeneratesBookingReference;

    public function __construct(
        private BookingRepository $bookingRepository,
        private TimeSlotRepository $timeSlotRepository,
        private PricingClient $pricingClient,
    ) {}

    // Create a booking in PENDING_PAYMENT. The visitor then has 15 minutes to pay.
    public function createBooking(string $userId, array $bookingDetails): Booking
    {
        // Ask pricing-service first, outside the transaction, so we do not keep a row lock during an HTTP call.
        $tier = $this->getBookableTier($bookingDetails['tier_id']);
        $this->ensureGuestsFitTier($tier, $bookingDetails['guests']);

        $booking = DB::transaction(function () use ($userId, $bookingDetails, $tier) {
            // We lock the time slot row here. Otherwise two people can book the last seats at the same time.
            $slot = $this->timeSlotRepository->findForUpdate($bookingDetails['time_slot_id']);
            $bookedGuests = $this->bookingRepository->countBookedGuests($bookingDetails['visit_date'], $slot->id);

            if ($bookedGuests + $bookingDetails['guests'] > $slot->capacity) {
                throw new SlotFullException;
            }

            // Tier code, name and price are copied, so a later price change does not change this booking.
            return $this->bookingRepository->create([
                'booking_reference' => $this->newUniqueReference($bookingDetails['visit_date']),
                'user_id' => $userId,
                'tier_id' => $tier['id'],
                'tier_code' => $tier['code'],
                'tier_name' => $tier['name'],
                'guests' => $bookingDetails['guests'],
                'price_paid' => $tier['price'],
                'currency' => $tier['currency'],
                'visit_date' => $bookingDetails['visit_date'],
                'time_slot_id' => $slot->id,
                'status' => Booking::STATUS_PENDING_PAYMENT,
            ]);
        });

        return $booking->load('timeSlot');
    }

    // The owner or an admin can open a booking (ticket view).
    public function getBookingForViewer(string $bookingReference, string $userId, bool $isAdmin): Booking
    {
        $booking = $this->findBooking($bookingReference);

        if (! $isAdmin && $booking->user_id !== $userId) {
            throw new BookingAccessDeniedException;
        }

        return $booking;
    }

    // Only the owner can pay for a booking.
    public function getBookingOwnedBy(string $bookingReference, string $userId): Booking
    {
        $booking = $this->findBooking($bookingReference);

        if ($booking->user_id !== $userId) {
            throw new BookingAccessDeniedException;
        }

        return $booking;
    }

    public function listBookingsForUser(string $userId): Collection
    {
        return $this->bookingRepository->listForUser($userId);
    }

    public function listBookingsForDate(string $visitDate): Collection
    {
        return $this->bookingRepository->listForDate($visitDate);
    }

    // Unpaid bookings older than 15 minutes give their seats back. Run by "bookings:expire-pending".
    public function expireUnpaidBookings(): int
    {
        $cutoff = now()->subMinutes(Booking::PAYMENT_HOLD_MINUTES);

        return $this->bookingRepository->expireUnpaidCreatedBefore($cutoff);
    }

    private function findBooking(string $bookingReference): Booking
    {
        $booking = $this->bookingRepository->findByReference($bookingReference);

        if ($booking === null) {
            throw new BookingNotFoundException($bookingReference);
        }

        return $booking;
    }

    private function getBookableTier(int $tierId): array
    {
        $tier = $this->pricingClient->getTier($tierId);

        if ($tier === null || ! $tier['is_active']) {
            throw new TierNotAvailableException($tierId);
        }

        return $tier;
    }

    private function ensureGuestsFitTier(array $tier, int $guests): void
    {
        if ($guests < $tier['min_guests'] || $guests > $tier['max_guests']) {
            throw new InvalidGuestCountException($tier['name'], $tier['min_guests'], $tier['max_guests']);
        }
    }

    // Random part has 36^6 (about 2 billion) options, so a clash is very rare. We still check to be safe.
    private function newUniqueReference(string $visitDate): string
    {
        do {
            $reference = $this->generateBookingReference($visitDate);
        } while ($this->bookingRepository->referenceExists($reference));

        return $reference;
    }
}
