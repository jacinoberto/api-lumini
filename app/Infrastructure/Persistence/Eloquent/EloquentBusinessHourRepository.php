<?php
namespace App\Infrastructure\Persistence\Eloquent;
use App\Domain\Entities\BusinessHour;
use App\Domain\Repositories\BusinessHourRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
class EloquentBusinessHourRepository implements BusinessHourRepositoryInterface {
    public function updateForBarbershop(string $barbershopId, array $hoursData): void {
        BusinessHour::where('barbershop_id', $barbershopId)->delete();
        if (empty($hoursData)) return;
        DB::table('business_hours')->insert(
            array_map(fn($hour) => array_merge($hour, [
                'id'             => Str::uuid()->toString(),
                'barbershop_id'  => $barbershopId,
            ]), $hoursData)
        );
    }
}
