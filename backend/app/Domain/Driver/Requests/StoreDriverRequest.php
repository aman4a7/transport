<?php

namespace App\Domain\Driver\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDriverRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'license_number' => ['required', 'string', 'max:50', 'unique:drivers,license_number'],
            'license_category' => ['required', 'string', 'in:light,medium,heavy,trailer'],
            'license_expiry' => ['required', 'date'],
            'status' => ['sometimes', 'string', 'in:active,suspended,expired,inactive'],
            'medical_expiry' => ['nullable', 'date'],
            'assigned_vehicle_id' => ['nullable', 'integer', 'exists:vehicles,id'],
        ];
    }
}
