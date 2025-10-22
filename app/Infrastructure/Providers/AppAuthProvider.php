<?php

namespace App\Infrastructure\Providers;

use App\Domain\Entities\Barbershop;
use App\Policies\BarbershopPolicy;
use Illuminate\Support\ServiceProvider;

class AppAuthProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    protected $policies = [
        Barbershop::class => BarbershopPolicy::class,
    ];

    public function boot(): void
    {
        //
    }
}
