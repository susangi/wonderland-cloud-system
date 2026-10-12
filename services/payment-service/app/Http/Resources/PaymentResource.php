<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// Payment fields sent back. Only the last 4 card digits exist, so nothing sensitive can leak.
class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'payment_id' => $this->payment_id,
            'status' => $this->status,
            'card_last4' => $this->card_last4,
            'failure_reason' => $this->failure_reason,
            'booking_reference' => $this->booking_reference,
            'amount' => (float) $this->amount,
            'currency' => $this->currency,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
