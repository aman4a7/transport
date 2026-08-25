<?php

namespace Database\Factories;

use App\Domain\Fuel\Models\FuelTransaction;
use App\Domain\Vehicle\Models\Vehicle;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FuelTransaction>
 */
class FuelTransactionFactory extends Factory
{
    protected $model = FuelTransaction::class;

    public function definition(): array
    {
        return [
            'vehicle_id' => Vehicle::factory(),
            'fuel_type' => fake()->randomElement(['diesel', 'petrol']),
            'quantity' => fake()->randomFloat(2, 10, 100),
            'transaction_type' => 'issue',
            'issued_by' => User::factory(),
            'notes' => fake()->sentence(),
        ];
    }

    public function issue(): static
    {
        return $this->state(fn (array $attrs) => [
            'transaction_type' => 'issue',
            'quantity' => -fake()->randomFloat(2, 10, 100),
        ]);
    }

    public function restock(): static
    {
        return $this->state(fn (array $attrs) => [
            'transaction_type' => 'restock',
            'vehicle_id' => null,
            'quantity' => fake()->randomFloat(2, 100, 1000),
            'unit_cost' => fake()->randomFloat(2, 30, 80),
            'total_cost' => fake()->randomFloat(2, 3000, 80000),
        ]);
    }

    public function adjustment(): static
    {
        return $this->state(fn (array $attrs) => [
            'transaction_type' => 'adjustment',
            'vehicle_id' => null,
            'quantity' => fake()->randomFloat(2, -50, 50),
        ]);
    }
}
