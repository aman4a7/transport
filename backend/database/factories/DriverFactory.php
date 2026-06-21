<?php

namespace Database\Factories;

use App\Domain\Driver\Models\Driver;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Driver>
 */
class DriverFactory extends Factory
{
    protected $model = Driver::class;

    public function definition(): array
    {
        return [
            'license_number' => strtoupper(fake()->bothify('LIC-####-??')),
            'license_category' => fake()->randomElement(['light', 'medium', 'heavy', 'trailer']),
            'license_expiry' => fake()->dateTimeBetween('+1 year', '+5 years')->format('Y-m-d'),
            'status' => 'active',
        ];
    }
}
