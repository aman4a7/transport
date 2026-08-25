<?php

namespace App\Domain\Fuel\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdjustFuelStockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fuel_type' => ['required', 'string', 'in:diesel,petrol'],
            'quantity' => ['required', 'numeric'],
            'notes' => ['required', 'string', 'min:10', 'max:1000'],
        ];
    }
}
