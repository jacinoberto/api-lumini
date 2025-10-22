<?php

namespace App\Infrastructure\Persistence\Eloquent;

use App\Domain\Entities\Barbershop;
use App\Domain\Repositories\BarbershopRepositoryInterface;

class EloquentBarbershopRepository implements BarbershopRepositoryInterface
{
    public function create(array $data): Barbershop
    {
        return Barbershop::create($data);
    }
}
