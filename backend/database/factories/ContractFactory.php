<?php

namespace Database\Factories;

use App\Domain\Contract\Enums\ContractStatus;
use App\Domain\Contract\Models\Contract;
use App\Domain\Owner\Models\Owner;
use App\Domain\Vehicle\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Contract>
 */
class ContractFactory extends Factory
{
    protected $model = Contract::class;

    public function configure(): static
    {
        return $this->afterCreating(function (Contract $contract): void {
            $contract->vehicle()->update(['owner_id' => $contract->owner_id]);
        });
    }

    public function definition(): array
    {
        return [
            'vehicle_id' => Vehicle::factory()->create(['category' => 'contracted_private'])->id,
            'owner_id' => Owner::factory(),
            'contract_number' => 'CNT-'.now()->year.'-'.fake()->unique()->randomNumber(4),
            'start_date' => fake()->dateTimeBetween('-1 year', '-1 month')->format('Y-m-d'),
            'end_date' => fake()->dateTimeBetween('+1 month', '+1 year')->format('Y-m-d'),
            'status' => ContractStatus::Active->value,
            'contract_value' => fake()->optional()->randomFloat(2, 10000, 1000000),
            'payment_terms' => fake()->optional()->sentence(),
            'notes' => fake()->optional()->paragraph(),
        ];
    }

    public function active(): static
    {
        return $this->state(fn (): array => [
            'status' => ContractStatus::Active->value,
            'end_date' => fake()->dateTimeBetween('+1 month', '+1 year')->format('Y-m-d'),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (): array => [
            'status' => ContractStatus::Expired->value,
            'end_date' => fake()->dateTimeBetween('-1 year', '-1 day')->format('Y-m-d'),
        ]);
    }

    public function terminated(): static
    {
        return $this->state(fn (): array => [
            'status' => ContractStatus::Terminated->value,
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (): array => [
            'status' => ContractStatus::Cancelled->value,
        ]);
    }
}
