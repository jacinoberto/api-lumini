<?php
namespace App\Infrastructure\Persistence\Eloquent;
use App\Domain\Entities\Service;
use App\Domain\Repositories\ServiceRepositoryInterface;
class EloquentServiceRepository implements ServiceRepositoryInterface {
    public function createForBarbershop(string $barbershopId, array $servicesData): void {
        foreach ($servicesData as $service) {
            Service::create(array_merge($service, ['barbershop_id' => $barbershopId]));
        }
    }
}
