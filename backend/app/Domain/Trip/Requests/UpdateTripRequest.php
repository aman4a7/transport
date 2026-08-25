<?php

namespace App\Domain\Trip\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTripRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'route_id' => ['sometimes', 'integer', 'exists:routes,id'],
            'vehicle_id' => ['sometimes', 'integer', 'exists:vehicles,id'],
            'driver_id' => ['sometimes', 'integer', 'exists:drivers,id'],
            'scheduled_date' => ['sometimes', 'date', 'after_or_equal:today'],
            'departure_time' => ['sometimes', 'date_format:H:i'],
            'estimated_arrival_time' => ['nullable', 'date_format:H:i'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'passenger_ids' => ['nullable', 'array'],
            'passenger_ids.*' => ['integer', 'exists:passengers,id'],
        ];
    }
}
