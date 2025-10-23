<?php
namespace App\Application\DTOs;

class UpdateBarberDTO {
    public function __construct(
        public readonly string $name,
        public readonly ?string $profileImage,
        public readonly ?string $specialties
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            name: $data['name'],
            profileImage: $data['profile_image'] ?? null,
            specialties: $data['specialties'] ?? null
        );
    }
}
