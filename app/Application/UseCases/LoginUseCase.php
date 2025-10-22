<?php

namespace App\Application\UseCases;

use App\Application\DTOs\LoginDTO;
use App\Domain\Entities\User;
use App\Domain\Repositories\UserRepositoryInterface;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class LoginUseCase
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository
    ) {}

    public function execute(LoginDTO $data): User
    {
        // 1. Busca o usuário pelo e-mail usando o repositório
        $user = $this->userRepository->findByEmail($data->email);

        // 2. Verifica se o usuário existe e se a senha está correta
        if (! $user || ! Hash::check($data->password, $user->password)) {
            // Lança uma exceção de validação padrão do Laravel
            throw ValidationException::withMessages([
                'email' => [__('auth.failed')],
            ]);
        }

        // 3. Retorna o usuário autenticado se as credenciais estiverem corretas
        return $user;
    }
}
