<?php

namespace App\Application\DTOs;

class CreatePaymentDTO
{
    public function __construct(
        public readonly string $appointmentId,
        public readonly float  $amount,
        public readonly string $clientName,
        public readonly string $clientEmail,
        public readonly string $serviceName,
        public readonly string $barbershopName,
    ) {}
}
