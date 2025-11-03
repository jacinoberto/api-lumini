<?php

namespace App\Infrastructure\Http\Controllers;


use App\Domain\Entities\Appointment;
use App\Domain\Entities\Barbershop;
use App\Domain\Entities\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class ClientAppointmentController extends Controller
{
    /**
     * Lista todos os clientes de uma barbearia
     */
    public function index(Barbershop $barbershop)
    {
        try {
            if ($barbershop->owner_id !== auth()->id()) {
                return response()->json([
                    'message' => 'Você não tem permissão para acessar estes clientes.'
                ], 403);
            }

            // Clientes que fizeram qualquer agendamento
            $clients = DB::table('appointments')
                ->join('users', 'appointments.client_id', '=', 'users.id')
                ->where('appointments.barbershop_id', $barbershop->id)
                ->where('appointments.status_id', 3)
                ->select(
                    'users.id',
                    'users.name',
                    'users.phone',
                    DB::raw('COUNT(appointments.id) as total_appointments'),
                    DB::raw('MAX(appointments.start_time) as last_appointment')
                )
                ->groupBy('users.id', 'users.name', 'users.phone')
                ->orderBy('last_appointment', 'desc')
                ->get()
                ->map(function($client) {
                    return [
                        'id' => $client->id,
                        'name' => $client->name,
                        'phone' => $client->phone,
                        'total_appointments' => (int) $client->total_appointments,
                        'last_appointment' => $client->last_appointment,
                    ];
                });

            return response()->json(['data' => $clients], 200);
        } catch (\Exception $e) {
            \Log::error('Erro ao buscar clientes:', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erro ao buscar clientes'], 500);
        }
    }

    /**
     * Busca um cliente específico com detalhes
     */
    public function show(Barbershop $barbershop, $clientId)
    {
        try {
            if ($barbershop->owner_id !== auth()->id()) {
                return response()->json([
                    'message' => 'Você não tem permissão para acessar este cliente.'
                ], 403);
            }

            // Busca informações do cliente
            $client = DB::table('users')
                ->where('id', $clientId)
                ->first();

            if (!$client) {
                return response()->json([
                    'message' => 'Cliente não encontrado'
                ], 404);
            }

            // Busca estatísticas do cliente
            $stats = DB::table('appointments')
                ->where('barbershop_id', $barbershop->id)
                ->where('client_id', $clientId)
                ->selectRaw('
                    COUNT(*) as total_appointments,
                    SUM(CASE WHEN status_id = 3 THEN 1 ELSE 0 END) as completed_appointments,
                    SUM(CASE WHEN status_id = 4 THEN 1 ELSE 0 END) as cancelled_appointments,
                    MAX(start_time) as last_appointment
                ')
                ->first();

            // Busca últimos agendamentos
            $recentAppointments = DB::table('appointments')
                ->join('services', 'appointments.service_id', '=', 'services.id')
                ->where('appointments.barbershop_id', $barbershop->id)
                ->where('appointments.client_id', $clientId)
                ->select(
                    'appointments.id',
                    'appointments.start_time',
                    'appointments.status_id',
                    'services.name as service_name',
                    'services.price'
                )
                ->orderBy('appointments.start_time', 'desc')
                ->limit(10)
                ->get();

            return response()->json([
                'data' => [
                    'client' => [
                        'id' => $client->id,
                        'name' => $client->name,
                        'phone' => $client->phone,
                    ],
                    'stats' => [
                        'total_appointments' => (int) $stats->total_appointments,
                        'completed_appointments' => (int) $stats->completed_appointments,
                        'cancelled_appointments' => (int) $stats->cancelled_appointments,
                        'last_appointment' => $stats->last_appointment,
                    ],
                    'recent_appointments' => $recentAppointments
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Erro ao buscar detalhes do cliente:', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Erro ao buscar detalhes do cliente',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Busca o histórico de agendamentos de um cliente
     */
    public function appointments(Barbershop $barbershop, User $client)
    {
        if ($barbershop->owner_id !== auth()->id()) {
            return response()->json([
                'message' => 'Você não tem permissão para acessar este histórico.'
            ], 403);
        }

        $appointments = Appointment::where('barbershop_id', $barbershop->id)
            ->where('client_id', $client->id)
            ->with(['service', 'barber', 'status'])
            ->orderBy('start_time', 'desc')
            ->get()
            ->map(function($appointment) {
                return [
                    'id' => $appointment->id,
                    'service_name' => $appointment->service->name ?? 'Serviço',
                    'service_price' => $appointment->service->price ?? 0,
                    'start_time' => $appointment->start_time,
                    'status_id' => $appointment->status_id,
                    'barber_name' => $appointment->barber->name ?? 'Barbeiro'
                ];
            });

        return response()->json([
            'data' => $appointments
        ], 200);
    }

    /**
     * Atualiza as anotações de um cliente
     */
    public function updateNotes(Request $request, Barbershop $barbershop, User $client)
    {
        if ($barbershop->owner_id !== auth()->id()) {
            return response()->json([
                'message' => 'Você não tem permissão para atualizar anotações.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'notes' => 'required|string|max:5000'
        ], [
            'notes.required' => 'Anotações são obrigatórias.',
            'notes.max' => 'Anotações não podem ter mais de 5000 caracteres.'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erro de validação',
                'errors' => $validator->errors()
            ], 422);
        }

        // Verifica se já existe uma anotação
        $existingNote = DB::table('client_notes')
            ->where('barbershop_id', $barbershop->id)
            ->where('client_id', $client->id)
            ->first();

        if ($existingNote) {
            // Atualiza
            DB::table('client_notes')
                ->where('barbershop_id', $barbershop->id)
                ->where('client_id', $client->id)
                ->update([
                    'notes' => $request->notes,
                    'updated_at' => now()
                ]);
        } else {
            // Cria
            DB::table('client_notes')->insert([
                'id' => \Illuminate\Support\Str::uuid(),
                'barbershop_id' => $barbershop->id,
                'client_id' => $client->id,
                'notes' => $request->notes,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        return response()->json([
            'message' => 'Anotações atualizadas com sucesso!',
            'data' => [
                'notes' => $request->notes
            ]
        ], 200);
    }
}
