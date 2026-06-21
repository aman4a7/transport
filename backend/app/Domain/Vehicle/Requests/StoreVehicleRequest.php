<?php

namespace App\Domain\Vehicle\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'plate_number' => ['required', 'string', 'max:20', Rule::unique('vehicles')],
            'make' => ['required', 'string', 'max:100'],
            'model' => ['required', 'string', 'max:100'],
            'year' => ['required', 'integer', 'min:1900', 'max:2100'],
            'category' => ['required', 'string', 'in:defence_plated,contracted_private'],
            'owner_id' => ['nullable', 'integer', 'exists:owners,id'],
            'color' => ['nullable', 'string', 'max:30'],
            'vin' => ['nullable', 'string', 'max:17'],
            'engine_number' => ['nullable', 'string', 'max:50'],
            'seating_capacity' => ['nullable', 'integer', 'min:1'],
            'fuel_type' => ['sometimes', 'string', 'in:diesel,petrol,electric,hybrid'],
            'status' => ['sometimes', 'string', 'in:active,in_maintenance,suspended,decommissioned'],
            'registration_expiry' => ['nullable', 'date'],
            'insurance_expiry' => ['nullable', 'date'],
        ];
    }
}
