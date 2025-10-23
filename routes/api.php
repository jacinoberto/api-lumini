<?php

use App\Infrastructure\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Infrastructure\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Infrastructure\Http\Controllers\Auth\NewPasswordController;
use App\Infrastructure\Http\Controllers\Auth\PasswordResetLinkController;
use App\Infrastructure\Http\Controllers\Auth\RegisteredUserController;
use App\Infrastructure\Http\Controllers\Auth\VerifyEmailController;
use App\Infrastructure\Http\Controllers\BarbershopController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Infrastructure\Http\Controllers\ServiceController;
use App\Infrastructure\Http\Controllers\BarberController;

// Rotas de Autenticação (Públicas)
Route::post('/register', [RegisteredUserController::class, 'store'])->middleware('guest');
Route::post('/login', [AuthenticatedSessionController::class, 'store'])->middleware('guest');
Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])->middleware('guest');
Route::post('/reset-password', [NewPasswordController::class, 'store'])->middleware('guest');

// Rotas que Exigem Autenticação
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy']);

    // Rotas de verificação de e-mail que precisam de autenticação
    Route::get('/verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::post('/email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware(['throttle:6,1']);

    Route::prefix('/barbershops')->group(function () {
        Route::put('/{barbershop}', [BarbershopController::class, 'update']);
        Route::get('/{barbershop}/business-hours', [BarbershopController::class, 'getHours']);
        Route::put('/{barbershop}/business-hours', [BarbershopController::class, 'updateHours']);
        Route::get('/{barbershop}/services', [ServiceController::class, 'index']);
        Route::post('/{barbershop}/services', [ServiceController::class, 'store']);
        Route::put('/{barbershop}/services/{service}', [ServiceController::class, 'update'])->scopeBindings();
        Route::get('/{barbershop}/barbers', [BarberController::class, 'index']);
        Route::post('/{barbershop}/barbers', [BarberController::class, 'store']);
        Route::put('/{barbershop}/barbers/{barber}', [BarberController::class, 'update'])->scopeBindings();
    });
});
