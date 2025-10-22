<?php

namespace App\Policies;

use App\Domain\Entities\Barbershop;
use App\Domain\Entities\User; // <-- GARANTA QUE ESTE IMPORT ESTÁ CORRETO

class BarbershopPolicy
{
    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Barbershop $barbershop): bool
    {
        // A lógica permanece a mesma:
        // Apenas o 'owner_id' da barbearia pode atualizá-la.
        return $user->id === $barbershop->owner_id;
    }
}
