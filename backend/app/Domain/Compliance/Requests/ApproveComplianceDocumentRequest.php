<?php

namespace App\Domain\Compliance\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ApproveComplianceDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
