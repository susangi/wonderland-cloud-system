<?php

namespace App\Http\Middleware;

use App\Traits\ApiResponse;
use App\Traits\ReadsAuthUser;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

// Lets only park administrators (Cognito "admins" group) through.
// Must run after AuthenticateJwt. The frontend also hides admin menus,
// but this check on the server is the real protection.
class RequireAdmin
{
    use ApiResponse, ReadsAuthUser;

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->currentUserIsAdmin()) {
            return $this->errorResponse('FORBIDDEN', 'Only park administrators can do this.', 403);
        }

        return $next($request);
    }
}
