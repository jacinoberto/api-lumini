<?php

namespace App\Infrastructure\Http\Controllers;

use App\Application\DTOs\UpdateOnboardingProfileDTO;
use App\Application\UseCases\UpdateBusinessHoursUseCase;
use App\Application\UseCases\UpdateOnboardingProfileUseCase;
use App\Domain\Entities\Barbershop;
use App\Infrastructure\Http\Requests\Barbershop\UpdateBusinessHoursRequest;
use App\Infrastructure\Http\Requests\Barbershop\UpdateOnboardingProfileRequest;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class BarbershopController extends Controller
{
    public function __construct(
        private readonly UpdateOnboardingProfileUseCase $updateOnboardingProfileUseCase,
        private readonly UpdateBusinessHoursUseCase $updateBusinessHoursUseCase
    ) {}

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
        // 1. Autorização: Reutilizamos a policy 'update', pois só o dono pode ver/editar.
        $this->authorize('update', $barbershop);

        // 2. Carrega o relacionamento (Eager Loading)
        // Ordenamos por 'day_of_week' para garantir a ordem Domingo -> Sábado na resposta.
        $barbershop->load(['businessHours' => function ($query) {
            $query->orderBy('day_of_week');
        }]);

        // 3. Retorna os dados
        return response()->json($barbershop->businessHours);
    }

    public function updateHours(UpdateBusinessHoursRequest $request, Barbershop $barbershop): JsonResponse
    {
        // 1. Autorização
        $this->authorize('update', $barbershop);

        // 2. Extrai os dados validados (o array 'business_hours')
        $validatedData = $request->validated()['business_hours'];

        // 3. Executa o UseCase
        $this->updateBusinessHoursUseCase->execute($barbershop, $validatedData);

        // 4. Retorna resposta de sucesso
        return response()->json([
            'message' => 'Business hours updated successfully.'
        ], Response::HTTP_OK);
    }
}
