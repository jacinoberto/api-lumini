<?php

use App\Infrastructure\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Infrastructure\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Infrastructure\Http\Controllers\Auth\NewPasswordController;
use App\Infrastructure\Http\Controllers\Auth\PasswordResetLinkController;
use App\Infrastructure\Http\Controllers\Auth\RegisteredUserController;
use App\Infrastructure\Http\Controllers\Auth\VerifyEmailController;
use App\Infrastructure\Http\Controllers\BarbershopController;
use App\Infrastructure\Http\Controllers\ClientController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Infrastructure\Http\Controllers\ServiceController;
use App\Infrastructure\Http\Controllers\BarberController;
use App\Infrastructure\Http\Controllers\FavoriteController;
use App\Infrastructure\Http\Controllers\AppointmentController;

// Rotas de Autenticação (Públicas)
Route::post('/register', [RegisteredUserController::class, 'store'])->middleware('guest');
Route::post('/login', [AuthenticatedSessionController::class, 'store'])->middleware('guest');
Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])->middleware('guest');
Route::post('/reset-password', [NewPasswordController::class, 'store'])->middleware('guest');

// Rotas PÚBLICAS do Cliente (não precisa autenticação)
Route::prefix('barbershops')->group(function () {
    Route::get('/', [BarbershopController::class, 'index']);
    Route::get('/search', [BarbershopController::class, 'search']);
    Route::get('/{id}', [BarbershopController::class, 'show']);
});

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
        //Worner
        Route::put('/{barbershop}', [BarbershopController::class, 'update']);
        Route::get('/{barbershop}/business-hours', [BarbershopController::class, 'getHours']);
        Route::put('/{barbershop}/business-hours', [BarbershopController::class, 'updateHours']);

        Route::get('/{barbershop}/services', [ServiceController::class, 'index']);
        Route::post('/{barbershop}/services', [ServiceController::class, 'store']);
        Route::put('/{barbershop}/services/{service}', [ServiceController::class, 'update'])->scopeBindings();
        Route::get('/{barbershop}/services/{service}', [ServiceController::class, 'show']);  // Buscar um
        Route::put('/{barbershop}/services/{service}', [ServiceController::class, 'update']); // Atualizar
        Route::delete('/{barbershop}/services/{service}', [ServiceController::class, 'destroy']); // Deletar

        Route::get('/{barbershop}/barbers', [BarberController::class, 'index']);
        Route::post('/{barbershop}/barbers', [BarberController::class, 'store']);
        Route::put('/{barbershop}/barbers/{barber}', [BarberController::class, 'update'])->scopeBindings();
        Route::delete('/{barbershop}/barbers/{barber}', [BarberController::class, 'destroy']);

        Route::prefix('{barbershop}/appointments')->group(function () {
            Route::get('/', [AppointmentController::class, 'index']);
            Route::post('/', [AppointmentController::class, 'store']);
            Route::get('/{appointment}', [AppointmentController::class, 'show']);
            Route::put('/{appointment}', [AppointmentController::class, 'update']);
            Route::patch('/{appointment}/status', [AppointmentController::class, 'updateStatus']);
            Route::delete('/{appointment}', [AppointmentController::class, 'destroy']);
        });

        // Cliente
        Route::get('/{id}/available-slots', [BarbershopController::class, 'availableSlots']);
        Route::prefix('{barbershop}/clients')->group(function () {
            Route::get('/', [ClientController::class, 'index']);
            Route::get('/{client}', [ClientController::class, 'show']);
            Route::get('/{client}/appointments', [ClientController::class, 'appointments']);
            Route::patch('/{client}/notes', [ClientController::class, 'updateNotes']);
        });
    });

    Route::prefix('/client')->group(function () {
        // Favoritos
        Route::get('favorites', [FavoriteController::class, 'index']);
        Route::post('favorites', [FavoriteController::class, 'store']);
        Route::delete('favorites/{barbershop_id}', [FavoriteController::class, 'destroy']);
        Route::get('favorites/{barbershop_id}/check', [FavoriteController::class, 'check']);

        // Agendamentos
        Route::get('appointments', [AppointmentController::class, 'index']);
        Route::post('appointments', [AppointmentController::class, 'store']);
        Route::get('appointments/{id}', [AppointmentController::class, 'show']);
        Route::delete('appointments/{id}', [AppointmentController::class, 'destroy']);
    });

});
