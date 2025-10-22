<?php
namespace App\Domain\Repositories;
interface BusinessHourRepositoryInterface {
public function updateForBarbershop(string $barbershopId, array $hoursData): void;
}
