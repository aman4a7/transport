<?php

namespace Database\Factories;

use App\Domain\Notification\Enums\NotificationType;
use App\Domain\Notification\Models\Notification;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Notification>
 */
class NotificationFactory extends Factory
{
    protected $model = Notification::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'type' => fake()->randomElement(NotificationType::cases())->value,
            'title' => fake()->sentence(4),
            'body' => fake()->optional()->sentence(),
            'data' => [
                'entity_type' => fake()->randomElement(['vehicle', 'driver', 'contract', 'maintenance', 'fuel_stock']),
                'entity_id' => fake()->randomNumber(4),
            ],
            'read_at' => null,
        ];
    }

    public function unread(): static
    {
        return $this->state(fn (): array => ['read_at' => null]);
    }

    public function read(): static
    {
        return $this->state(fn (): array => ['read_at' => now()]);
    }

    public function complianceExpiring(): static
    {
        return $this->state(fn (): array => [
            'type' => NotificationType::ComplianceExpiring->value,
        ]);
    }

    public function contractExpiring(): static
    {
        return $this->state(fn (): array => [
            'type' => NotificationType::ContractExpiring->value,
        ]);
    }

    public function maintenanceDue(): static
    {
        return $this->state(fn (): array => [
            'type' => NotificationType::MaintenanceDue->value,
        ]);
    }

    public function fuelLowStock(): static
    {
        return $this->state(fn (): array => [
            'type' => NotificationType::FuelLowStock->value,
        ]);
    }
}
