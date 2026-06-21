<?php

namespace App\Domain\Passenger\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePassengerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'employee_id' => ['nullable', 'string', 'max:50', 'unique:passengers,employee_id'],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:100', 'unique:passengers,email'],
            'phone' => ['nullable', 'string', 'max:50'],
            'department' => ['nullable', 'string', 'max:100'],
            'status' => ['sometimes', 'string', 'in:active,inactive'],
        ];
    }
}
