<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

// GET /api/analytics/summary?from=YYYY-MM-DD&to=YYYY-MM-DD (park dates). Both are optional.
class AnalyticsSummaryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
        ];
    }
}
