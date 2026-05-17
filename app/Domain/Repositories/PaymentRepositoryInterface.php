<?php

namespace App\Domain\Repositories;

use App\Domain\Entities\Payment;
use App\Domain\Enums\PaymentStatus;

interface PaymentRepositoryInterface
{
    public function create(array $data): Payment;

    public function findById(string $id): ?Payment;

    public function findByAppointmentId(string $appointmentId): ?Payment;

    public function findByMpPaymentId(string $mpPaymentId): ?Payment;

    public function updateStatus(string $id, PaymentStatus $status, array $extra = []): Payment;
}
