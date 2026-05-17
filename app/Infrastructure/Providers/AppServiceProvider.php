<?php

namespace App\Infrastructure\Providers;

use App\Domain\Repositories\AddressRepositoryInterface;
use App\Domain\Repositories\BarberRepositoryInterface;
use App\Domain\Repositories\BarbershopRepositoryInterface;
use App\Domain\Repositories\BusinessHourRepositoryInterface;
use App\Domain\Repositories\ServiceRepositoryInterface;
use App\Domain\Repositories\UserRepositoryInterface;
use App\Domain\Repositories\PaymentRepositoryInterface;
use App\Domain\Repositories\ZipCodeRepositoryInterface;
use App\Infrastructure\Persistence\Eloquent\EloquentAddressRepository;
use App\Infrastructure\Persistence\Eloquent\EloquentBarberRepository;
use App\Infrastructure\Persistence\Eloquent\EloquentBarbershopRepository;
use App\Infrastructure\Persistence\Eloquent\EloquentBusinessHourRepository;
use App\Infrastructure\Persistence\Eloquent\EloquentPaymentRepository;
use App\Infrastructure\Persistence\Eloquent\EloquentServiceRepository;
use App\Infrastructure\Persistence\Eloquent\EloquentUserRepository;
use App\Infrastructure\Persistence\Eloquent\EloquentZipCodeRepository;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            UserRepositoryInterface::class,
            EloquentUserRepository  ::class
        );
        $this->app->bind(
            BarbershopRepositoryInterface::class,
            EloquentBarbershopRepository::class
        );
        $this->app->bind(AddressRepositoryInterface::class, EloquentAddressRepository::class);
        $this->app->bind(ZipCodeRepositoryInterface::class, EloquentZipCodeRepository::class);
        $this->app->bind(BusinessHourRepositoryInterface::class, EloquentBusinessHourRepository::class);
        $this->app->bind(ServiceRepositoryInterface::class, EloquentServiceRepository::class);
        $this->app->bind(BarberRepositoryInterface::class, EloquentBarberRepository::class);
        $this->app->bind(PaymentRepositoryInterface::class, EloquentPaymentRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Laravel reabilita E_ALL no bootstrap — suprime deprecated do vendor aqui
        error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);
    }
}
