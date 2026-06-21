<?php

namespace App\Domain\Compliance\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreComplianceDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'documentable_type' => ['required', 'string', 'in:App\Domain\Vehicle\Models\Vehicle,App\Domain\Driver\Models\Driver,App\Domain\Owner\Models\Owner'],
            'documentable_id' => ['required', 'integer', 'exists:'.$this->input('documentable_type').',id'],
            'type' => ['required', 'string', 'in:vehicle_registration,insurance,driver_license,contract_document,other'],
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'issued_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:issued_at'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.max' => 'The document must not be larger than 10MB.',
            'file.mimes' => 'The document must be a file of type: pdf, jpg, jpeg, png.',
        ];
    }
}
