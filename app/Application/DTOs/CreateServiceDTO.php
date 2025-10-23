<?php
namespace App\Application\DTOs;

class CreateServiceDTO
{
    public function __construct(
        public readonly string $name,
        public readonly float $price,
        public readonly int $durationMinutes,
        public readonly ?string $description
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            name: $data['name'],
            price: (float)$data['price'],
            durationMinutes: (int)$data['duration_minutes'],
            description: $data['description'] ?? null
        );
    }
}
