<?php
namespace App\Application\UseCases;

use App\Application\DTOs\UpdateBarberDTO;
use App\Application\Services\ImageUploadService;
use App\Domain\Entities\Barber;
use App\Domain\Repositories\BarberRepositoryInterface;

class UpdateBarberUseCase
{
    public function __construct(
        private readonly BarberRepositoryInterface $barberRepository,
        private readonly ImageUploadService $imageUploadService
    ) {}

    public function execute(Barber $barber, UpdateBarberDTO $data): Barber
    {
        $updateData = [
            'name' => $data->name,
            'specialties' => $data->specialties,
        ];

        // Processa a imagem apenas se uma nova foi enviada
        if ($data->profileImage) {
            $imageUrl = $this->imageUploadService->handleBase64Upload(
                $data->profileImage,
                'barber-profiles'
            );
            $updateData['profile_image_url'] = $imageUrl;
            // TODO: Opcionalmente, deletar a imagem antiga do storage
        }

        return $this->barberRepository->update($barber, $updateData);
    }
}
