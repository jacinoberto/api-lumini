<?php

namespace App\Application\DTOs;

class RegisterUserDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly string $password,
        public readonly string $role,
        public readonly ?string $barbershopName, // Campo opcional
        public readonly ?string $companyCode    // Campo opcional
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            name: $data['name'],
            email: $data['email'],
            password: $data['password'],
            role: $data['role'],
            barbershopName: $data['barbershop_name'] ?? null,
            companyCode: $data['company_code'] ?? null
        );
    }
}
