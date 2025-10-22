<?php
namespace App\Infrastructure\Persistence\Eloquent;
use App\Domain\Entities\BusinessHour;
use App\Domain\Repositories\BusinessHourRepositoryInterface;
class EloquentBusinessHourRepository implements BusinessHourRepositoryInterface {
    public function updateForBarbershop(string $barbershopId, array $hoursData): void {
        BusinessHour::where('barbershop_id', $barbershopId)->delete();
        foreach ($hoursData as $hour) {
            BusinessHour::create(array_merge($hour, ['barbershop_id' => $barbershopId]));
        }
    }
}
