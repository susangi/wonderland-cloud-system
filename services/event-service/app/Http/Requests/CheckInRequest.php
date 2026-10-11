<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

// POST /api/checkins. The reference comes from the QR code or is typed by staff.
class CheckInRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    // Typed references may have spaces or small letters, so we clean them first.
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('booking_reference'))) {
            $this->merge(['booking_reference' => strtoupper(trim($this->input('booking_reference')))]);
        }
    }

    public function rules(): array
    {
        return [
            'booking_reference' => ['required', 'string', 'max:30'],
        ];
    }
}
