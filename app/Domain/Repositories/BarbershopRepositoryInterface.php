<?php

namespace App\Domain\Repositories;

use App\Domain\Entities\Barbershop;

interface BarbershopRepositoryInterface
{
    public function create(array $data): Barbershop;
}
