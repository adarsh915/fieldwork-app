<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DayController;
use App\Http\Controllers\Api\MasterController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// ─── Public Routes ─────────────────────────────────────────────────────────────
Route::prefix('auth')->name('api.auth.')->group(function () {
    Route::post('login', [AuthController::class, 'login'])->name('login');
});

// ─── Protected Routes ──────────────────────────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::prefix('auth')->name('api.auth.')->group(function () {
        Route::get('me',     [AuthController::class, 'me'])->name('me');
        Route::post('logout',[AuthController::class, 'logout'])->name('logout');
    });

    // Master Data (dropdowns / pickers)
    Route::prefix('master')->name('api.master.')->group(function () {
        Route::get('tour-purposes',  [MasterController::class, 'tourPurposes'])->name('tour-purposes');
        Route::get('tour-types',     [MasterController::class, 'tourTypes'])->name('tour-types');
        Route::get('vehicle-types',  [MasterController::class, 'vehicleTypes'])->name('vehicle-types');
        Route::get('parties',        [MasterController::class, 'parties'])->name('parties');
    });

    // Day Lifecycle
    Route::prefix('day')->name('api.day.')->group(function () {
        Route::get('status', [DayController::class, 'status'])->name('status');
        Route::post('start', [DayController::class, 'start'])->name('start');
        Route::post('end',   [DayController::class, 'end'])->name('end');
    });

    // Catalog
    Route::get('/products', [\App\Http\Controllers\Api\ProductController::class, 'index']);

    // Orders
    Route::post('/orders', [\App\Http\Controllers\Api\OrderController::class, 'store']);

    // Visits
    Route::prefix('visits')->name('api.visits.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\VisitController::class, 'index'])->name('index');
        Route::get('/party/{partyId}/status', [\App\Http\Controllers\Api\VisitController::class, 'status'])->name('status');
        Route::post('/check-in', [\App\Http\Controllers\Api\VisitController::class, 'checkIn'])->name('check-in');
        Route::patch('/{id}/check-out', [\App\Http\Controllers\Api\VisitController::class, 'checkOut'])->name('check-out');
    });
});
