<?php

namespace App\Domain\Fuel\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RestockFuelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fuel_type' => ['required', 'string', 'in:diesel,petrol'],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'unit_cost' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
