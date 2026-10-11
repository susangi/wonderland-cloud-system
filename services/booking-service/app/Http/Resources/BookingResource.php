<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// Booking fields sent to the frontend (ticket view, booking history, admin list).
class BookingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'booking_reference' => $this->booking_reference,
            'status' => $this->status,
            'user_id' => $this->user_id,
            'tier_id' => $this->tier_id,
            'tier_code' => $this->tier_code,
            'tier_name' => $this->tier_name,
            'guests' => $this->guests,
            'price_paid' => (float) $this->price_paid,
            'currency' => $this->currency,
            'visit_date' => substr((string) $this->visit_date, 0, 10),
            'time_slot_id' => $this->time_slot_id,
            'time_slot_label' => $this->timeSlot?->label,
            'payment_id' => $this->payment_id,
            'confirmed_at' => $this->confirmed_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
