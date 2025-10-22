<?php

namespace App\Infrastructure\Http\Requests\Auth;

use App\Domain\Entities\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Para um endpoint de registro público, a autorização é sempre permitida.
        return true;
    }

    public function rules(): array
    {
        return [
            // Regras do Usuário (permanecem as mesmas)
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' => ['required', 'string', Rule::in(['CLIENT', 'OWNER'])],

            // Regras da Barbearia (condicionais)
            'barbershop_name' => ['required_if:role,OWNER', 'string', 'max:255'],
            'company_code' => ['nullable', 'string', 'max:18'], // CNPJ é opcional no seu form
        ];
    }
}
