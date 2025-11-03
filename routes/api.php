<?php

use App\Infrastructure\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Infrastructure\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Infrastructure\Http\Controllers\Auth\NewPasswordController;
use App\Infrastructure\Http\Controllers\Auth\PasswordResetLinkController;
use App\Infrastructure\Http\Controllers\Auth\RegisteredUserController;
use App\Infrastructure\Http\Controllers\Auth\VerifyEmailController;
use App\Infrastructure\Http\Controllers\BarbershopController;
use App\Infrastructure\Http\Controllers\ServiceController;
use App\Infrastructure\Http\Controllers\BarberController;
use App\Infrastructure\Http\Controllers\FavoriteController;
use App\Infrastructure\Http\Controllers\AppointmentController;
use App\Infrastructure\Http\Controllers\DashboardController;
use App\Infrastructure\Http\Controllers\ProfileController;
use App\Infrastructure\Http\Controllers\OnboardingController;
use App\Infrastructure\Http\Controllers\ClientAppointmentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// ==========================================
// ROTAS PÚBLICAS
// ==========================================
Route::post('/register', [RegisteredUserController::class, 'store'])->middleware('guest');
Route::post('/login', [AuthenticatedSessionController::class, 'store'])->middleware('guest');
Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])->middleware('guest');
Route::post('/reset-password', [NewPasswordController::class, 'store'])->middleware('guest');

Route::prefix('barbershops')->group(function () {
    Route::get('/', [BarbershopController::class, 'index']);
    Route::get('/search', [BarbershopController::class, 'search']);
    Route::get('/{id}', [BarbershopController::class, 'show']);
});

// ==========================================
// ROTAS AUTENTICADAS
// ==========================================
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy']);

    Route::get('/verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::post('/email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware(['throttle:6,1']);

    Route::get('/onboarding/status', [OnboardingController::class, 'checkStatus']);
    Route::post('/onboarding/complete', [OnboardingController::class, 'complete']);

    Route::post('/profile/change-password', [ProfileController::class, 'changePassword']);
    Route::put('/profile', [ProfileController::class, 'updateProfile']);

    Route::prefix('barbershops')->group(function () {
        Route::put('/{barbershop}', [BarbershopController::class, 'update']);
        Route::put('/{barbershop}/profile', [ProfileController::class, 'updateBarbershop']);

        Route::get('/{barbershop}/dashboard/stats', [DashboardController::class, 'stats']);
        Route::get('/{barbershop}/dashboard/today', [DashboardController::class, 'todayAppointments']);

        // Business Hours
        Route::get('/{barbershop}/business-hours', [BarbershopController::class, 'getHours']);
        Route::post('/{barbershop}/business-hours', [BarbershopController::class, 'createHour']);
        Route::put('/{barbershop}/business-hours/{businessHour}', [BarbershopController::class, 'updateHour']);

        Route::prefix('{barbershop}/services')->group(function () {
            Route::get('/', [ServiceController::class, 'index']);
            Route::post('/', [ServiceController::class, 'store']);
            Route::get('/{service}', [ServiceController::class, 'show']);
            Route::put('/{service}', [ServiceController::class, 'update']);
            Route::delete('/{service}', [ServiceController::class, 'destroy']);
        });

        Route::prefix('{barbershop}/barbers')->group(function () {
            Route::get('/', [BarberController::class, 'index']);
            Route::post('/', [BarberController::class, 'store']);
            Route::put('/{barber}', [BarberController::class, 'update']);
            Route::delete('/{barber}', [BarberController::class, 'destroy']);
        });

        Route::prefix('{barbershop}/appointments')->group(function () {
            Route::get('/', [AppointmentController::class, 'index']);
            Route::post('/', [AppointmentController::class, 'store']);
            Route::get('/{appointment}', [AppointmentController::class, 'show']);
            Route::put('/{appointment}', [AppointmentController::class, 'update']);
            Route::patch('/{appointment}/status', [AppointmentController::class, 'updateStatus']);
            Route::delete('/{appointment}', [AppointmentController::class, 'destroy']);
        });

        Route::prefix('{barbershop}/clients')->group(function () {
            Route::get('/', [ClientAppointmentController::class, 'index']);
            Route::get('/{client}', [ClientAppointmentController::class, 'show']);
            Route::get('/{client}/appointments', [ClientAppointmentController::class, 'appointments']);
            Route::patch('/{client}/notes', [ClientAppointmentController::class, 'updateNotes']);
        });

        Route::get('/{id}/available-slots', [BarbershopController::class, 'availableSlots']);
    });

    Route::prefix('client')->group(function () {
        Route::get('/favorites', [FavoriteController::class, 'index']);
        Route::post('/favorites', [FavoriteController::class, 'store']);
        Route::delete('/favorites/{barbershop_id}', [FavoriteController::class, 'destroy']);
        Route::get('/favorites/{barbershop_id}/check', [FavoriteController::class, 'check']);

        Route::post('/appointments/{id}/rating', [AppointmentController::class, 'rateAppointment']);

        Route::get('/appointments', [AppointmentController::class, 'clientAppointments']);
        Route::post('/appointments', [AppointmentController::class, 'clientStore']);
        Route::get('/appointments/{id}', [AppointmentController::class, 'clientShow']);
        Route::delete('/appointments/{id}', [AppointmentController::class, 'clientDestroy']);
    });
});
