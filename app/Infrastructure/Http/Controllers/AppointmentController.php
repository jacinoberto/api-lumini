<?php

namespace App\Infrastructure\Http\Controllers;

use App\Application\DTOs\CreatePaymentDTO;
use App\Application\UseCases\CreatePaymentPreferenceUseCase;
use App\Domain\Entities\Appointment;
use App\Domain\Entities\Barbershop;
use App\Domain\Repositories\PaymentRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AppointmentController extends Controller
{
    public function __construct(
        private readonly CreatePaymentPreferenceUseCase $createPaymentPreference,
        private readonly PaymentRepositoryInterface     $paymentRepository,
    ) {}

    /**
     * Lista todos os agendamentos de uma barbearia
     */
    public function index(Request $request, Barbershop $barbershop)
    {
        if ($barbershop->owner_id !== auth()->id()) {
            return response()->json([
                'message' => 'Você não tem permissão para acessar estes agendamentos.'
            ], 403);
        }

        $query = $barbershop->appointments()
            ->with([
                'client:id,name,phone',
                'barber:id,name',
                'service:id,name,price,duration_minutes',
                'status:id,status_key,description',
                'payment:id,appointment_id,status,amount,paid_at',
            ])
            ->orderBy('start_time', 'asc');

        // Filtro por data
        if ($request->has('date')) {
            $date = $request->date;
            $query->whereDate('start_time', $date);
        }

        // Filtro por status
        if ($request->has('status_id')) {
            $query->where('status_id', $request->status_id);
        }

        // Filtro por barbeiro
        if ($request->has('barber_id')) {
            $query->where('barber_id', $request->barber_id);
        }

        $appointments = $query->get();

        return response()->json([
            'data' => $appointments
        ], 200);
    }

    public function rateAppointment(Request $request, $id)
    {
        try {
            $user = auth()->user();

            // Busca o agendamento
            $appointment = Appointment::where('id', $id)
                ->where('client_id', $user->id)
                ->where('status_id', 3) // Apenas concluídos podem ser avaliados
                ->firstOrFail();

            // Valida dados
            $validated = $request->validate([
                'rating' => 'required|integer|min:1|max:5',
                'comment' => 'nullable|string|max:500'
            ]);

            // Verifica se já existe avaliação (unique constraint)
            $existingReview = DB::table('reviews')
                ->where('appointment_id', $appointment->id)
                ->first();

            if ($existingReview) {
                return response()->json([
                    'message' => 'Este agendamento já foi avaliado'
                ], 400);
            }

            // Cria a avaliação
            $reviewId = \Illuminate\Support\Str::orderedUuid()->toString();

            DB::table('reviews')->insert([
                'id' => $reviewId,
                'appointment_id' => $appointment->id,
                'barbershop_id' => $appointment->barbershop_id,
                'client_id' => $user->id,
                'rating' => $validated['rating'],
                'comment' => $validated['comment'] ?? null,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // Atualiza média de avaliação da barbearia
            $this->updateBarbershopRating($appointment->barbershop_id);

            return response()->json([
                'message' => 'Avaliação enviada com sucesso!',
                'data' => [
                    'id' => $reviewId,
                    'rating' => $validated['rating'],
                    'comment' => $validated['comment']
                ]
            ], 201);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Agendamento não encontrado ou não pode ser avaliado'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Erro ao salvar avaliação:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Erro ao salvar avaliação',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Busca um agendamento específico
     */
    public function show(Barbershop $barbershop, Appointment $appointment)
    {
        if ($barbershop->owner_id !== auth()->id()) {
            return response()->json([
                'message' => 'Você não tem permissão para acessar este agendamento.'
            ], 403);
        }

        if ($appointment->barbershop_id !== $barbershop->id) {
            return response()->json([
                'message' => 'Agendamento não encontrado nesta barbearia.'
            ], 404);
        }

        $appointment->load(['client:id,name,phone', 'barber:id,name', 'service:id,name,price,duration_minutes', 'status:id,status_key,description', 'payment:id,appointment_id,status,amount,paid_at']);

        return response()->json([
            'data' => $appointment
        ], 200);
    }

    /**
     * Cria um novo agendamento
     */
    public function store(Request $request, Barbershop $barbershop)
    {
        Log::debug($request);

        $validator = Validator::make($request->all(), [
            'client_id' => 'required|uuid|exists:users,id',
            'barber_id' => 'required|uuid|exists:barbers,id',
            'service_id' => 'required|uuid|exists:services,id',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
            'status_id' => 'nullable|integer|exists:appointment_status,id'
        ], [
            'client_id.required' => 'Cliente é obrigatório.',
            'barber_id.required' => 'Barbeiro é obrigatório.',
            'service_id.required' => 'Serviço é obrigatório.',
            'start_time.required' => 'Horário de início é obrigatório.',
            'end_time.required' => 'Horário de término é obrigatório.',
            'end_time.after' => 'Horário de término deve ser após o horário de início.'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erro de validação',
                'errors' => $validator->errors()
            ], 422);
        }

        $appointment = $barbershop->appointments()->create([
            'client_id' => $request->client_id,
            'barber_id' => $request->barber_id,
            'service_id' => $request->service_id,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'status_id' => $request->status_id ?? 1 // 1 = Pendente
        ]);

        $appointment->load(['client', 'barber', 'service', 'status']);

        return response()->json([
            'message' => 'Agendamento criado com sucesso!',
            'data' => $appointment
        ], 201);
    }

    /**
     * Atualiza um agendamento
     */
    public function update(Request $request, Barbershop $barbershop, Appointment $appointment)
    {
        if ($barbershop->owner_id !== auth()->id()) {
            return response()->json([
                'message' => 'Você não tem permissão para atualizar este agendamento.'
            ], 403);
        }

        if ($appointment->barbershop_id !== $barbershop->id) {
            return response()->json([
                'message' => 'Agendamento não encontrado nesta barbearia.'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'client_id' => 'sometimes|uuid|exists:users,id',
            'barber_id' => 'sometimes|uuid|exists:barbers,id',
            'service_id' => 'sometimes|uuid|exists:services,id',
            'start_time' => 'sometimes|date',
            'end_time' => 'sometimes|date|after:start_time',
            'status_id' => 'sometimes|integer|exists:appointment_status,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erro de validação',
                'errors' => $validator->errors()
            ], 422);
        }

        $appointment->update($request->only([
            'client_id',
            'barber_id',
            'service_id',
            'start_time',
            'end_time',
            'status_id'
        ]));

        $appointment->load(['client', 'barber', 'service', 'status']);

        return response()->json([
            'message' => 'Agendamento atualizado com sucesso!',
            'data' => $appointment
        ], 200);
    }

    /**
     * Atualiza apenas o status de um agendamento
     */
    public function updateStatus(Request $request, Barbershop $barbershop, Appointment $appointment)
    {
        if ($barbershop->owner_id !== auth()->id()) {
            return response()->json([
                'message' => 'Você não tem permissão para atualizar este agendamento.'
            ], 403);
        }

        if ($appointment->barbershop_id !== $barbershop->id) {
            return response()->json([
                'message' => 'Agendamento não encontrado nesta barbearia.'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'status_id' => 'required|integer|exists:appointment_status,id'
        ], [
            'status_id.required' => 'Status é obrigatório.',
            'status_id.exists' => 'Status inválido.'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erro de validação',
                'errors' => $validator->errors()
            ], 422);
        }

        $appointment->update([
            'status_id' => $request->status_id
        ]);

        $appointment->load(['client', 'barber', 'service', 'status']);

        return response()->json([
            'message' => 'Status do agendamento atualizado com sucesso!',
            'data' => $appointment
        ], 200);
    }

    /**
     * Deleta um agendamento
     */
    public function destroy(Barbershop $barbershop, Appointment $appointment)
    {
        if ($barbershop->owner_id !== auth()->id()) {
            return response()->json([
                'message' => 'Você não tem permissão para deletar este agendamento.'
            ], 403);
        }

        if ($appointment->barbershop_id !== $barbershop->id) {
            return response()->json([
                'message' => 'Agendamento não encontrado nesta barbearia.'
            ], 404);
        }

        $appointment->delete();

        return response()->json([
            'message' => 'Agendamento deletado com sucesso!'
        ], 200);
    }

    public function clientStore(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'barbershop_id' => 'required|uuid|exists:barbershops,id',
            'barber_id'     => 'required|uuid|exists:barbers,id',
            'service_id'    => 'required|uuid|exists:services,id',
            'start_time'    => 'required|date',
            'end_time'      => 'required|date|after:start_time',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Erro de validação', 'errors' => $validator->errors()], 422);
        }

        $user        = auth()->user();
        $barbershop  = Barbershop::with('owner')->findOrFail($request->barbershop_id);
        $service     = \App\Domain\Entities\Service::findOrFail($request->service_id);

        $appointment = Appointment::create([
            'client_id'     => $user->id,
            'barbershop_id' => $barbershop->id,
            'barber_id'     => $request->barber_id,
            'service_id'    => $request->service_id,
            'start_time'    => $request->start_time,
            'end_time'      => $request->end_time,
            'price'         => $service->price,
        ]);

        $appointment->load(['barber', 'service', 'status']);

        // Se a barbearia exige pré-pagamento, gera preferência no Mercado Pago
        if ($barbershop->requires_prepayment) {
            $payment = $this->createPaymentPreference->execute(new CreatePaymentDTO(
                appointmentId:  $appointment->id,
                amount:         (float) $service->price,
                clientName:     $user->name,
                clientEmail:    $user->email,
                serviceName:    $service->name,
                barbershopName: $barbershop->name,
            ));

            $checkoutUrl = config('services.mercadopago.is_sandbox')
                ? $payment->sandbox_init_point
                : $payment->init_point;

            return response()->json([
                'message' => 'Agendamento criado. Realize o pagamento para confirmar.',
                'data'    => $appointment,
                'payment' => [
                    'required'     => true,
                    'status'       => $payment->status->value,
                    'checkout_url' => $checkoutUrl,
                ],
            ], 201);
        }

        return response()->json([
            'message' => 'Agendamento criado com sucesso!',
            'data'    => $appointment,
            'payment' => ['required' => false],
        ], 201);
    }

    public function clientShow(string $id)
    {
        $appointment = Appointment::where('id', $id)
            ->where('client_id', auth()->id())
            ->with(['barbershop', 'barber', 'service', 'status'])
            ->firstOrFail();

        $payment = $this->paymentRepository->findByAppointmentId($id);

        return response()->json([
            'data'    => $appointment,
            'payment' => $payment ? [
                'status'       => $payment->status->value,
                'status_label' => $payment->status->label(),
                'paid_at'      => $payment->paid_at,
            ] : null,
        ]);
    }

    public function clientDestroy(string $id)
    {
        $appointment = Appointment::where('id', $id)
            ->where('client_id', auth()->id())
            ->firstOrFail();

        if (!$appointment->canBeCancelled()) {
            return response()->json(['message' => 'Este agendamento não pode ser cancelado.'], 422);
        }

        $appointment->cancel(byClient: true);

        return response()->json(['message' => 'Agendamento cancelado com sucesso.']);
    }

    public function clientAppointments(Request $request)
    {
        try {
            $user = auth()->user();

            if (!$user) {
                return response()->json([
                    'message' => 'Usuário não autenticado'
                ], 401);
            }

            $appointments = Appointment::where('client_id', $user->id)
                ->with([
                    'barbershop:id,name',
                    'service:id,name,price,duration_minutes',
                    'barber:id,name',
                    'status:id,status_key,description',
                    'payment:id,appointment_id,status,amount,paid_at,init_point,sandbox_init_point',
                ])
                ->orderBy('start_time', 'desc')
                ->get()
                ->map(function ($appointment) {
                    $payment = $appointment->payment;

                    return [
                        'id'               => $appointment->id,
                        'barbershop_id'    => $appointment->barbershop_id,
                        'service_id'       => $appointment->service_id,
                        'barber_id'        => $appointment->barber_id,
                        'start_time'       => $appointment->start_time,
                        'end_time'         => $appointment->end_time,
                        'status_id'        => $appointment->status_id,
                        'status_key'       => $appointment->status->status_key ?? null,
                        'status_label'     => $appointment->status->description ?? null,
                        'barbershop_name'  => $appointment->barbershop->name,
                        'service_name'     => $appointment->service->name,
                        'service_price'    => (float) $appointment->service->price,
                        'service_duration' => $appointment->service->duration_minutes,
                        'barber_name'      => $appointment->barber->name,
                        'payment'          => $payment ? [
                            'id'             => $payment->id,
                            'appointment_id' => $payment->appointment_id,
                            'status'         => $payment->status instanceof \BackedEnum ? $payment->status->value : $payment->status,
                            'amount'         => $payment->amount,
                            'paid_at'        => $payment->paid_at,
                            'checkout_url'   => config('services.mercadopago.is_sandbox')
                                ? $payment->sandbox_init_point
                                : $payment->init_point,
                        ] : null,
                    ];
                });

            return response()->json([
                'data' => $appointments
            ], 200);

        } catch (\Exception $e) {
            Log::error('Erro ao buscar agendamentos do cliente:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Erro ao buscar agendamentos',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function updateBarbershopRating($barbershopId)
    {
        $stats = DB::table('reviews')
            ->where('barbershop_id', $barbershopId)
            ->selectRaw('AVG(rating) as average, COUNT(*) as count')
            ->first();

        DB::table('barbershops')
            ->where('id', $barbershopId)
            ->update([
                'rating_average' => $stats->average ?? 0,
                'rating_count' => $stats->count ?? 0,
                'updated_at' => now()
            ]);
    }
}
