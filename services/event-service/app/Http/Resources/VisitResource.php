<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// One item on the "My visits" timeline: a gate check-in or a ride use.
class VisitResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'event_type' => $this->event_type,
            'booking_reference' => $this->booking_reference,
            'tier_code' => $this->tier_code,
            'guests' => $this->guests,
            'attraction_id' => $this->attraction_id,
            'attraction_name' => $this->attraction_name,
            'occurred_at' => $this->occurred_at?->toIso8601String(),
        ];
    }
}
