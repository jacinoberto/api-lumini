<?php

namespace App\Infrastructure\Http\Controllers;

use App\Application\DTOs\CreateServiceDTO;
use App\Application\DTOs\UpdateServiceDTO;
use App\Application\UseCases\CreateServiceUseCase;
use App\Application\UseCases\UpdateServiceUseCase;
use App\Domain\Entities\Barbershop;
use App\Domain\Entities\Service;
use App\Infrastructure\Http\Requests\Service\CreateServiceRequest;
use App\Infrastructure\Http\Requests\Service\UpdateServiceRequest;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ServiceController extends Controller
{
    // O construtor agora apenas injeta os UseCases
    public function __construct(
        private readonly CreateServiceUseCase $createServiceUseCase,
        private readonly UpdateServiceUseCase $updateServiceUseCase
    ) {}

    /**
     * Lista os serviços de uma barbearia específica.
     */
    public function index(Barbershop $barbershop): JsonResponse
    {
        // Autorização explícita para a ação na barbearia
        $this->authorize('update', $barbershop);

        $services = $barbershop->services()->orderBy('name')->get();
        return response()->json($services);
    }

    /**
     * Cria um novo serviço para a barbearia.
     */
    public function store(CreateServiceRequest $request, Barbershop $barbershop): JsonResponse
    {
        // Autorização explícita
        $this->authorize('update', $barbershop);

        $dto = CreateServiceDTO::fromRequest($request->validated());
        $service = $this->createServiceUseCase->execute($barbershop->id, $dto);

        return response()->json($service, Response::HTTP_CREATED);
    }

    /**
     * Atualiza um serviço existente da barbearia.
     */
    public function update(UpdateServiceRequest $request, Barbershop $barbershop, Service $service): JsonResponse
    {
        // Autorização explícita
        $this->authorize('update', $barbershop);
        // O scoped binding da rota já garante que $service pertence a $barbershop

        $dto = UpdateServiceDTO::fromRequest($request->validated());
        $updatedService = $this->updateServiceUseCase->execute($service, $dto);

        return response()->json($updatedService);
    }
}
