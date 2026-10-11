<?php

namespace App\Http\Requests;

use App\Models\Attraction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

// Validation for a new attraction (design 6.1).
// Admin check is done by the RequireAdmin middleware, not here.
class CreateAttractionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'description' => ['required', 'string'],
            'category' => ['required', Rule::in(Attraction::CATEGORIES)],
            'thrill_level' => ['required', Rule::in(Attraction::THRILL_LEVELS)],
            // Optional. When left out the database default 0 (no limit) is used.
            'min_age' => ['sometimes', 'integer', 'min:0', 'max:100'],
            'min_height_cm' => ['sometimes', 'integer', 'min:0', 'max:250'],
        ];
    }
}
