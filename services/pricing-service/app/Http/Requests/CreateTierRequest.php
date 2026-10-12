<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

// Validation for a new ticket tier (design 6.2).
class CreateTierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    // Codes are always upper case (FAMILY, not family), so analytics group them correctly.
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('code'))) {
            $this->merge(['code' => strtoupper(trim($this->input('code')))]);
        }
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:20', 'alpha_dash', 'unique:ticket_tiers,code'],
            'name' => ['required', 'string', 'max:50'],
            'min_guests' => ['required', 'integer', 'min:1'],
            'max_guests' => ['required', 'integer', 'gte:min_guests', 'max:10'],
            'price' => ['required', 'numeric', 'gt:0', 'max:99999999.99'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
