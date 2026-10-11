<?php

namespace App\Http\Requests;

use App\Models\Attraction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

// Query filters for GET /api/attractions.
class ListAttractionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category' => ['nullable', Rule::in(Attraction::CATEGORIES)],
            'thrill_level' => ['nullable', Rule::in(Attraction::THRILL_LEVELS)],
            'include_retired' => ['nullable', 'boolean'],
        ];
    }
}
