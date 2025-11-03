<?php

namespace App\Infrastructure\Http\Controllers;

use App\Domain\Entities\Barbershop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ProfileController extends Controller
{
    /**
     * Atualiza dados da barbearia
     */
    public function updateBarbershop(Request $request, Barbershop $barbershop)
    {
        if ($barbershop->owner_id !== auth()->id()) {
            return response()->json([
                'message' => 'Você não tem permissão para atualizar esta barbearia.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|string|max:1000',
            'phone' => 'sometimes|string|max:20',
            'address' => 'sometimes|string|max:500',
            'latitude' => 'sometimes|numeric',
            'longitude' => 'sometimes|numeric',
            'logo' => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erro de validação',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->only(['name', 'description', 'phone', 'address', 'latitude', 'longitude']);

        // Upload do logo
        if ($request->hasFile('logo')) {
            // Remove logo antigo
            if ($barbershop->logo_url) {
                $oldPath = str_replace('/storage/', '', $barbershop->logo_url);
                Storage::disk('public')->delete($oldPath);
            }

            $path = $request->file('logo')->store('barbershops', 'public');
            $data['logo_url'] = Storage::url($path);
        }

        $barbershop->update($data);

        return response()->json([
            'message' => 'Barbearia atualizada com sucesso!',
            'data' => $barbershop
        ], 200);
    }

    /**
     * Altera a senha do usuário
     */
    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:6|confirmed'
        ], [
            'current_password.required' => 'Senha atual é obrigatória.',
            'new_password.required' => 'Nova senha é obrigatória.',
            'new_password.min' => 'A nova senha deve ter no mínimo 6 caracteres.',
            'new_password.confirmed' => 'As senhas não coincidem.'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erro de validação',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = auth()->user();

        // Verifica se a senha atual está correta
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'message' => 'Senha atual incorreta.'
            ], 422);
        }

        // Atualiza a senha
        $user->password = Hash::make($request->new_password);
        $user->save();

        return response()->json([
            'message' => 'Senha alterada com sucesso!'
        ], 200);
    }

    /**
     * Atualiza dados do usuário
     */
    public function updateProfile(Request $request)
    {
        $user = auth()->user();

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $user->id,
            'phone' => 'sometimes|string|max:20',
            'profile_image' => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erro de validação',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->only(['name', 'email', 'phone']);

        // Upload da imagem de perfil
        if ($request->hasFile('profile_image')) {
            // Remove imagem antiga
            if ($user->profile_image_url) {
                $oldPath = str_replace('/storage/', '', $user->profile_image_url);
                Storage::disk('public')->delete($oldPath);
            }

            $path = $request->file('profile_image')->store('profiles', 'public');
            $data['profile_image_url'] = Storage::url($path);
        }

        $user->update($data);

        return response()->json([
            'message' => 'Perfil atualizado com sucesso!',
            'data' => $user
        ], 200);
    }
}
