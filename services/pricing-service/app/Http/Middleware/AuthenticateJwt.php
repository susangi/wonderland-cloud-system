<?php

namespace App\Http\Middleware;

use App\Traits\ApiResponse;
use Closure;
use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

// Finds out who is calling the API and saves the user on the request.
// API Gateway already checks the token, but we check it again here (defence in depth),
// because a request could reach the service without going through the gateway.
//
// Use "auth.jwt" for routes that need a login and "auth.jwt:optional" for public routes
// that only want to know if the caller is an admin.
class AuthenticateJwt
{
    use ApiResponse;

    // Cognito public keys change rarely, so we keep them for one hour.
    private const JWKS_CACHE_SECONDS = 3600;

    public function handle(Request $request, Closure $next, string $mode = 'required'): Response
    {
        $hasCredentials = $request->bearerToken() !== null || $request->hasHeader('X-Local-User');

        // Public route and the caller did not send any login, so continue as a guest.
        if ($mode === 'optional' && ! $hasCredentials) {
            return $next($request);
        }

        $user = config('wonderland.auth_mode') === 'local'
            ? $this->userFromLocalHeaders($request)
            : $this->userFromCognitoToken($request);

        if ($user === null) {
            return $this->errorResponse('UNAUTHENTICATED', 'Please log in to continue.', 401);
        }

        $request->attributes->set('user', $user);

        return $next($request);
    }

    // Local development login: X-Local-User and X-Local-Groups headers instead of a token.
    // AppServiceProvider makes sure this mode can never run in production.
    private function userFromLocalHeaders(Request $request): ?array
    {
        $userId = $request->header('X-Local-User');

        if (empty($userId)) {
            return null;
        }

        $groups = array_filter(array_map('trim', explode(',', (string) $request->header('X-Local-Groups', ''))));

        return [
            'sub' => $userId,
            'email' => null,
            'groups' => array_values($groups),
        ];
    }

    // Real login: check the Cognito access token signature and claims.
    private function userFromCognitoToken(Request $request): ?array
    {
        $token = $request->bearerToken();

        if ($token === null) {
            return null;
        }

        try {
            // decode() checks the signature with the Cognito keys and also checks "exp".
            $claims = JWT::decode($token, JWK::parseKeySet($this->cognitoKeys()));
        } catch (Throwable) {
            return null;
        }

        // Only accept access tokens made by our user pool for our app client.
        $isValid = ($claims->iss ?? null) === $this->cognitoIssuer()
            && ($claims->token_use ?? null) === 'access'
            && ($claims->client_id ?? null) === config('wonderland.cognito.app_client_id');

        if (! $isValid) {
            return null;
        }

        return [
            'sub' => $claims->sub,
            'email' => $claims->email ?? null,
            'groups' => $claims->{'cognito:groups'} ?? [],
        ];
    }

    // Download the user pool public keys (JWKS). Cached so we do not call Cognito on every request.
    private function cognitoKeys(): array
    {
        return Cache::remember('cognito_jwks', self::JWKS_CACHE_SECONDS, function () {
            return Http::timeout(3)
                ->get($this->cognitoIssuer().'/.well-known/jwks.json')
                ->throw()
                ->json();
        });
    }

    private function cognitoIssuer(): string
    {
        return sprintf(
            'https://cognito-idp.%s.amazonaws.com/%s',
            config('wonderland.cognito.region'),
            config('wonderland.cognito.user_pool_id'),
        );
    }
}
