<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

// Validation for PUT /api/tiers/{id}: price, guests, name and active flag (design 6.2).
// The code cannot change, because old bookings and analytics use it.
class UpdateTierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:50'],
            'min_guests' => ['required', 'integer', 'min:1'],
            'max_guests' => ['required', 'integer', 'gte:min_guests', 'max:10'],
            'price' => ['required', 'numeric', 'gt:0', 'max:99999999.99'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
