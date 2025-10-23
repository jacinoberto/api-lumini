<?php

namespace App\Infrastructure\Http\Requests\Barbershop;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBusinessHoursRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Policy será checada no controller
    }

    public function rules(): array
    {
        return [
            'business_hours' => ['required', 'array', 'size:7'],
            'business_hours.*.day_of_week' => ['required', 'integer', 'between:0,6'],
            'business_hours.*.start_time' => ['nullable', 'date_format:H:i'],
            'business_hours.*.end_time' => ['nullable', 'date_format:H:i', 'after_or_equal:business_hours.*.start_time'], // Use after_or_equal
            'business_hours.*.is_active' => ['required', 'boolean'],
        ];
    }
}
