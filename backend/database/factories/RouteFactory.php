<?php

namespace Database\Factories;

use App\Domain\Route\Models\Route;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Route>
 */
class RouteFactory extends Factory
{
    protected $model = Route::class;

    public function definition(): array
    {
        return [
            'name' => fake()->city().' - '.fake()->city(),
            'code' => strtoupper(fake()->bothify('RTE-###')),
            'origin' => fake()->city(),
            'destination' => fake()->city(),
            'distance_km' => fake()->randomFloat(2, 5, 500),
            'estimated_duration_minutes' => fake()->numberBetween(30, 480),
            'capacity' => fake()->numberBetween(10, 60),
            'status' => 'active',
        ];
    }
}
