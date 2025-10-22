<?php

namespace App\Infrastructure\Http\Controllers\Auth;

use App\Application\DTOs\LoginDTO;
use App\Application\UseCases\LoginUseCase;
use App\Infrastructure\Http\Controllers\Controller;
use App\Infrastructure\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class AuthenticatedSessionController extends Controller
{
    public function __construct(
        private readonly LoginUseCase $loginUseCase
    ) {}

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): JsonResponse
    {
        // 1. Cria o DTO a partir dos dados validados do request
        $loginDTO = LoginDTO::fromRequest($request->validated());

        // 2. Delega a lógica de autenticação para o UseCase
        $user = $this->loginUseCase->execute($loginDTO);

        // 3. Eager load a barbearia para incluir na resposta
        $user->load('barbershop');

        // 4. Cria o token de API
        $token = $user->createToken('auth_token')->plainTextToken;

        // 5. Retorna a resposta JSON de sucesso
        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user,
        ]);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): Response
    {
        $request->user()->currentAccessToken()->delete();

        return response()->noContent();
    }
}
