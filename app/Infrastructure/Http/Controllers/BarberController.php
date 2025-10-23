<?php

namespace App\Infrastructure\Http\Controllers;

use App\Application\DTOs\CreateBarberDTO;
use App\Application\DTOs\UpdateBarberDTO;
use App\Application\UseCases\CreateBarberUseCase;
use App\Application\UseCases\UpdateBarberUseCase;
use App\Domain\Entities\Barber;
use App\Domain\Entities\Barbershop;
use App\Http\Resources\BarberResource; // Importe o Resource
use App\Infrastructure\Http\Requests\Barber\CreateBarberRequest;
use App\Infrastructure\Http\Requests\Barber\UpdateBarberRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

// Import Request

class BarberController extends Controller
{
    public function __construct(
        private readonly CreateBarberUseCase $createBarberUseCase,
        private readonly UpdateBarberUseCase $updateBarberUseCase // Adicionar
    ) {}

    /**
     * Lista os barbeiros de uma barbearia específica.
     */
    public function index(Barbershop $barbershop): JsonResponse
    {
        // Autorização explícita (só o dono pode gerenciar)
        $this->authorize('update', $barbershop);

        $barbers = $barbershop->barbers()->where('is_active', true)->orderBy('name')->get();

        // Usa o Resource para formatar a coleção
        return response()->json(BarberResource::collection($barbers));
    }

    public function store(CreateBarberRequest $request, Barbershop $barbershop): JsonResponse
    {
        $this->authorize('update', $barbershop);

        $dto = CreateBarberDTO::fromRequest($request->validated());
        $barber = $this->createBarberUseCase->execute($barbershop->id, $dto);

        // Retorna o barbeiro criado usando o Resource
        return response()->json(new BarberResource($barber), Response::HTTP_CREATED);
    }

    public function update(UpdateBarberRequest $request, Barbershop $barbershop, Barber $barber): JsonResponse
    {
        $this->authorize('update', $barbershop);
        // Scoped binding garante que $barber pertence a $barbershop

        $dto = UpdateBarberDTO::fromRequest($request->validated());
        $updatedBarber = $this->updateBarberUseCase->execute($barber, $dto);

        // Retorna o barbeiro atualizado usando o Resource
        return response()->json(new BarberResource($updatedBarber));
    }
}
