<?php

namespace Database\Factories;

use App\Domain\Driver\Models\Driver;
use App\Domain\Route\Models\Route;
use App\Domain\Trip\Models\Trip;
use App\Domain\Vehicle\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Trip>
 */
class TripFactory extends Factory
{
    protected $model = Trip::class;

    public function definition(): array
    {
        return [
            'route_id' => Route::factory(),
            'vehicle_id' => Vehicle::factory(),
            'driver_id' => Driver::factory(),
            'scheduled_date' => fake()->dateTimeBetween('today', '+1 month')->format('Y-m-d'),
            'departure_time' => fake()->time('H:i'),
            'estimated_arrival_time' => fake()->time('H:i'),
            'status' => 'scheduled',
        ];
    }

    public function inProgress(): static
    {
        return $this->state(fn (array $attrs) => [
            'status' => 'in_progress',
            'actual_departure_time' => now(),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attrs) => [
            'status' => 'completed',
            'actual_departure_time' => now()->subHours(2),
            'actual_arrival_time' => now(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attrs) => [
            'status' => 'cancelled',
        ]);
    }
}
