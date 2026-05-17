<?php

namespace App\Application\UseCases;

use App\Domain\Enums\PaymentStatus;
use App\Domain\Repositories\PaymentRepositoryInterface;
use App\Infrastructure\Services\MercadoPagoService;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class RefundPaymentUseCase
{
    public function __construct(
        private readonly PaymentRepositoryInterface $paymentRepository,
        private readonly MercadoPagoService         $mercadoPago,
    ) {}

    public function execute(string $appointmentId): void
    {
        $payment = $this->paymentRepository->findByAppointmentId($appointmentId);

        if (!$payment || !$payment->canBeRefunded()) {
            return;
        }

        try {
            $this->mercadoPago->refund($payment->mp_payment_id);

            $this->paymentRepository->updateStatus($payment->id, PaymentStatus::Refunded, [
                'refunded_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Erro ao estornar pagamento MP', [
                'appointment_id' => $appointmentId,
                'mp_payment_id'  => $payment->mp_payment_id,
                'error'          => $e->getMessage(),
            ]);

            throw new RuntimeException('Falha ao processar o estorno. Tente novamente ou contate o suporte.');
        }
    }
}
