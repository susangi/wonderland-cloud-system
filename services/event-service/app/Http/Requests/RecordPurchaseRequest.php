<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

// TICKET_PURCHASE event sent by booking-service's outbox (internal).
class RecordPurchaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'booking_reference' => ['required', 'string', 'max:30'],
            'user_id' => ['required', 'string', 'max:64'],
            'tier_code' => ['required', 'string', 'max:20'],
            'guests' => ['required', 'integer', 'min:1', 'max:10'],
            'amount' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'visit_date' => ['required', 'date_format:Y-m-d'],
            'time_slot_label' => ['required', 'string', 'max:50'],
            'occurred_at' => ['required', 'date'],
        ];
    }
}
