<?php
namespace App\Infrastructure\Persistence\Eloquent;
use App\Domain\Entities\Barber;
use App\Domain\Entities\Barbershop; // Import Barbershop
use App\Domain\Repositories\BarberRepositoryInterface;

class EloquentBarberRepository implements BarberRepositoryInterface {
    public function createForBarbershop(string $barbershopId, array $data): Barber {
        // Encontra a barbearia e usa o relacionamento para criar
        $barbershop = Barbershop::findOrFail($barbershopId);
        return $barbershop->barbers()->create($data);
    }

    public function update(Barber $barber, array $data): Barber
    {
        $barber->update($data);
        return $barber->refresh();
    }
}
