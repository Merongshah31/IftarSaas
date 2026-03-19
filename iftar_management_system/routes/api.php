<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AdminAuthController;
use App\Http\Controllers\Api\IftarDayController;
use App\Http\Controllers\Api\ParticipantController;
use App\Http\Controllers\Api\PublicRegistrationController;
use App\Http\Controllers\Api\SponsorshipController;
use App\Http\Controllers\Api\MasjidController;
use App\Http\Controllers\Api\SuperadminController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {
    // Public routes
    Route::get('public/masjids', [MasjidController::class, 'publicIndex']);
    Route::get('public/masjids/{masjid}/iftar-days', [PublicRegistrationController::class, 'listOpenIftarDays']);
    Route::post('public/masjids/{masjid}/participants', [PublicRegistrationController::class, 'registerParticipant']);

    // Admin authentication routes
    Route::post('admin/register', [AdminAuthController::class, 'register']);
    Route::post('admin/login', [AdminAuthController::class, 'login']);

    Route::middleware('admin.auth')->group(function () {
        Route::get('admin/me', [AdminAuthController::class, 'me']);
        Route::post('admin/logout', [AdminAuthController::class, 'logout']);
    });

    // Superadmin routes (master key only)
    Route::middleware('master.key')->prefix('superadmin')->group(function () {
        Route::get('admins/pending', [SuperadminController::class, 'pendingAdmins']);
        Route::post('admins/{userId}/approve', [SuperadminController::class, 'approveAdmin']);
    });

    // Admin panel routes (authenticated + tenant context)
    Route::middleware(['admin.auth', 'tenant'])->group(function () {
        // Masjid routes
        Route::get('masjid/profile', [MasjidController::class, 'profile']);
        Route::put('masjid/profile', [MasjidController::class, 'update']);
        Route::get('masjid/dashboard', [MasjidController::class, 'dashboard']);

        // Sponsorship - Specific routes BEFORE resource
        Route::get('sponsorships/statistics', [SponsorshipController::class, 'statistics']);
        Route::get('sponsorships/top-sponsors', [SponsorshipController::class, 'topSponsors']);
        Route::post('sponsorships/{sponsorship}/mark-paid', [SponsorshipController::class, 'markAsPaid']);

        // Sponsorship - Resource routes
        Route::apiResource('sponsorships', SponsorshipController::class);

        // Iftar Days - Specific routes BEFORE resource
        Route::get('iftar-days/{iftarDay}/statistics', [IftarDayController::class, 'statistics']);
        Route::post('iftar-days/{iftarDay}/close', [IftarDayController::class, 'close']);
        Route::post('iftar-days/{iftarDay}/reopen', [IftarDayController::class, 'reopen']);

        // Iftar Days - Resource routes
        Route::apiResource('iftar-days', IftarDayController::class);

        // Participants - Specific routes BEFORE resource
        Route::post('participants/check-eligibility', [ParticipantController::class, 'checkEligibility']);
        Route::post('participants/{participant}/cancel', [ParticipantController::class, 'cancel']);
        Route::post('participants/{participant}/check-in', [ParticipantController::class, 'checkIn']);
        Route::post('participants/{participant}/no-show', [ParticipantController::class, 'noShow']);

        // Participants - Resource routes
        Route::apiResource('participants', ParticipantController::class)->only(['index', 'store', 'show']);
    });
});