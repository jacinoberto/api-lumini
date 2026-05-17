<?php

namespace App\Infrastructure\Http\Controllers;

use App\Application\DTOs\CreatePaymentDTO;
use App\Application\UseCases\CreatePaymentPreferenceUseCase;
use App\Application\UseCases\RefundPaymentUseCase;
use App\Domain\Entities\Appointment;
use App\Domain\Repositories\PaymentRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(
        private readonly CreatePaymentPreferenceUseCase $createPreference,
        private readonly RefundPaymentUseCase            $refund,
        private readonly PaymentRepositoryInterface      $paymentRepository,
    ) {}

    public function create(Request $request, string $appointmentId): JsonResponse
    {
        $appointment = Appointment::with(['client', 'service', 'barbershop'])
            ->where('id', $appointmentId)
            ->where('client_id', auth()->id())
            ->firstOrFail();

        $existing = $this->paymentRepository->findByAppointmentId($appointmentId);

        if ($existing?->isApproved()) {
            return response()->json(['message' => 'Este agendamento já está pago.'], 409);
        }

        $dto = new CreatePaymentDTO(
            appointmentId:  $appointment->id,
            amount:         (float) $appointment->price,
            clientName:     $appointment->client->name,
            clientEmail:    $appointment->client->email,
            serviceName:    $appointment->service->name,
            barbershopName: $appointment->barbershop->name,
        );

        $payment = $this->createPreference->execute($dto);

        $checkoutUrl = config('services.mercadopago.is_sandbox')
            ? $payment->sandbox_init_point
            : $payment->init_point;

        return response()->json([
            'data' => [
                'payment_id'   => $payment->id,
                'status'       => $payment->status->value,
                'amount'       => $payment->amount,
                'checkout_url' => $checkoutUrl,
            ],
        ], 201);
    }

    public function status(string $appointmentId): JsonResponse
    {
        $payment = $this->paymentRepository->findByAppointmentId($appointmentId);

        if (!$payment) {
            return response()->json(['message' => 'Nenhum pagamento encontrado para este agendamento.'], 404);
        }

        return response()->json([
            'data' => [
                'payment_id'     => $payment->id,
                'status'         => $payment->status->value,
                'status_label'   => $payment->status->label(),
                'amount'         => $payment->amount,
                'payment_method' => $payment->payment_method,
                'paid_at'        => $payment->paid_at,
                'refunded_at'    => $payment->refunded_at,
            ],
        ]);
    }

    public function refundAppointment(Request $request, string $appointmentId): JsonResponse
    {
        $appointment = Appointment::where('id', $appointmentId)->firstOrFail();

        if ($appointment->barbershop->owner_id !== auth()->id()) {
            return response()->json(['message' => 'Sem permissão para estornar este pagamento.'], 403);
        }

        $this->refund->execute($appointmentId);

        return response()->json(['message' => 'Estorno solicitado com sucesso.']);
    }
}
