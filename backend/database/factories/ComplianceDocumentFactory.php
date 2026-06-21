<?php

namespace Database\Factories;

use App\Domain\Compliance\Enums\ComplianceDocumentType;
use App\Domain\Compliance\Enums\ComplianceStatus;
use App\Domain\Compliance\Models\ComplianceDocument;
use App\Domain\Driver\Models\Driver;
use App\Domain\Owner\Models\Owner;
use App\Domain\Vehicle\Models\Vehicle;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ComplianceDocumentFactory extends Factory
{
    protected $model = ComplianceDocument::class;

    public function definition(): array
    {
        $documentableType = $this->faker->randomElement([
            Vehicle::class,
            Driver::class,
            Owner::class,
        ]);
        $documentable = $documentableType::factory()->create();

        $type = $this->faker->randomElement([
            ComplianceDocumentType::VehicleRegistration,
            ComplianceDocumentType::Insurance,
            ComplianceDocumentType::DriverLicense,
            ComplianceDocumentType::ContractDocument,
            ComplianceDocumentType::Other,
        ]);

        return [
            'documentable_type' => $documentableType,
            'documentable_id' => $documentable->id,
            'type' => $type,
            'status' => ComplianceStatus::Pending,
            'file_path' => 'documents/'.fake()->uuid().'.pdf',
            'original_filename' => fake()->word().'.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => fake()->numberBetween(10000, 5000000),
            'issued_at' => now(),
            'expires_at' => now()->addYear(),
            'submitted_by' => User::factory(),
            'reviewed_by' => null,
            'reviewed_at' => null,
            'rejection_reason' => null,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ComplianceStatus::Approved,
            'reviewed_by' => User::factory(),
            'reviewed_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ComplianceStatus::Rejected,
            'reviewed_by' => User::factory(),
            'reviewed_at' => now(),
            'rejection_reason' => fake()->sentence(),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ComplianceStatus::Expired,
            'expires_at' => now()->subDay(),
            'reviewed_by' => User::factory(),
            'reviewed_at' => now(),
        ]);
    }
}
