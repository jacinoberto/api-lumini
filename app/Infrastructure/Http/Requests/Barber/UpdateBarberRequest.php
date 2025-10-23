<?php
namespace App\Infrastructure\Http\Requests\Barber;
use Illuminate\Foundation\Http\FormRequest;

class UpdateBarberRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array {
        return [
            'name' => ['required', 'string', 'max:255'],
            'profile_image' => ['nullable', 'string'],
            'specialties' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
