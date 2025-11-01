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
     * Busca um serviço específico
     */
    public function show(Barbershop $barbershop, Service $service)
    {
        // Verifica se o usuário é o dono da barbearia
        if ($barbershop->owner_id !== auth()->id()) {
            return response()->json([
                'message' => 'Você não tem permissão para acessar este serviço.'
            ], 403);
        }

        // Verifica se o serviço pertence à barbearia
        if ($service->barbershop_id !== $barbershop->id) {
            return response()->json([
                'message' => 'Serviço não encontrado nesta barbearia.'
            ], 404);
        }

        return response()->json([
            'data' => $service
        ], 200);
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

    /**
     * Deleta um serviço
     */
    public function destroy(Barbershop $barbershop, Service $service)
    {
        // Verifica se o usuário é o dono da barbearia
        if ($barbershop->owner_id !== auth()->id()) {
            return response()->json([
                'message' => 'Você não tem permissão para deletar este serviço.'
            ], 403);
        }

        // Verifica se o serviço pertence à barbearia
        if ($service->barbershop_id !== $barbershop->id) {
            return response()->json([
                'message' => 'Serviço não encontrado nesta barbearia.'
            ], 404);
        }

        // Verifica se existem agendamentos futuros com este serviço
        // CORRIGIDO: usando 'start_time' e 'status_id'
        $futureAppointments = $service->appointments()
            ->where('start_time', '>=', now())
            ->whereIn('status_id', [1, 2]) // 1=pending, 2=confirmed (ajuste conforme sua tabela)
            ->count();

        if ($futureAppointments > 0) {
            return response()->json([
                'message' => 'Não é possível deletar este serviço pois existem agendamentos futuros associados a ele. Cancele os agendamentos primeiro ou desative o serviço.'
            ], 422);
        }

        // Deleta o serviço
        $service->delete();

        return response()->json([
            'message' => 'Serviço deletado com sucesso!'
        ], 200);
    }
}
