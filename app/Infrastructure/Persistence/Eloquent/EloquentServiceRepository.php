<?php
namespace App\Infrastructure\Persistence\Eloquent;
use App\Domain\Entities\Service;
use App\Domain\Repositories\ServiceRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
class EloquentServiceRepository implements ServiceRepositoryInterface {
    public function createForBarbershop(string $barbershopId, array $servicesData): void {
        if (empty($servicesData)) return;
        $now = now();
        DB::table('services')->insert(
            array_map(fn($service) => array_merge($service, [
                'id'           => Str::uuid()->toString(),
                'barbershop_id' => $barbershopId,
                'is_active'    => true,
                'created_at'   => $now,
                'updated_at'   => $now,
            ]), $servicesData)
        );
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
