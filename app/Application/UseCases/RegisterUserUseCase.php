<?php

namespace App\Application\UseCases;

use App\Application\DTOs\RegisterUserDTO;
use App\Domain\Entities\User;
use App\Domain\Repositories\BarbershopRepositoryInterface;
use App\Domain\Repositories\UserRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class RegisterUserUseCase
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly BarbershopRepositoryInterface $barbershopRepository
    ) {}

    public function execute(RegisterUserDTO $data): User
    {
        // DB::transaction garante que ou tudo é executado com sucesso, ou nada é salvo.
        return DB::transaction(function () use ($data) {
            // 1. Cria o usuário
            $user = $this->userRepository->create([
                'name' => $data->name,
                'email' => $data->email,
                'password' => Hash::make($data->password),
                'role' => $data->role,
            ]);

            // 2. Se o papel for OWNER, cria a barbearia associada
            if ($data->role === 'OWNER') {
                $this->barbershopRepository->create([
                    'owner_id' => $user->id, // Vincula ao usuário recém-criado
                    'name' => $data->barbershopName,
                    'company_code' => $data->companyCode,
                ]);
            }

            // O Eager Loading garante que o objeto User retornado já inclua
            // a barbearia, se ela foi criada.
            return $user->load('barbershop');
        });
    }
}
