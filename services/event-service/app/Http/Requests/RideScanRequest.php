<?php

namespace App\Http\Requests;

// POST /api/ride-scans. Same reference rules as check-in, plus the ride.
class RideScanRequest extends CheckInRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'attraction_id' => ['required', 'integer', 'min:1'],
        ]);
    }
}
