<?php

// Wonderland settings for this service. Every value comes from env variables,
// so the same code runs on the laptop and in Kubernetes without changes.

return [

    // Name shown in GET /api/health.
    'service_name' => 'booking-service',

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

    // Other Wonderland services this service calls. Kubernetes DNS names in the cloud
    // (http://pricing-service), 127.0.0.1 ports on the laptop. Never hard code them.
    'services' => [
        'pricing_url' => env('PRICING_URL', 'http://127.0.0.1:8002'),
        'payment_url' => env('PAYMENT_URL', 'http://127.0.0.1:8004'),
        'event_url' => env('EVENT_URL', 'http://127.0.0.1:8005'),
    ],

    // Visit dates and "today" are decided in park time, not UTC.
    'park_timezone' => 'Asia/Colombo',

];
