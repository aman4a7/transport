<?php

namespace App\Domain\Garage\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMaintenanceRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'vehicle_id' => ['sometimes', 'integer', 'exists:vehicles,id'],
            'maintenance_type' => ['sometimes', 'string', 'in:scheduled,repair,inspection,other'],
            'description' => ['sometimes', 'string', 'max:2000'],
            'scheduled_date' => ['sometimes', 'date'],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'performed_by' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }
}
