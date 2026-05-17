<?php

namespace App\Infrastructure\Http\Controllers;

use App\Domain\Entities\Appointment;
use App\Domain\Entities\Barbershop;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    /**
     * Retorna estatísticas do dashboard
     */
    public function stats(Barbershop $barbershop)
    {
        try {
            if ($barbershop->owner_id !== auth()->id()) {
                return response()->json([
                    'message' => 'Você não tem permissão para acessar estas estatísticas.'
                ], 403);
            }

            $today          = Carbon::today();
            $startOfMonth   = Carbon::now()->startOfMonth();
            $endOfMonth     = Carbon::now()->endOfMonth();

            $stats = DB::selectOne("
                SELECT
                    COUNT(CASE WHEN a.start_time >= ? AND a.start_time < ? THEN 1 END) AS today_appointments,
                    COALESCE(SUM(CASE WHEN p.status = 'approved' AND p.paid_at BETWEEN ? AND ? THEN p.amount END), 0) AS monthly_revenue
                FROM appointments a
                LEFT JOIN payments p ON p.appointment_id = a.id
                WHERE a.barbershop_id = ?
            ", [
                $today->toDateTimeString(),
                $today->copy()->addDay()->toDateTimeString(),
                $startOfMonth->toDateTimeString(),
                $endOfMonth->toDateTimeString(),
                $barbershop->id,
            ]);

            $todayAppointments = (int) ($stats->today_appointments ?? 0);
            $monthlyRevenue    = (float) ($stats->monthly_revenue ?? 0);
            $averageRating     = (float) ($barbershop->rating_average ?? 0);
            $ratingCount       = (int) ($barbershop->rating_count ?? 0);

            return response()->json([
                'data' => [
                    'today_appointments' => (int) $todayAppointments,
                    'monthly_revenue' => (float) ($monthlyRevenue ?? 0),
                    'average_rating' => (float) $averageRating,
                    'rating_count' => (int) $ratingCount
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Erro no dashboard stats:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Erro ao carregar estatísticas',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Retorna agendamentos de hoje
     */
    public function todayAppointments(Barbershop $barbershop)
    {
        try {
            if ($barbershop->owner_id !== auth()->id()) {
                return response()->json([
                    'message' => 'Você não tem permissão para acessar estes agendamentos.'
                ], 403);
            }

            $today = Carbon::today();

            $appointments = Appointment::where('barbershop_id', $barbershop->id)
                ->whereDate('start_time', $today)
                ->with(['client', 'service', 'barber'])
                ->orderBy('start_time', 'asc')
                ->get()
                ->map(function($appointment) {
                    return [
                        'id' => $appointment->id,
                        'time' => Carbon::parse($appointment->start_time)->format('H:i'),
                        'client_name' => $appointment->client->name ?? 'Cliente',
                        'service_name' => $appointment->service->name ?? 'Serviço',
                        'barber_name' => $appointment->barber->name ?? 'Barbeiro',
                        'status_id' => $appointment->status_id
                    ];
                });

            return response()->json([
                'data' => $appointments
            ], 200);

        } catch (\Exception $e) {
            Log::error('Erro ao buscar agendamentos de hoje:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Erro ao carregar agendamentos',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
