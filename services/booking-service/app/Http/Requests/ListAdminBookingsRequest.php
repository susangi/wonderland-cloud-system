<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

// GET /api/admin/bookings?date=YYYY-MM-DD. Without a date the park's today is used.
class ListAdminBookingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date' => ['nullable', 'date_format:Y-m-d'],
        ];
    }

    public function visitDate(): string
    {
        return $this->input('date') ?? now(config('wonderland.park_timezone'))->toDateString();
    }
}
