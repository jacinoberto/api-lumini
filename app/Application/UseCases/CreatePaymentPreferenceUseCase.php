<?php

namespace App\Application\UseCases;

use App\Application\DTOs\CreatePaymentDTO;
use App\Domain\Entities\Payment;
use App\Domain\Repositories\PaymentRepositoryInterface;
use App\Infrastructure\Services\MercadoPagoService;
use Illuminate\Support\Facades\Log;
use MercadoPago\Exceptions\MPApiException;

class CreatePaymentPreferenceUseCase
{
    public function __construct(
        private readonly PaymentRepositoryInterface $paymentRepository,
        private readonly MercadoPagoService         $mercadoPago,
    ) {}

    public function execute(CreatePaymentDTO $dto): Payment
    {
        try {
            $preference = $this->mercadoPago->createPreference([
                'appointment_id'  => $dto->appointmentId,
                'amount'          => $dto->amount,
                'client_name'     => $dto->clientName,
                'client_email'    => $dto->clientEmail,
                'service_name'    => $dto->serviceName,
                'barbershop_name' => $dto->barbershopName,
            ]);
        } catch (MPApiException $e) {
            Log::error('MP createPreference falhou', [
                'status_code' => $e->getStatusCode(),
                'response'    => $e->getApiResponse()->getContent(),
            ]);
            throw $e;
        }

        return $this->paymentRepository->create([
            'appointment_id'     => $dto->appointmentId,
            'amount'             => $dto->amount,
            'status'             => 'pending',
            'mp_preference_id'   => $preference->id,
            'init_point'         => $preference->init_point,
            'sandbox_init_point' => $preference->sandbox_init_point,
        ]);
    }
}
