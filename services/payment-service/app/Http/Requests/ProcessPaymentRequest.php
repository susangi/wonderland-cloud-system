<?php

namespace App\Http\Requests;

use App\Rules\LuhnCheck;
use Illuminate\Foundation\Http\FormRequest;

// Validation for POST /api/internal/payments (called only by booking-service).
class ProcessPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    // Visitors often type the card with spaces ("4242 4242 4242 4242"), so we remove them first.
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('card_number'))) {
            $this->merge(['card_number' => str_replace([' ', '-'], '', $this->input('card_number'))]);
        }
    }

    public function rules(): array
    {
        return [
            'booking_reference' => ['required', 'string', 'max:30'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'currency' => ['required', 'string', 'size:3'],
            'card_number' => ['required', 'digits:16', new LuhnCheck],
            'expiry' => ['required', 'regex:/^(0[1-9]|1[0-2])\/\d{2}$/'],
            'cvc' => ['required', 'digits_between:3,4'],
            'card_holder' => ['required', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'expiry.regex' => 'The expiry must be in MM/YY format.',
        ];
    }
}
