<?php
namespace App\Application\UseCases;

use App\Application\DTOs\CreateServiceDTO;
use App\Domain\Entities\Service;
use App\Domain\Repositories\ServiceRepositoryInterface;

class CreateServiceUseCase
{
    public function __construct(
        private readonly ServiceRepositoryInterface $serviceRepository
    ) {}

    public function execute(string $barbershopId, CreateServiceDTO $data): Service
    {
        return $this->serviceRepository->create([
            'barbershop_id' => $barbershopId,
            'name' => $data->name,
            'price' => $data->price,
            'duration_minutes' => $data->durationMinutes,
            'description' => $data->description,
        ]);
    }
}
