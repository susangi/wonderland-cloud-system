<?php

use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\TicketTierController;
use Illuminate\Support\Facades\Route;

// All paths here start with /api (design 6.2).

Route::get('/health', [HealthController::class, 'show']);

// Public. "optional" login lets an admin ask for inactive tiers too.
// booking-service also calls GET /api/tiers/{id} when a visitor books.
Route::get('/tiers', [TicketTierController::class, 'index'])->middleware('auth.jwt:optional');
Route::get('/tiers/{id}', [TicketTierController::class, 'show'])->whereNumber('id');

// Admin only.
Route::middleware(['auth.jwt', 'admin'])->group(function () {
    Route::post('/tiers', [TicketTierController::class, 'store']);
    Route::put('/tiers/{id}', [TicketTierController::class, 'update'])->whereNumber('id');
});
