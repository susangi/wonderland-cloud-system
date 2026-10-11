<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

// GET /api/time-slots?date=YYYY-MM-DD
class ListTimeSlotsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date' => ['required', 'date_format:Y-m-d'],
        ];
    }
}
