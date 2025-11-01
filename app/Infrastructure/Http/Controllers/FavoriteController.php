<?php
// app/Infrastructure/Http/Controllers/FavoriteController.php

namespace App\Infrastructure\Http\Controllers;

use App\Domain\Entities\Barbershop;
use App\Domain\Entities\Favorite;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    /**
     * Lista todos os favoritos do usuário
     * GET /api/client/favorites
     */
    public function index(): JsonResponse
    {
        $user = auth()->user();

        $favorites = $user->favorites()
            ->with(['services' => function ($q) {
                $q->where('is_active', true)->orderBy('order')->orderBy('name');
            }, 'barbers' => function ($q) {
                $q->where('is_active', true)->orderBy('order')->orderBy('name');
            }])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        // Adiciona informações extras
        $favorites->transform(function ($barbershop) {
            $barbershop->is_favorite = true;
            $barbershop->price_range = $this->calculatePriceRange($barbershop);
            return $barbershop;
        });

        return response()->json([
            'success' => true,
            'data' => $favorites
        ]);
    }

    /**
     * Adiciona uma barbearia aos favoritos
     * POST /api/client/favorites
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'barbershop_id' => 'required|uuid|exists:barbershops,id'
        ]);

        $user = auth()->user();
        $barbershopId = $request->barbershop_id;

        // Verifica se a barbearia está ativa
        $barbershop = Barbershop::where('id', $barbershopId)
            ->where('is_active', true)
            ->first();

        if (!$barbershop) {
            return response()->json([
                'success' => false,
                'message' => 'Barbearia não encontrada ou inativa'
            ], 404);
        }

        // Verifica se já está favoritado
        $exists = Favorite::where('user_id', $user->id)
            ->where('barbershop_id', $barbershopId)
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Barbearia já está nos favoritos'
            ], 409);
        }

        // Cria o favorito usando o Model diretamente
        Favorite::create([
            'user_id' => $user->id,
            'barbershop_id' => $barbershopId
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Barbearia adicionada aos favoritos'
        ], 201);
    }

    /**
     * Remove uma barbearia dos favoritos
     * DELETE /api/client/favorites/{barbershop_id}
     */
    public function destroy(string $barbershopId): JsonResponse
    {
        $user = auth()->user();

        // Busca o favorito
        $favorite = Favorite::where('user_id', $user->id)
            ->where('barbershop_id', $barbershopId)
            ->first();

        if (!$favorite) {
            return response()->json([
                'success' => false,
                'message' => 'Barbearia não está nos favoritos'
            ], 404);
        }

        // Remove o favorito
        $favorite->delete();

        return response()->json([
            'success' => true,
            'message' => 'Barbearia removida dos favoritos'
        ]);
    }

    /**
     * Verifica se uma barbearia está favoritada
     * GET /api/client/favorites/{barbershop_id}/check
     */
    public function check(string $barbershopId): JsonResponse
    {
        $user = auth()->user();

        $isFavorite = Favorite::where('user_id', $user->id)
            ->where('barbershop_id', $barbershopId)
            ->exists();

        return response()->json([
            'success' => true,
            'is_favorite' => $isFavorite
        ]);
    }

    /**
     * Helper para calcular faixa de preço
     */
    private function calculatePriceRange($barbershop): string
    {
        $services = $barbershop->services;

        if ($services->isEmpty()) {
            return '0';
        }

        $minPrice = $services->min('price');
        $maxPrice = $services->max('price');

        if ($minPrice == $maxPrice) {
            return number_format($minPrice, 0, ',', '');
        }

        return number_format($minPrice, 0, ',', '') . '-' . number_format($maxPrice, 0, ',', '');
    }
}
