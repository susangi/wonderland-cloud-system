<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// Answer after a successful gate check-in. "message" is what the big green panel shows.
class CheckInResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'booking_reference' => $this->booking_reference,
            'tier_code' => $this->tier_code,
            'guests' => $this->guests,
            'visit_date' => $this->visit_date,
            'time_slot_label' => $this->time_slot_label,
            'checked_in_at' => $this->checked_in_at?->toIso8601String(),
            'message' => "Welcome, {$this->tierName()} ticket – {$this->guests} guests",
        ];
    }
}
