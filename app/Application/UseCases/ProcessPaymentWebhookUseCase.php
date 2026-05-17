<?php

namespace App\Application\UseCases;

use App\Domain\Entities\AppointmentStatus;
use App\Domain\Enums\PaymentStatus;
use App\Domain\Repositories\PaymentRepositoryInterface;
use App\Infrastructure\Services\MercadoPagoService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessPaymentWebhookUseCase
{
    public function __construct(
        private readonly PaymentRepositoryInterface $paymentRepository,
        private readonly MercadoPagoService         $mercadoPago,
    ) {}

    public function execute(string $mpPaymentId): void
    {
        $mpData = $this->mercadoPago->getPayment($mpPaymentId);

        $payment = $this->paymentRepository->findByMpPaymentId($mpPaymentId)
            ?? $this->paymentRepository->findByAppointmentId($mpData['external_reference'] ?? '');

        if (!$payment) {
            Log::warning('Webhook MP: pagamento não encontrado', ['mp_payment_id' => $mpPaymentId]);
            return;
        }

        // Evita reprocessar status já terminal
        if ($payment->status->isTerminal()) {
            return;
        }

        $newStatus = $this->resolveStatus($mpData['status'] ?? '');

        DB::transaction(function () use ($payment, $newStatus, $mpPaymentId, $mpData) {
            $extra = [
                'mp_payment_id'   => $mpPaymentId,
                'payment_method'  => $mpData['payment_method_id'] ?? null,
                'payment_type'    => $mpData['payment_type_id'] ?? null,
                'gateway_response' => $mpData,
            ];

            if ($newStatus === PaymentStatus::Approved) {
                $extra['paid_at'] = now();
            }

            $this->paymentRepository->updateStatus($payment->id, $newStatus, $extra);

            // Confirma o agendamento automaticamente quando o pagamento é aprovado
            if ($newStatus === PaymentStatus::Approved) {
                $payment->appointment->update(['status_id' => AppointmentStatus::CONFIRMED]);
            }
        });
    }

    private function resolveStatus(string $mpStatus): PaymentStatus
    {
        return match($mpStatus) {
            'approved'        => PaymentStatus::Approved,
            'rejected'        => PaymentStatus::Rejected,
            'cancelled'       => PaymentStatus::Cancelled,
            default           => PaymentStatus::Pending,
        };
    }
}
