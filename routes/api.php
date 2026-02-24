<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VenueController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\VenueIntervalController;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::get('/venues/user', [VenueController::class, 'index']);
Route::get('/venue/dashboard', [VenueController::class, 'dashboard']);
Route::get('/venues', [VenueController::class, 'index']);
Route::get('/venues/{venue}', [VenueController::class, 'show']);

Route::middleware('auth:sanctum')->group(function () {


    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::get('/user/dashboard', [UserController::class , 'userData']);

    // Venues
    Route::get('/venues/manage/admin', [VenueController::class, 'getAdminVenues']);
    Route::get('/venues/manage/admin/{venue}', [VenueController::class, 'getAdminVenue']);
//    Route::get('/venues/{venue}', [VenueController::class, 'show']);
    Route::post('admin/venues', [VenueController::class, 'store'])
        ->middleware('role:venue_admin,super_admin');
    Route::post('/venues/upload/{venue}', [VenueController::class, 'uploadsPhoto'])->middleware('role:venue_admin,super_admin');
    Route::post('venues/admin/manage/update/{venue}', [VenueController::class, 'update']);
    Route::delete('/venues/{venue}', [VenueController::class, 'destroy']);

    Route::get('venues/time/{venue}', [VenueController::class, 'getTime']);
    Route::get('/venues/calendars/{venue}', [VenueController::class, 'getCalendars']);
    Route::get('calendar', [VenueController::class, 'getCalendarsData']);

    Route::post('/venues/price/{venue}', [VenueIntervalController::class, 'createIntervalTimeVenue'])
        ->middleware('role:venue_admin,super_admin');


    // Reservations
    Route::get('/reservations', [ReservationController::class, 'index']);
    Route::get('/reservations/{reservation}', [ReservationController::class, 'show']);
    Route::post('/reservations', [ReservationController::class, 'reserveSlot']);
    Route::get('admin/dashboard', [UserController::class, 'dashboard']);

    Route::patch(
        '/reservations/{reservation}/status',
        [ReservationController::class, 'updateStatus']
    )->middleware('role:venue_admin,super_admin');
});

