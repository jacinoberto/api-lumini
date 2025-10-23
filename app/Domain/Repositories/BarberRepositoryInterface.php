<?php
namespace App\Domain\Repositories;
use App\Domain\Entities\Barber;
interface BarberRepositoryInterface {
    public function createForBarbershop(string $barbershopId, array $data): Barber;
    public function update(Barber $barber, array $data): Barber;
}
