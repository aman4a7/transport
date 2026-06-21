<?php

namespace App\Domain\Compliance\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RejectComplianceDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'min:10', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'reason.required' => 'A rejection reason is required.',
            'reason.min' => 'The rejection reason must be at least 10 characters.',
        ];
    }
}
