<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

// Validation for POST /api/bookings/{reference}/pay.
// Only basic checks here. payment-service does the card checks (16 digits, Luhn) like a real provider.
class PayBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'card_number' => ['required', 'string', 'max:25'],
            'expiry' => ['required', 'string', 'max:5'],
            'cvc' => ['required', 'string', 'max:4'],
            'card_holder' => ['required', 'string', 'max:100'],
        ];
    }
}
