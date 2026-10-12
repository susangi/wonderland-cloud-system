<?php

use App\Http\Controllers\Api\AttractionController;
use App\Http\Controllers\Api\HealthController;
use Illuminate\Support\Facades\Route;

// All paths here start with /api (design 6.1).

Route::get('/health', [HealthController::class, 'show']);

// Public. "optional" login lets an admin ask for retired rides too.
Route::get('/attractions', [AttractionController::class, 'index'])->middleware('auth.jwt:optional');
Route::get('/attractions/{id}', [AttractionController::class, 'show'])->whereNumber('id');

// Admin only.
Route::middleware(['auth.jwt', 'admin'])->group(function () {
    Route::post('/attractions', [AttractionController::class, 'store']);
    Route::put('/attractions/{id}', [AttractionController::class, 'update'])->whereNumber('id');
    Route::patch('/attractions/{id}/retire', [AttractionController::class, 'retire'])->whereNumber('id');
    Route::patch('/attractions/{id}/activate', [AttractionController::class, 'activate'])->whereNumber('id');
});
