<?php
namespace App\Domain\Repositories;
interface ServiceRepositoryInterface {
public function createForBarbershop(string $barbershopId, array $servicesData): void;
}
