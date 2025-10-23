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

    public function create(array $data): Service
    {
        return Service::create($data);
    }

    public function update(Service $service, array $data): Service
    {
        $service->update($data);
        return $service->refresh(); // Retorna o modelo atualizado
    }
}
