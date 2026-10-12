<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

// Validation for POST /api/bookings (design 6.3 step 1).
// Tier limits are checked later in BookingService, because they come from pricing-service.
class CreateBookingRequest extends FormRequest
{
    // Visitors can book from today up to 90 days ahead (FR-V-09).
    private const MAX_DAYS_AHEAD = 90;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // "Today" is the park's today (Asia/Colombo), not the server's UTC date.
        $today = now(config('wonderland.park_timezone'))->startOfDay();
        $lastBookableDay = $today->copy()->addDays(self::MAX_DAYS_AHEAD);

        return [
            'tier_id' => ['required', 'integer'],
            'guests' => ['required', 'integer', 'min:1', 'max:10'],
            'visit_date' => [
                'required',
                'date_format:Y-m-d',
                'after_or_equal:'.$today->toDateString(),
                'before_or_equal:'.$lastBookableDay->toDateString(),
            ],
            'time_slot_id' => ['required', 'integer', Rule::exists('time_slots', 'id')->where('is_active', true)],
        ];
    }

    public function messages(): array
    {
        return [
            'visit_date.after_or_equal' => 'The visit date cannot be in the past.',
            'visit_date.before_or_equal' => 'You can book up to 90 days ahead.',
            'time_slot_id.exists' => 'This time slot is not available.',
        ];
    }
}
