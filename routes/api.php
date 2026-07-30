<?php

use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\EducationalClassController;
use App\Http\Controllers\EducationalClassEnrollmentController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\RoleUpgradeRequestController;
use App\Http\Controllers\SupportTicketController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VenueController;
use App\Http\Controllers\VenueIntervalController;
use App\Http\Controllers\WithdrawalRequestController;

Route::get('/v1/payments/boometo/redirect/{invoice:number}', [PaymentController::class, 'redirect'])
    ->name('payments.boometo.redirect');
Route::match(['get', 'post'], '/v1/payments/boometo/callback', [PaymentController::class, 'callback'])
    ->name('payments.boometo.callback');

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::get('/venues/user', [VenueController::class, 'index']);
Route::get('/venue/dashboard', [VenueController::class, 'dashboard']);
Route::get('/venues', [VenueController::class, 'index']);
Route::get('/venues/{venue}', [VenueController::class, 'show']);
Route::get('/educational-classes', [EducationalClassController::class, 'index']);
Route::get('/educational-classes/{educationalClass}', [EducationalClassController::class, 'show']);

Route::middleware('auth:sanctum')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::get('/user/dashboard', [UserController::class, 'userData']);

    // Educational class registrations
    Route::get('/my/educational-class-enrollments', [EducationalClassEnrollmentController::class, 'index']);
    Route::post('/educational-classes/{educationalClass}/enroll', [EducationalClassEnrollmentController::class, 'store']);
    Route::delete('/educational-classes/{educationalClass}/enroll', [EducationalClassEnrollmentController::class, 'destroy']);

    Route::prefix('manage/educational-classes')->middleware('role:venue_admin,instructor,super_admin')->group(function () {
        Route::get('/', [EducationalClassController::class, 'manageIndex']);
        Route::post('/', [EducationalClassController::class, 'store']);
        Route::get('/analytics', [EducationalClassController::class, 'analytics']);
        Route::put('/{educationalClass}', [EducationalClassController::class, 'update']);
        Route::delete('/{educationalClass}', [EducationalClassController::class, 'destroy']);
        Route::get('/{educationalClass}/enrollments', [EducationalClassController::class, 'enrollments']);
    });

    Route::middleware('role:venue_admin,instructor')->group(function () {
        Route::get('/earnings/balance', [WithdrawalRequestController::class, 'balance']);
        Route::get('/withdrawals', [WithdrawalRequestController::class, 'index']);
        Route::post('/withdrawals', [WithdrawalRequestController::class, 'store']);
    });

    // Venue-owner upgrade requests
    Route::post('/upgrade-requests', [RoleUpgradeRequestController::class, 'store']);
    Route::get('/upgrade-requests', [RoleUpgradeRequestController::class, 'current']);

    // Support
    Route::post('/support/tickets', [SupportTicketController::class, 'store']);
    Route::get('/support/tickets', [SupportTicketController::class, 'index']);
    Route::get('/support/tickets/{ticket}', [SupportTicketController::class, 'show']);
    Route::post('/support/tickets/{ticket}/messages', [SupportTicketController::class, 'message']);

    Route::prefix('admin')->middleware('role:super_admin')->group(function () {
        Route::get('/users', [AdminUserController::class, 'index']);
        Route::get('/users/{user}', [AdminUserController::class, 'show']);
        Route::patch('/users/{user}/role', [AdminUserController::class, 'updateRole']);
        Route::get('/upgrade-requests', [RoleUpgradeRequestController::class, 'index']);
        Route::patch('/upgrade-requests/{upgradeRequest}', [RoleUpgradeRequestController::class, 'review']);
        Route::get('/support/tickets', [SupportTicketController::class, 'adminIndex']);
        Route::get('/support/tickets/{ticket}', [SupportTicketController::class, 'show']);
        Route::post('/support/tickets/{ticket}/messages', [SupportTicketController::class, 'message']);
        Route::patch('/support/tickets/{ticket}/status', [SupportTicketController::class, 'updateStatus']);
        Route::get('/educational-classes', [EducationalClassController::class, 'adminIndex']);
        Route::patch('/educational-classes/{educationalClass}/status', [EducationalClassController::class, 'adminStatus']);
        Route::get('/withdrawals', [WithdrawalRequestController::class, 'adminIndex']);
        Route::get('/withdrawals/{withdrawalRequest}', [WithdrawalRequestController::class, 'show']);
        Route::patch('/withdrawals/{withdrawalRequest}/status', [WithdrawalRequestController::class, 'updateStatus']);
    });

    // Venues
    Route::get('/venues/manage/admin', [VenueController::class, 'getAdminVenues'])->middleware('role:venue_admin,super_admin');
    Route::get('/venues/manage/admin/{venue}', [VenueController::class, 'getAdminVenue'])->middleware('role:venue_admin,super_admin');
    //    Route::get('/venues/{venue}', [VenueController::class, 'show']);
    Route::post('admin/venues', [VenueController::class, 'store'])
        ->middleware('role:venue_admin,super_admin');
    Route::post('/venues/upload/{venue}', [VenueController::class, 'uploadsPhoto'])->middleware('role:venue_admin,super_admin');
    Route::post('venues/admin/manage/update/{venue}', [VenueController::class, 'update'])->middleware('role:venue_admin,super_admin');
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
    Route::get('admin/dashboard', [UserController::class, 'dashboard'])->middleware('role:venue_admin,super_admin');

    Route::patch(
        '/reservations/{reservation}/status',
        [ReservationController::class, 'updateStatus']
    )->middleware('role:venue_admin,super_admin');
});
