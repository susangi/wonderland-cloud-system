<?php

namespace App\Repositories;

use App\Models\Booking;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

// All database queries for bookings are kept here.
class BookingRepository
{
    // Count guests already holding seats in a slot. Old unpaid bookings are not counted.
    public function countBookedGuests(string $visitDate, int $timeSlotId): int
    {
        return (int) $this->seatHoldingBookings()
            ->where('visit_date', $visitDate)
            ->where('time_slot_id', $timeSlotId)
            ->sum('guests');
    }

    // Same rule as above for all slots of one day, in one query. Returns [time_slot_id => guests].
    public function bookedGuestsPerSlot(string $visitDate): array
    {
        return $this->seatHoldingBookings()
            ->where('visit_date', $visitDate)
            ->groupBy('time_slot_id')
            ->selectRaw('time_slot_id, SUM(guests) as booked_guests')
            ->pluck('booked_guests', 'time_slot_id')
            ->map(fn ($guests) => (int) $guests)
            ->all();
    }

    public function create(array $attributes): Booking
    {
        return Booking::create($attributes);
    }

    public function referenceExists(string $bookingReference): bool
    {
        return Booking::where('booking_reference', $bookingReference)->exists();
    }

    public function findByReference(string $bookingReference): ?Booking
    {
        return Booking::with('timeSlot')->where('booking_reference', $bookingReference)->first();
    }

    // Booking history for one visitor, newest first.
    public function listForUser(string $userId): Collection
    {
        return Booking::with('timeSlot')
            ->where('user_id', $userId)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();
    }

    public function listForDate(string $visitDate): Collection
    {
        return Booking::with('timeSlot')
            ->where('visit_date', $visitDate)
            ->orderBy('time_slot_id')
            ->orderBy('created_at')
            ->get();
    }

    public function markConfirmed(Booking $booking, string $paymentId): Booking
    {
        $booking->update([
            'status' => Booking::STATUS_CONFIRMED,
            'payment_id' => $paymentId,
            'confirmed_at' => now(),
        ]);

        return $booking;
    }

    public function updateStatus(Booking $booking, string $status): Booking
    {
        $booking->update(['status' => $status]);

        return $booking;
    }

    // Mark unpaid bookings older than the cutoff as EXPIRED. Returns how many were changed.
    public function expireUnpaidCreatedBefore(\DateTimeInterface $cutoff): int
    {
        return Booking::whereIn('status', [Booking::STATUS_PENDING_PAYMENT, Booking::STATUS_PAYMENT_FAILED])
            ->where('created_at', '<', $cutoff)
            ->update(['status' => Booking::STATUS_EXPIRED, 'updated_at' => now()]);
    }

    // Bookings that hold seats: confirmed ones, plus unpaid ones made in the last 15 minutes.
    // A failed payment also keeps its seats for 15 minutes, so the visitor can retry without losing them.
    private function seatHoldingBookings(): Builder
    {
        $holdStartedAfter = now()->subMinutes(Booking::PAYMENT_HOLD_MINUTES);

        return Booking::query()->where(function (Builder $query) use ($holdStartedAfter) {
            $query->where('status', Booking::STATUS_CONFIRMED)
                ->orWhere(function (Builder $unpaid) use ($holdStartedAfter) {
                    $unpaid->whereIn('status', [Booking::STATUS_PENDING_PAYMENT, Booking::STATUS_PAYMENT_FAILED])
                        ->where('created_at', '>=', $holdStartedAfter);
                });
        });
    }
}
