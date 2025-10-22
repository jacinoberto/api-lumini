<?php

namespace App\Infrastructure\Http\Controllers\Auth;

use App\Application\DTOs\RegisterUserDTO;
use App\Application\UseCases\RegisterUserUseCase;
use App\Infrastructure\Http\Controllers\Controller;
use App\Infrastructure\Http\Requests\Auth\RegisterRequest;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

// Usando nosso request atualizado

class RegisteredUserController extends Controller
{
    public function __construct(
        private readonly RegisterUserUseCase $registerUserUseCase
    ) {}

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(RegisterRequest $request): JsonResponse
    {
        $registerUserDTO = RegisterUserDTO::fromRequest($request->validated());
        $user = $this->registerUserUseCase->execute($registerUserDTO);
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'User registered successfully.',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                // A barbearia virá aqui se for um OWNER
                'barbershop' => $user->barbershop,
            ]
        ], Response::HTTP_CREATED);
    }
}
