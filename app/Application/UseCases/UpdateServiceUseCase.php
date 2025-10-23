<?php
namespace App\Application\UseCases;

use App\Application\DTOs\UpdateServiceDTO;
use App\Domain\Entities\Service;
use App\Domain\Repositories\ServiceRepositoryInterface;

class UpdateServiceUseCase
{
    public function __construct(
        private readonly ServiceRepositoryInterface $serviceRepository
    ) {}

    public function execute(Service $service, UpdateServiceDTO $data): Service
    {
        return $this->serviceRepository->update($service, [
            'name' => $data->name,
            'price' => $data->price,
            'duration_minutes' => $data->durationMinutes,
            'description' => $data->description,
        ]);
    }
}
