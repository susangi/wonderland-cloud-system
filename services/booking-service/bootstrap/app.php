<?php

use App\Exceptions\ApiException;
use App\Http\Middleware\AuthenticateJwt;
use App\Http\Middleware\RequireAdmin;
use App\Http\Middleware\RequireInternalToken;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        // Built in health route. Kubernetes liveness and readiness probes call it.
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Short names used in routes/api.php. Same names in all five services.
        $middleware->alias([
            'auth.jwt' => AuthenticateJwt::class,
            'admin' => RequireAdmin::class,
            'internal' => RequireInternalToken::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Business errors are normal answers (like "slot full"), not bugs, so we do not log them.
        $exceptions->dontReport(ApiException::class);

        // API routes always answer with JSON, even when something goes wrong.
        $exceptions->shouldRenderJsonWhen(fn (Request $request) => $request->is('api/*') || $request->expectsJson());

        // Route not found, wrong method and similar HTTP errors use our error format too.
        $exceptions->render(function (HttpExceptionInterface $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            [$code, $message] = match ($exception->getStatusCode()) {
                404 => ['NOT_FOUND', 'The requested resource was not found.'],
                405 => ['METHOD_NOT_ALLOWED', 'This HTTP method is not allowed here.'],
                default => ['HTTP_ERROR', $exception->getMessage() ?: 'Request failed.'],
            };

            return (new ApiException($code, $message, $exception->getStatusCode()))->render();
        });

        // Unexpected errors: hide the details from users in production. The full error still goes to the log.
        // Validation errors keep Laravel's normal 422 format (message + errors).
        $exceptions->render(function (Throwable $exception, Request $request) {
            if (! $request->is('api/*') || config('app.debug') || $exception instanceof ValidationException) {
                return null;
            }

            return (new ApiException('SERVER_ERROR', 'Something went wrong. Please try again.', 500))->render();
        });
    })->create();
