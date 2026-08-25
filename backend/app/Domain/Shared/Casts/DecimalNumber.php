<?php

namespace App\Domain\Shared\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

class DecimalNumber implements CastsAttributes
{
    public function __construct(protected int $precision = 2) {}

    public function get(Model $model, string $key, mixed $value, array $attributes): ?float
    {
        return $value === null ? null : (float) $value;
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return number_format((float) $value, $this->precision, '.', '');
    }
}
