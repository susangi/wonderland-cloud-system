<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// Fields of a ticket tier that are sent to the frontend and to booking-service.
class TicketTierResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'min_guests' => $this->min_guests,
            'max_guests' => $this->max_guests,
            // Sent as a number (140.0), not a string, so the frontend can do maths with it.
            'price' => (float) $this->price,
            'currency' => $this->currency,
            'is_active' => $this->is_active,
        ];
    }
}
