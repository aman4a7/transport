<?php

namespace Database\Factories;

use App\Domain\Garage\Enums\MaintenanceStatus;
use App\Domain\Garage\Enums\MaintenanceType;
use App\Domain\Garage\Models\MaintenanceRecord;
use App\Domain\Vehicle\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MaintenanceRecord>
 */
class MaintenanceRecordFactory extends Factory
{
    protected $model = MaintenanceRecord::class;

    public function definition(): array
    {
        return [
            'vehicle_id' => Vehicle::factory(),
            'maintenance_type' => fake()->randomElement(MaintenanceType::cases())->value,
            'status' => MaintenanceStatus::Pending->value,
            'description' => fake()->sentence(),
            'scheduled_date' => fake()->date(),
            'cost' => fake()->optional()->randomFloat(2, 100, 10000),
            'notes' => fake()->optional()->paragraph(),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (): array => [
            'status' => MaintenanceStatus::Pending->value,
        ]);
    }

    public function inProgress(): static
    {
        return $this->state(fn (): array => [
            'status' => MaintenanceStatus::InProgress->value,
            'started_at' => now(),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (): array => [
            'status' => MaintenanceStatus::Completed->value,
            'started_at' => now()->subDay(),
            'completed_at' => now(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (): array => [
            'status' => MaintenanceStatus::Cancelled->value,
        ]);
    }
}
