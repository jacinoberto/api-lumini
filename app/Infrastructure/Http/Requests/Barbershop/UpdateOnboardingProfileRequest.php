<?php

namespace App\Infrastructure\Http\Requests\Barbershop;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOnboardingProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // A autorização de fato será feita pela Policy no controller.
        // Manter como true aqui permite que a requisição chegue até o controller.
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'biography' => ['nullable', 'string', 'max:1000'],
            'profile_image' => ['nullable', 'string'], // Validamos como string (Base64)
            'cover_image' => ['nullable', 'string'],

            'address' => ['required', 'array'],
            'address.zip_code' => ['required', 'string', 'max:9'],
            'address.street' => ['required', 'string', 'max:255'],
            'address.number' => ['required', 'string', 'max:20'],
            'address.complement' => ['nullable', 'string', 'max:100'],
            'address.area' => ['nullable', 'string', 'max:100'],
            'address.city' => ['required', 'string', 'max:100'],
            'address.state' => ['required', 'string', 'max:2'],

            'business_hours' => ['required', 'array', 'size:7'],
            'business_hours.*.day_of_week' => ['required', 'integer', 'between:0,6'],
            'business_hours.*.start_time' => ['nullable', 'date_format:H:i'],
            'business_hours.*.end_time' => ['nullable', 'date_format:H:i', 'after:business_hours.*.start_time'],
            'business_hours.*.is_active' => ['required', 'boolean'],

            'services' => ['required', 'array', 'min:1'],
            'services.*.name' => ['required', 'string', 'max:100'],
            'services.*.price' => ['required', 'numeric', 'min:0'],
            'services.*.duration_minutes' => ['required', 'integer', 'min:5'],
            'services.*.description' => ['nullable', 'string'],

            'barbers' => ['nullable', 'array'],
            'barbers.*.name' => ['required', 'string', 'max:100'],
            'barbers.*.specialties' => ['nullable', 'string', 'max:255'],
        ];
    }
}
