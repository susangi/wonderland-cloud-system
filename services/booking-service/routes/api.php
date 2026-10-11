<?php

use App\Http\Controllers\Api\AdminBookingController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\TimeSlotController;
use Illuminate\Support\Facades\Route;

// All paths here start with /api (design 6.3).

Route::get('/health', [HealthController::class, 'show']);

// Public.
Route::get('/time-slots', [TimeSlotController::class, 'index']);

// Any logged in user. Owner checks are in BookingService.
Route::middleware('auth.jwt')->group(function () {
    Route::post('/bookings', [BookingController::class, 'store']);
    // "me" must come before {reference}, otherwise "me" would be read as a reference.
    Route::get('/bookings/me', [BookingController::class, 'mine']);
    Route::get('/bookings/{reference}', [BookingController::class, 'show']);
    Route::post('/bookings/{reference}/pay', [BookingController::class, 'pay']);
});

// Admin only.
Route::get('/admin/bookings', [AdminBookingController::class, 'index'])->middleware(['auth.jwt', 'admin']);
