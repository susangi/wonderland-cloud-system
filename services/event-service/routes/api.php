<?php

use App\Http\Controllers\Api\AnalyticsController;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\PurchaseEventController;
use App\Http\Controllers\Api\ScanController;
use App\Http\Controllers\Api\VisitController;
use Illuminate\Support\Facades\Route;

// All paths here start with /api (design 6.5).

Route::get('/health', [HealthController::class, 'show']);

// Internal only: booking-service's outbox sends purchases here. Not routed by API Gateway or ingress.
Route::post('/internal/events/purchase', [PurchaseEventController::class, 'store'])->middleware('internal');

// Any logged in user.
Route::get('/visits/me', [VisitController::class, 'mine'])->middleware('auth.jwt');

// Admin only (gate and ride staff use the admin role).
Route::middleware(['auth.jwt', 'admin'])->group(function () {
    Route::post('/checkins', [ScanController::class, 'checkIn']);
    Route::post('/ride-scans', [ScanController::class, 'rideScan']);
    Route::get('/analytics/summary', [AnalyticsController::class, 'summary']);
});
