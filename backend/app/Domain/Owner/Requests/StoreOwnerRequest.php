<?php

namespace App\Domain\Owner\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOwnerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'company_name' => ['required', 'string', 'max:200'],
            'contact_person' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'max:50'],
            'email' => ['required', 'string', 'email', 'max:100', 'unique:owners,email'],
            'address' => ['nullable', 'string'],
            'status' => ['sometimes', 'string', 'in:active,inactive,suspended'],
        ];
    }
}
