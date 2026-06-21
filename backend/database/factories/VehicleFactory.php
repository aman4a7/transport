<?php

namespace Database\Factories;

use App\Domain\Vehicle\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vehicle>
 */
class VehicleFactory extends Factory
{
    protected $model = Vehicle::class;

    public function definition(): array
    {
        return [
            'plate_number' => strtoupper(fake()->bothify('???-####')),
            'make' => fake()->randomElement(['Toyota', 'Isuzu', 'Mitsubishi', 'Hyundai']),
            'model' => fake()->randomElement(['Hiace', 'Fuso', 'Pajero', 'Coaster']),
            'year' => fake()->numberBetween(2010, 2024),
            'category' => 'defence_plated',
            'status' => 'active',
            'fuel_type' => 'diesel',
        ];
    }
}
