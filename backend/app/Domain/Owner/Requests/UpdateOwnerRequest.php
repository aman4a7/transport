<?php

namespace App\Domain\Owner\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOwnerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'company_name' => ['sometimes', 'string', 'max:200'],
            'contact_person' => ['sometimes', 'string', 'max:100'],
            'phone' => ['sometimes', 'string', 'max:50'],
            'email' => ['sometimes', 'string', 'email', 'max:100', 'unique:owners,email,'.$this->route('owner')],
            'address' => ['nullable', 'string'],
            'status' => ['sometimes', 'string', 'in:active,inactive,suspended'],
        ];
    }
}
