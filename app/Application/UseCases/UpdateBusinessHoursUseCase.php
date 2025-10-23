<?php

namespace App\Application\UseCases;

use App\Domain\Entities\Barbershop;
use App\Domain\Repositories\BusinessHourRepositoryInterface;
use Illuminate\Support\Facades\DB; // Importe a facade DB

class UpdateBusinessHoursUseCase
{
    public function __construct(
        private readonly BusinessHourRepositoryInterface $businessHourRepository
    ) {}

    /**
     * Atualiza os horários de funcionamento de uma barbearia.
     *
     * @param Barbershop $barbershop A barbearia a ser atualizada.
     * @param array $hoursData Array com os dados dos novos horários.
     * @return void
     */
    public function execute(Barbershop $barbershop, array $hoursData): void
    {
        // Usamos transação para garantir atomicidade
        DB::transaction(function () use ($barbershop, $hoursData) {
            // Reutiliza a lógica do repositório que já tínhamos
            $this->businessHourRepository->updateForBarbershop($barbershop->id, $hoursData);
        });
    }
}
