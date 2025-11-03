<?php

namespace App\Infrastructure\Http\Controllers;

use App\Domain\Entities\Appointment;
use App\Domain\Entities\Barbershop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AppointmentController extends Controller
{
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
            ->with(['client', 'barber', 'service', 'status'])
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

        // Carrega relacionamentos
        $appointment->load(['client', 'barber', 'service', 'status']);

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

    public function clientAppointments(Request $request)
    {
        try {
            $user = auth()->user();

            if (!$user) {
                return response()->json([
                    'message' => 'Usuário não autenticado'
                ], 401);
            }

            // Busca apenas agendamentos concluídos (status_id = 3)
            $appointments = Appointment::where('client_id', $user->id)
                ->where('status_id', 3) // Apenas concluídos
                ->with([
                    'barbershop:id,name',
                    'service:id,name,price,duration_minutes',
                    'barber:id,name'
                ])
                ->orderBy('start_time', 'desc')
                ->get()
                ->map(function($appointment) {
                    return [
                        'id' => $appointment->id,
                        'barbershop_id' => $appointment->barbershop_id,
                        'service_id' => $appointment->service_id,
                        'barber_id' => $appointment->barber_id,
                        'start_time' => $appointment->start_time,
                        'barbershop_name' => $appointment->barbershop->name,
                        'service_name' => $appointment->service->name,
                        'service_price' => (float) $appointment->service->price,
                        'service_duration' => $appointment->service->duration_minutes,
                        'barber_name' => $appointment->barber->name,
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
}
