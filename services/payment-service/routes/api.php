<?php

use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\PaymentController;
use Illuminate\Support\Facades\Route;

// All paths here start with /api (design 6.4).

Route::get('/health', [HealthController::class, 'show']);

// Internal only: called by booking-service with X-Internal-Token. Not routed by API Gateway or ingress.
Route::post('/internal/payments', [PaymentController::class, 'store'])->middleware('internal');

// Admin only.
Route::get('/payments/{paymentId}', [PaymentController::class, 'show'])->middleware(['auth.jwt', 'admin']);
