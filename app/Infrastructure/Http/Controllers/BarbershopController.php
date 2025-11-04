<?php

namespace App\Infrastructure\Http\Controllers;

use App\Application\DTOs\UpdateOnboardingProfileDTO;
use App\Application\UseCases\UpdateBusinessHoursUseCase;
use App\Application\UseCases\UpdateOnboardingProfileUseCase;
use App\Domain\Entities\Appointment;
use App\Domain\Entities\Barbershop;
use App\Domain\Entities\BusinessHour;
use App\Infrastructure\Http\Requests\Barbershop\UpdateBusinessHoursRequest;
use App\Infrastructure\Http\Requests\Barbershop\UpdateOnboardingProfileRequest;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class BarbershopController extends Controller
{
    public function __construct(
        private readonly UpdateOnboardingProfileUseCase $updateOnboardingProfileUseCase,
        private readonly UpdateBusinessHoursUseCase $updateBusinessHoursUseCase
    ) {}

    // ==========================================
    // MÉTODOS DO OWNER (já existentes)
    // ==========================================

    public function update(UpdateOnboardingProfileRequest $request, Barbershop $barbershop): JsonResponse
    {
        $this->authorize('update', $barbershop);

        $dto = UpdateOnboardingProfileDTO::fromRequest($request->validated());

        $this->updateOnboardingProfileUseCase->execute($barbershop, $dto);

        return response()->json([
            'message' => 'Barbershop profile completed successfully.'
        ], Response::HTTP_OK);
    }

    /**
     * Recupera os horários de funcionamento de uma barbearia.
     */
    public function getHours(Barbershop $barbershop): JsonResponse
    {
        $this->authorize('update', $barbershop);

        $barbershop->load(['businessHours' => function ($query) {
            $query->orderBy('day_of_week');
        }]);

        return response()->json($barbershop->businessHours);
    }

    public function updateHours(UpdateBusinessHoursRequest $request, Barbershop $barbershop): JsonResponse
    {
        $this->authorize('update', $barbershop);

        $validatedData = $request->validated()['business_hours'];

        $this->updateBusinessHoursUseCase->execute($barbershop, $validatedData);

        return response()->json([
            'message' => 'Business hours updated successfully.'
        ], Response::HTTP_OK);
    }

    // ==========================================
    // MÉTODOS DO CLIENT (novos)
    // ==========================================

    /**
     * Lista todas as barbearias ativas (CLIENT)
     * GET /api/barbershops
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'category' => 'nullable|string|in:all,nearby,top-rated',
            'latitude' => 'nullable|numeric|required_if:category,nearby',
            'longitude' => 'nullable|numeric|required_if:category,nearby',
            'radius' => 'nullable|numeric|min:1|max:50',
            'search' => 'nullable|string|max:255',
        ]);

        $query = Barbershop::with(['services' => function ($q) {
            $q->where('is_active', true)->orderBy('order')->orderBy('name');
        }, 'barbers' => function ($q) {
            $q->where('is_active', true)->orderBy('order');
        }])
            ->where('is_active', true);

        // Busca por nome
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        // Filtros por categoria
        $category = $request->get('category', 'all');

        switch ($category) {
            case 'nearby':
                if ($request->filled(['latitude', 'longitude'])) {
                    $radius = $request->get('radius', 10);
                    $query->nearby(
                        $request->latitude,
                        $request->longitude,
                        $radius
                    );
                }
                break;

            case 'top-rated':
                $query->where('rating_count', '>', 0)
                    ->orderBy('rating_average', 'desc')
                    ->orderBy('rating_count', 'desc');
                break;

            default: // 'all'
                $query->orderBy('rating_average', 'desc')
                    ->orderBy('name', 'asc');
                break;
        }

        $barbershops = $query->paginate(20);

        // Adiciona informação de favoritos e preço
        $this->enrichBarbershopsWithClientData($barbershops->getCollection());

        return response()->json([
            'success' => true,
            'data' => $barbershops->items(),
            'meta' => [
                'current_page' => $barbershops->currentPage(),
                'last_page' => $barbershops->lastPage(),
                'per_page' => $barbershops->perPage(),
                'total' => $barbershops->total(),
            ]
        ]);
    }

    /**
     * Detalhes de uma barbearia específica (CLIENT)
     * GET /api/barbershops/{id}
     */
    public function show(string $id): JsonResponse
    {
        $barbershop = Barbershop::with([
            'services' => function ($q) {
                $q->where('is_active', true)->orderBy('order')->orderBy('name');
            },
            'barbers' => function ($q) {
                $q->where('is_active', true)->orderBy('order');
            },
            'businessHours' => function ($q) {
                $q->orderBy('day_of_week');
            },
            'reviews' => function ($q) {
                $q->with('client:id,name')
                    ->latest()
                    ->limit(10);
            }
        ])
            ->where('is_active', true)
            ->findOrFail($id);

        // Adiciona informação se o usuário favoritou
        $barbershop->is_favorite = false;
        if (auth()->check()) {
            $barbershop->is_favorite = auth()->user()->hasFavorited($id);
        }

        $barbershop->price_range = $this->calculatePriceRange($barbershop);

        return response()->json([
            'success' => true,
            'data' => $barbershop
        ]);
    }

    /**
     * Busca barbearias (CLIENT)
     * GET /api/barbershops/search
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'query' => 'required|string|max:255',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        $query = Barbershop::where('is_active', true)
            ->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->query . '%');

                // Se tiver relacionamento com address
                if (method_exists(Barbershop::class, 'address')) {
                    $q->orWhereHas('address', function ($subQ) use ($request) {
                        $subQ->where('city', 'like', '%' . $request->query . '%')
                            ->orWhere('neighborhood', 'like', '%' . $request->query . '%');
                    });
                }
            });

        // Se tiver coordenadas, ordena por proximidade
        if ($request->filled(['latitude', 'longitude'])) {
            $query->nearby($request->latitude, $request->longitude, 50);
        } else {
            $query->orderBy('rating_average', 'desc');
        }

        $barbershops = $query->limit(20)->get();

        // Adiciona informação de favoritos e preço
        $this->enrichBarbershopsWithClientData($barbershops);

        return response()->json([
            'success' => true,
            'data' => $barbershops
        ]);
    }

    /**
     * Horários disponíveis de uma barbearia (CLIENT)
     * GET /api/barbershops/{id}/available-slots
     */
    public function availableSlots(Request $request, string $id)
    {
        $request->validate([
            'date' => 'required|date|after_or_equal:today',
            'barber_id' => 'required|uuid|exists:barbers,id'
        ]);

        $barbershop = Barbershop::findOrFail($id);
        $date = Carbon::parse($request->date);
        $barberId = $request->barber_id;
        $dayOfWeek = $date->dayOfWeek;

        // Busca o horário de funcionamento para o dia da semana
        $businessHour = $barbershop->businessHours()
            ->where('day_of_week', $dayOfWeek)
            ->where('is_active', true)
            ->first();

        if (!$businessHour) {
            return response()->json([
                'success' => true,
                'data' => []
            ]);
        }

        // Gera os slots de horário
        $startTime = Carbon::parse($businessHour->start_time);
        $endTime = Carbon::parse($businessHour->end_time);
        $slots = [];
        $slotDuration = 30; // minutos

        $current = $startTime->copy();
        while ($current->lessThan($endTime)) {
            $slots[] = $current->format('H:i');
            $current->addMinutes($slotDuration);
        }

        // Remove horários já ocupados
        $bookedSlots = Appointment::where('barbershop_id', $id)
            ->where('barber_id', $barberId)
            ->whereDate('start_time', $date)
            ->whereIn('status_id', [1, 2]) // 1=PENDING, 2=CONFIRMED (ajuste conforme sua tabela)
            ->get()
            ->map(fn($appointment) => Carbon::parse($appointment->start_time)->format('H:i'))
            ->toArray();

        $availableSlots = array_diff($slots, $bookedSlots);

        return response()->json([
            'success' => true,
            'data' => array_values($availableSlots)
        ]);
    }

    // ==========================================
    // MÉTODOS AUXILIARES (privados)
    // ==========================================

    /**
     * Enriquece coleção de barbearias com dados do cliente
     */
    private function enrichBarbershopsWithClientData($barbershops): void
    {
        if (auth()->check()) {
            $favoriteIds = auth()->user()
                ->favorites()
                ->pluck('barbershop_id')
                ->toArray();

            $barbershops->transform(function ($barbershop) use ($favoriteIds) {
                $barbershop->is_favorite = in_array($barbershop->id, $favoriteIds);
                $barbershop->price_range = $this->calculatePriceRange($barbershop);
                return $barbershop;
            });
        } else {
            $barbershops->transform(function ($barbershop) {
                $barbershop->is_favorite = false;
                $barbershop->price_range = $this->calculatePriceRange($barbershop);
                return $barbershop;
            });
        }
    }

    /**
     * Calcula faixa de preço dos serviços
     */
    private function calculatePriceRange(Barbershop $barbershop): string
    {
        $services = $barbershop->services;

        if ($services->isEmpty()) {
            return '0';
        }

        $minPrice = $services->min('price');
        $maxPrice = $services->max('price');

        if ($minPrice == $maxPrice) {
            return number_format($minPrice, 0, ',', '');
        }

        return number_format($minPrice, 0, ',', '') . '-' . number_format($maxPrice, 0, ',', '');
    }

    /**
     * Gera slots de horário
     */
    private function generateTimeSlots(string $openTime, string $closeTime, int $intervalMinutes): array
    {
        $slots = [];
        $currentTime = strtotime($openTime);
        $closeTime = strtotime($closeTime);


        while ($currentTime < $closeTime) {
            $slots[] = date('H:i', $currentTime);
            $currentTime = strtotime("+{$intervalMinutes} minutes", $currentTime);
        }

        return $slots;
    }

    /**
     * Atualiza um horário de funcionamento específico
     */
    public function updateHour(Request $request, Barbershop $barbershop, $businessHourId)
    {
        $rules['start_time'] = 'sometimes|date_format:H:i|date_format:H:i:s';
        $rules['end_time'] = 'sometimes|date_format:H:i|date_format:H:i:s|after:start_time';

        if ($request->has('start_time')) {
            $request->merge([
                'start_time' => substr($request->start_time, 0, 5)
            ]);
        }

        if ($request->has('end_time')) {
            $request->merge([
                'end_time' => substr($request->end_time, 0, 5)
            ]);
        }

        if ($barbershop->owner_id !== auth()->id()) {
            return response()->json([
                'message' => 'Você não tem permissão para gerenciar horários desta barbearia.'
            ], 403);
        }

        $businessHour = BusinessHour::find($businessHourId);

        if (!$businessHour || $businessHour->barbershop_id !== $barbershop->id) {
            return response()->json([
                'message' => 'Horário de funcionamento não encontrado.'
            ], 404);
        }

        // Validação condicional
        $isActive = $request->has('is_active') ? $request->is_active : $businessHour->is_active;

        $rules = [
            'day_of_week' => 'sometimes|integer|between:0,6',
            'is_active' => 'boolean'
        ];

        if ($isActive) {
            $rules['start_time'] = 'sometimes|date_format:H:i';
            $rules['end_time'] = 'sometimes|date_format:H:i|after:start_time';
        } else {
            $rules['start_time'] = 'sometimes|date_format:H:i';
            $rules['end_time'] = 'sometimes|date_format:H:i';
        }

        $validator = Validator::make($request->all(), $rules, [
            'day_of_week.between' => 'Dia da semana deve estar entre 0 (Domingo) e 6 (Sábado).',
            'end_time.after' => 'Horário de término deve ser posterior ao horário de início.'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erro de validação',
                'errors' => $validator->errors()
            ], 422);
        }

        // Atualiza usando Eloquent (não Query Builder)
        $businessHour->fill($request->only([
            'day_of_week',
            'start_time',
            'end_time',
            'is_active'
        ]));

        $businessHour->save();

        return response()->json([
            'message' => 'Horário de funcionamento atualizado com sucesso!',
            'data' => $businessHour
        ], 200);
    }
}
