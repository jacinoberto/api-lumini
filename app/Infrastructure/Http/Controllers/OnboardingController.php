<?php

namespace App\Infrastructure\Http\Controllers;

use App\Domain\Entities\Barbershop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class OnboardingController extends Controller
{
    /**
     * Completa o onboarding da barbearia
     */
    public function complete(Request $request)
    {
        $user = auth()->user();

        // Verifica se o usuário é OWNER
        if ($user->role !== 'OWNER') {
            return response()->json([
                'message' => 'Apenas donos de barbearia podem completar o onboarding.'
            ], 403);
        }

        // Verifica se já tem uma barbearia
        if ($user->barbershops()->exists()) {
            return response()->json([
                'message' => 'Você já possui uma barbearia cadastrada.'
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'phone' => 'required|string|max:20',
            'address' => 'required|string|max:500',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ], [
            'name.required' => 'Nome da barbearia é obrigatório.',
            'phone.required' => 'Telefone é obrigatório.',
            'address.required' => 'Endereço é obrigatório.'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erro de validação',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = [
            'owner_id' => $user->id,
            'name' => $request->name,
            'description' => $request->description,
            'phone' => $request->phone,
            'address' => $request->address,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'is_active' => true
        ];

        // Upload do logo
        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('barbershops', 'public');
            $data['logo_url'] = Storage::url($path);
        }

        $barbershop = Barbershop::create($data);

        return response()->json([
            'message' => 'Barbearia criada com sucesso!',
            'data' => $barbershop
        ], 201);
    }

    /**
     * Verifica se o onboarding foi completado
     */
    public function checkStatus()
    {
        $user = auth()->user();

        if ($user->role !== 'OWNER') {
            return response()->json([
                'completed' => true // Clientes não precisam de onboarding
            ], 200);
        }

        $hasBarbershop = $user->barbershops()->exists();

        return response()->json([
            'completed' => $hasBarbershop,
            'barbershop' => $hasBarbershop ? $user->barbershops()->first() : null
        ], 200);
    }
}
