<?php

namespace Database\Factories;

use App\Domain\Passenger\Models\Passenger;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Passenger>
 */
class PassengerFactory extends Factory
{
    protected $model = Passenger::class;

    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'status' => 'active',
        ];
    }
}
