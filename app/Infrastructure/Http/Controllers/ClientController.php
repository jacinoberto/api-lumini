<?php

namespace App\Infrastructure\Http\Controllers;


use App\Domain\Entities\Appointment;
use App\Domain\Entities\Barbershop;
use App\Domain\Entities\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class ClientController extends Controller
{
    /**
     * Lista todos os clientes de uma barbearia
     */
    public function index(Request $request, Barbershop $barbershop)
    {
        if ($barbershop->owner_id !== auth()->id()) {
            return response()->json([
                'message' => 'Você não tem permissão para acessar estes clientes.'
            ], 403);
        }

        // Query base
        $query = User::query()
            ->where('role', 'CLIENT')
            ->whereHas('appointments', function($q) use ($barbershop) {
                $q->where('barbershop_id', $barbershop->id);
            })
            ->withCount([
                'appointments as total_appointments' => function($q) use ($barbershop) {
                    $q->where('barbershop_id', $barbershop->id);
                }
            ])
            ->with(['appointments' => function($q) use ($barbershop) {
                $q->where('barbershop_id', $barbershop->id)
                    ->orderBy('start_time', 'desc')
                    ->limit(1);
            }]);

        // Filtro de busca
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $clients = $query->orderBy('name')->get();

        // Adiciona estatísticas
        $clients->map(function($client) use ($barbershop) {
            // Total gasto
            $totalSpent = Appointment::where('barbershop_id', $barbershop->id)
                ->where('client_id', $client->id)
                ->where('status_id', 3) // Apenas agendamentos concluídos
                ->join('services', 'appointments.service_id', '=', 'services.id')
                ->sum('services.price');

            $client->total_spent = $totalSpent;

            return $client;
        });

        return response()->json([
            'data' => $clients
        ], 200);
    }

    /**
     * Busca um cliente específico com detalhes
     */
    public function show(Barbershop $barbershop, User $client)
    {
        if ($barbershop->owner_id !== auth()->id()) {
            return response()->json([
                'message' => 'Você não tem permissão para acessar este cliente.'
            ], 403);
        }

        // Verifica se o usuário é cliente
        if ($client->role !== 'CLIENT') {
            return response()->json([
                'message' => 'Usuário não é um cliente.'
            ], 404);
        }

        // Verifica se o cliente tem agendamentos nesta barbearia
        $hasAppointments = Appointment::where('barbershop_id', $barbershop->id)
            ->where('client_id', $client->id)
            ->exists();

        if (!$hasAppointments) {
            return response()->json([
                'message' => 'Cliente não encontrado nesta barbearia.'
            ], 404);
        }

        // Total de agendamentos
        $totalAppointments = Appointment::where('barbershop_id', $barbershop->id)
            ->where('client_id', $client->id)
            ->count();

        // Total gasto (apenas agendamentos concluídos)
        $totalSpent = Appointment::where('barbershop_id', $barbershop->id)
            ->where('client_id', $client->id)
            ->where('status_id', 3)
            ->join('services', 'appointments.service_id', '=', 'services.id')
            ->sum('services.price');

        // Busca anotações
        $clientNote = DB::table('client_notes')
            ->where('barbershop_id', $barbershop->id)
            ->where('client_id', $client->id)
            ->first();

        $client->total_appointments = $totalAppointments;
        $client->total_spent = $totalSpent;
        $client->notes = $clientNote ? $clientNote->notes : '';

        return response()->json([
            'data' => $client
        ], 200);
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
