<?php
namespace App\Application\UseCases;

use App\Application\DTOs\UpdateOnboardingProfileDTO;
use App\Application\Services\ImageUploadService;
use App\Domain\Entities\Barbershop;
use App\Domain\Repositories\AddressRepositoryInterface;
use App\Domain\Repositories\BarbershopRepositoryInterface;
use App\Domain\Repositories\BusinessHourRepositoryInterface;
use App\Domain\Repositories\ServiceRepositoryInterface;
use App\Domain\Repositories\ZipCodeRepositoryInterface;
use Illuminate\Support\Facades\DB;

class UpdateOnboardingProfileUseCase
{
    public function __construct(
        private readonly BarbershopRepositoryInterface $barbershopRepository,
        private readonly AddressRepositoryInterface $addressRepository,
        private readonly ZipCodeRepositoryInterface $zipCodeRepository,
        private readonly BusinessHourRepositoryInterface $businessHourRepository,
        private readonly ServiceRepositoryInterface $serviceRepository,
        private readonly ImageUploadService $imageUploadService
    ) {}

    public function execute(Barbershop $barbershop, UpdateOnboardingProfileDTO $data): Barbershop
    {
        return DB::transaction(function () use ($barbershop, $data) {
            $profileUrl = $this->imageUploadService->handleBase64Upload($data->profileImage, 'profiles');
            $coverUrl = $this->imageUploadService->handleBase64Upload($data->coverImage, 'covers');

            $addressData = $data->address;
            $zipCode = $this->zipCodeRepository->findOrCreate(
                ['zip_code' => $addressData['zip_code']],
                $addressData
            );
            $address = $this->addressRepository->createOrUpdate($barbershop->address_id, [
                'zip_code_id' => $zipCode->id,
                'number' => $addressData['number'],
                'complement' => $addressData['complement'] ?? null,
            ]);

            $this->businessHourRepository->updateForBarbershop($barbershop->id, $data->businessHours);
            $this->serviceRepository->createForBarbershop($barbershop->id, $data->services);

            $barbershop->biography = $data->biography;
            $barbershop->address_id = $address->id;
            if ($profileUrl) $barbershop->profile_image_url = $profileUrl;
            if ($coverUrl) $barbershop->cover_image_url = $coverUrl;
            $barbershop->save();

            return $barbershop;
        });
    }
}
