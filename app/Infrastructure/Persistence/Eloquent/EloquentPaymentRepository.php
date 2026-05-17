<?php

namespace App\Infrastructure\Persistence\Eloquent;

use App\Domain\Entities\Payment;
use App\Domain\Enums\PaymentStatus;
use App\Domain\Repositories\PaymentRepositoryInterface;

class EloquentPaymentRepository implements PaymentRepositoryInterface
{
    public function create(array $data): Payment
    {
        return Payment::create($data);
    }

    public function findById(string $id): ?Payment
    {
        return Payment::find($id);
    }

    public function findByAppointmentId(string $appointmentId): ?Payment
    {
        return Payment::where('appointment_id', $appointmentId)->latest()->first();
    }

    public function findByMpPaymentId(string $mpPaymentId): ?Payment
    {
        return Payment::where('mp_payment_id', $mpPaymentId)->first();
    }

    public function updateStatus(string $id, PaymentStatus $status, array $extra = []): Payment
    {
        $payment = Payment::findOrFail($id);
        $payment->update(array_merge(['status' => $status->value], $extra));

        return $payment->fresh();
    }
}
