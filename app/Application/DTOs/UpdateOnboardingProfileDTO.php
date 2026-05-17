<?php
namespace App\Application\DTOs;

class UpdateOnboardingProfileDTO
{
    public function __construct(
        public readonly ?string $biography,
        public readonly ?string $profileImage,
        public readonly ?string $coverImage,
        public readonly array $address,
        public readonly array $businessHours,
        public readonly array $services,
        public readonly array $barbers,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            biography: $data['biography'] ?? null,
            profileImage: $data['profile_image'] ?? null,
            coverImage: $data['cover_image'] ?? null,
            address: $data['address'],
            businessHours: $data['business_hours'],
            services: $data['services'],
            barbers: $data['barbers'] ?? [],
        );
    }
}
