<?php
namespace App\Application\UseCases;

use App\Application\DTOs\CreateBarberDTO;
use App\Application\Services\ImageUploadService;
use App\Domain\Entities\Barber;
use App\Domain\Repositories\BarberRepositoryInterface; // Precisaremos deste Repo

class CreateBarberUseCase
{
    public function __construct(
        private readonly BarberRepositoryInterface $barberRepository,
        private readonly ImageUploadService $imageUploadService
    ) {}

    public function execute(string $barbershopId, CreateBarberDTO $data): Barber
    {
        $imageUrl = $this->imageUploadService->handleBase64Upload(
            $data->profileImage,
            'barber-profiles'
        );

        return $this->barberRepository->createForBarbershop($barbershopId, [
            'name' => $data->name,
            'specialties' => $data->specialties,
            'profile_image_url' => $imageUrl,
        ]);
    }
}
