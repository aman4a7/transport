<?php

namespace App\Domain\Shared\Support;

class SafeSort
{
    public static function field(array $allowed, mixed $requested, string $default): string
    {
        if (is_string($requested) && in_array($requested, $allowed, true)) {
            return $requested;
        }

        return $default;
    }

    public static function direction(mixed $requested, string $default = 'desc'): string
    {
        $direction = strtolower((string) $requested);

        if (in_array($direction, ['asc', 'desc'], true)) {
            return $direction;
        }

        return $default;
    }
}
