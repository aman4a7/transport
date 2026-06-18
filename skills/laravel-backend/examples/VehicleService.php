<?php

namespace App\Domain\Vehicle\Services;

use App\Domain\Vehicle\Models\Vehicle;
use App\Domain\Vehicle\Enums\VehicleCategory;
use App\Domain\Vehicle\Enums\VehicleStatus;
use App\Domain\Vehicle\Events\VehicleCreated;
use App\Domain\Vehicle\Events\VehicleUpdated;
use App\Domain\Vehicle\Events\VehicleDeleted;
use App\Domain\Vehicle\Events\VehicleStatusChanged;
use App\Exceptions\VehicleException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * EXAMPLE: Vehicle service using the laravel-backend skill templates.
 *
 * Key patterns demonstrated:
 * - Typed parameters (no Request access)
 * - DB::transaction for multi-step mutations
 * - Event dispatch for audit/side effects
 * - Pre-condition checks before mutations
 * - Domain-specific exceptions
 * - Enum-based filtering
 */
class VehicleService
{
    /**
     * List vehicles with filtering and pagination.
     *
     * @param array<string, mixed> $filters
     */
    public function list(array $filters = []): LengthAwarePaginator
    {
        $query = Vehicle::query();

        // Text search across plate number and make/model
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('plate_number', 'ilike', "%{$search}%")
                  ->orWhere('make', 'ilike', "%{$search}%")
                  ->orWhere('model', 'ilike', "%{$search}%");
            });
        }

        // Filter by category (defence_plated or contracted_private)
        if (!empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        // Filter by operational status
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Filter by owner (for contracted vehicles)
        if (!empty($filters['owner_id'])) {
            $query->where('owner_id', $filters['owner_id']);
        }

        // Sorting
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDir = $filters['sort_dir'] ?? 'desc';
        $query->orderBy($sortBy, $sortDir);

        return $query->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Create a new vehicle.
     *
     * @param array<string, mixed> $data
     * @throws VehicleException
     */
    public function create(array $data): Vehicle
    {
        return DB::transaction(function () use ($data) {
            // Pre-condition: contracted vehicles must have an owner
            if (
                ($data['category'] ?? '') === VehicleCategory::ContractedPrivate->value
                && empty($data['owner_id'])
            ) {
                throw VehicleException::contractedVehicleRequiresOwner();
            }

            $vehicle = Vehicle::create($data);

            event(new VehicleCreated($vehicle));

            return $vehicle;
        });
    }

    /**
     * Get a single vehicle with its relations loaded.
     */
    public function getWithRelations(Vehicle $vehicle): Vehicle
    {
        return $vehicle->load([
            'owner',
            'documents',
            'currentDriver',
            'activeContract',
        ]);
    }

    /**
     * Update an existing vehicle.
     *
     * @param array<string, mixed> $data
     */
    public function update(Vehicle $vehicle, array $data): Vehicle
    {
        return DB::transaction(function () use ($vehicle, $data) {
            $oldValues = $vehicle->getOriginal();

            $vehicle->update($data);

            event(new VehicleUpdated($vehicle, $oldValues));

            return $vehicle->fresh();
        });
    }

    /**
     * Delete a vehicle.
     *
     * @throws VehicleException
     */
    public function delete(Vehicle $vehicle): void
    {
        DB::transaction(function () use ($vehicle) {
            // Pre-condition: cannot delete if assigned to active trips
            if ($vehicle->activeTrips()->exists()) {
                throw VehicleException::cannotDeleteWithActiveTrips();
            }

            event(new VehicleDeleted($vehicle));

            $vehicle->delete();
        });
    }

    /**
     * Update vehicle operational status with audit reason.
     *
     * @throws VehicleException
     */
    public function updateStatus(Vehicle $vehicle, string $status, ?string $reason = null): Vehicle
    {
        return DB::transaction(function () use ($vehicle, $status, $reason) {
            $oldStatus = $vehicle->status;

            // Pre-condition: validate status transition
            if (!$this->isValidStatusTransition($oldStatus, $status)) {
                throw VehicleException::invalidStatusTransition($oldStatus, $status);
            }

            $vehicle->update([
                'status' => $status,
                'status_changed_at' => now(),
            ]);

            event(new VehicleStatusChanged($vehicle, $oldStatus, $status, $reason));

            return $vehicle->fresh();
        });
    }

    /**
     * Check if a status transition is valid.
     */
    private function isValidStatusTransition(string $from, string $to): bool
    {
        $allowed = [
            VehicleStatus::Active->value => [
                VehicleStatus::InMaintenance->value,
                VehicleStatus::Suspended->value,
                VehicleStatus::Decommissioned->value,
            ],
            VehicleStatus::InMaintenance->value => [
                VehicleStatus::Active->value,
                VehicleStatus::Suspended->value,
            ],
            VehicleStatus::Suspended->value => [
                VehicleStatus::Active->value,
                VehicleStatus::Decommissioned->value,
            ],
            VehicleStatus::Decommissioned->value => [],
        ];

        return in_array($to, $allowed[$from] ?? [], true);
    }
}
