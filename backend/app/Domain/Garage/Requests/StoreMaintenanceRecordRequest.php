<?php

namespace App\Domain\Garage\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMaintenanceRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'vehicle_id' => ['required', 'integer', 'exists:vehicles,id'],
            'maintenance_type' => ['required', 'string', 'in:scheduled,repair,inspection,other'],
            'description' => ['required', 'string', 'max:2000'],
            'scheduled_date' => ['required', 'date'],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'performed_by' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }
}
