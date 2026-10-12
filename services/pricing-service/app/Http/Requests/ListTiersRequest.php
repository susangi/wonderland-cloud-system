<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

// Query options for GET /api/tiers.
class ListTiersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'include_inactive' => ['nullable', 'boolean'],
        ];
    }
}
