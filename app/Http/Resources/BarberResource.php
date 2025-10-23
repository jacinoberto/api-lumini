<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BarberResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'profile_image_url' => $this->profile_image_url,
            'specialties' => $this->specialties,
            'is_active' => $this->is_active,
            // Placeholders para dados futuros
            'completed_appointments_count' => 0, // Ou calcular futuramente
            'average_rating' => 0.0, // Ou calcular futuramente
        ];
    }
}
