<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

// Builds JSON responses in the same shape in every service,
// so the frontend can read success and error answers the same way.
trait ApiResponse
{
    // Send data back as plain JSON (no "data" wrapper).
    protected function successResponse(mixed $data, int $status = 200): JsonResponse
    {
        return response()->json($data, $status);
    }

    // Send an error in the format from design section 9: {"error": {"code", "message"}}.
    protected function errorResponse(string $code, string $message, int $status): JsonResponse
    {
        return response()->json([
            'error' => [
                'code' => $code,
                'message' => $message,
            ],
        ], $status);
    }
}
