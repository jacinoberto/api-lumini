<?php

namespace App\Infrastructure\Http\Controllers;

use App\Application\DTOs\UpdateOnboardingProfileDTO;
use App\Application\UseCases\UpdateOnboardingProfileUseCase;
use App\Domain\Entities\Barbershop;
use App\Infrastructure\Http\Requests\Barbershop\UpdateOnboardingProfileRequest;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class BarbershopController extends Controller
{
    public function __construct(
        private readonly UpdateOnboardingProfileUseCase $updateOnboardingProfileUseCase
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
}
