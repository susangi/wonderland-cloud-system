<?php

// Wonderland settings for this service. Every value comes from env variables,
// so the same code runs on the laptop and in Kubernetes without changes.

return [

    // Name shown in GET /api/health.
    'service_name' => 'payment-service',

    // "cognito" checks real Cognito tokens. "local" accepts X-Local-User headers
    // and is allowed only on the laptop (APP_ENV local or testing).
    'auth_mode' => env('AUTH_MODE', 'cognito'),

    'cognito' => [
        'region' => env('COGNITO_REGION'),
        'user_pool_id' => env('COGNITO_USER_POOL_ID'),
        'app_client_id' => env('COGNITO_APP_CLIENT_ID'),
    ],

    // Shared secret for service to service calls (X-Internal-Token header).
    'internal_token' => env('INTERNAL_TOKEN'),

    // Demo switches for the mock payment (design 6.4).
    // simulate_down=true makes every payment return 503, to show the circuit breaker in booking-service.
    // delay_ms adds a wait before answering, to show timeouts.
    'payment' => [
        'simulate_down' => (bool) env('PAYMENT_SIMULATE_DOWN', false),
        'delay_ms' => (int) env('PAYMENT_DELAY_MS', 0),
    ],

];
