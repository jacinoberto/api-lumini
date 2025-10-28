<?php

namespace App\Infrastructure\Http\Controllers\Auth;

use App\Application\DTOs\LoginDTO;
use App\Application\UseCases\LoginUseCase;
use App\Infrastructure\Http\Controllers\Controller;
use App\Infrastructure\Http\Requests\Auth\LoginRequest; // Verifique se o nome está correto
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
// Remova: use Illuminate\Support\Facades\Auth;

class AuthenticatedSessionController extends Controller
{
    public function __construct(
        private readonly LoginUseCase $loginUseCase
    ) {}

    /**
     * Handle an incoming authentication request (Stateless).
     */
    public function store(LoginRequest $request): JsonResponse
    {
        $loginDTO = LoginDTO::fromRequest($request->validated());
        $user = $this->loginUseCase->execute($loginDTO); // Valida credenciais
        $user->load('barbershop'); // Carrega dados adicionais

        // Gera um Token de API Sanctum
        $token = $user->createToken('auth_token', ['*'], now()->addDays(30))->plainTextToken; // Token com expiração (ex: 30 dias)

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user,
        ]);
    }

    /**
     * Destroy an authenticated session (Stateless - Revoga Token).
     */
    public function destroy(Request $request): Response
    {
        // Obtém o usuário autenticado via token e revoga o token usado na requisição
        if ($request->user()) {
            $request->user()->currentAccessToken()->delete();
        }

        return response()->noContent();
    }
}
