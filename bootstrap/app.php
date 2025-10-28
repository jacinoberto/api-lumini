<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Cache\RateLimiting\Limit; // Add this import
use Illuminate\Http\Request;             // Add this import
use Illuminate\Support\Facades\RateLimiter;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Configuração dos Grupos de Middleware
        $middleware->group('web', [
            // ... middlewares do grupo web ...
        ]);

        $middleware->group('api', [
            'throttle:api', // Esta linha agora encontrará a definição
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            // Lembre-se de remover EnsureFrontendRequestsAreStateful se mudámos para stateless
        ]);

        $middleware->alias([
            'verified' => \App\Infrastructure\Http\Middleware\EnsureEmailIsVerified::class,
        ]);

        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by(
                $request->user()?->id ?: $request->ip()
            );
        });
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
