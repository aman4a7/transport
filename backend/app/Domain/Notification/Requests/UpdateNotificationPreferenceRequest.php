<?php

namespace App\Domain\Notification\Requests;

use App\Domain\Notification\Enums\NotificationType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateNotificationPreferenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'preferences' => ['required', 'array'],
            'preferences.*' => ['boolean'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $allowed = collect(NotificationType::cases())
                    ->map(fn (NotificationType $type): string => $type->value)
                    ->all();

                foreach (array_keys($this->input('preferences', [])) as $key) {
                    if (! in_array($key, $allowed, true)) {
                        $validator->errors()->add(
                            "preferences.{$key}",
                            "Unsupported notification type: {$key}.",
                        );
                    }
                }
            },
        ];
    }
}
