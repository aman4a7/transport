<?php

namespace Database\Factories;

use App\Domain\Passenger\Models\Passenger;
use App\Domain\Trip\Models\Trip;
use App\Domain\Trip\Models\TripAssignment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TripAssignment>
 */
class TripAssignmentFactory extends Factory
{
    protected $model = TripAssignment::class;

    public function definition(): array
    {
        return [
            'trip_id' => Trip::factory(),
            'passenger_id' => Passenger::factory(),
            'status' => 'confirmed',
        ];
    }
}
