<?php

namespace App\Http\Middleware;

use App\Traits\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

// Protects /api/internal/* routes that only other Wonderland services may call.
// The caller must send X-Internal-Token with the shared secret.
// These routes are also not exposed by API Gateway or the ingress.
class RequireInternalToken
{
    use ApiResponse;

    public function handle(Request $request, Closure $next): Response
    {
        $expectedToken = (string) config('wonderland.internal_token');
        $givenToken = (string) $request->header('X-Internal-Token', '');

        // hash_equals compares in constant time, so the token cannot be guessed by timing.
        if ($expectedToken === '' || ! hash_equals($expectedToken, $givenToken)) {
            return $this->errorResponse('INVALID_INTERNAL_TOKEN', 'This endpoint is only for Wonderland services.', 401);
        }

        return $next($request);
    }
}
