<?php

namespace Database\Factories;

use App\Domain\Fuel\Models\FuelStock;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FuelStock>
 */
class FuelStockFactory extends Factory
{
    protected $model = FuelStock::class;

    public function definition(): array
    {
        return [
            'fuel_type' => fake()->unique()->randomElement(['diesel', 'petrol']),
            'current_quantity' => fake()->randomFloat(2, 500, 5000),
            'minimum_quantity' => fake()->randomFloat(2, 100, 500),
            'created_by' => User::factory(),
            'updated_by' => User::factory(),
        ];
    }

    public function diesel(): static
    {
        return $this->state(fn (array $attrs) => ['fuel_type' => 'diesel']);
    }

    public function petrol(): static
    {
        return $this->state(fn (array $attrs) => ['fuel_type' => 'petrol']);
    }
}
