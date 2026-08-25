<?php

namespace App\Domain\Vehicle\Services;

use App\Domain\Contract\Models\Contract;
use App\Domain\Garage\Models\MaintenanceRecord;
use App\Domain\Shared\Exceptions\BusinessRuleException;
use App\Domain\Shared\Services\AuditLogService;
use App\Domain\Shared\Support\SafeSort;
use App\Domain\Trip\Models\Trip;
use App\Domain\Vehicle\Models\Vehicle;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class VehicleService
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
    ) {}

    public function list(array $filters = []): LengthAwarePaginator
    {
        $query = Vehicle::query();

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search): void {
                $q->where('plate_number', 'ilike', "%{$search}%")
                    ->orWhere('make', 'ilike', "%{$search}%")
                    ->orWhere('model', 'ilike', "%{$search}%");
            });
        }

        if (! empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['owner_id'])) {
            $query->where('owner_id', $filters['owner_id']);
        }

        $sortField = SafeSort::field(
            ['created_at', 'plate_number', 'make', 'model', 'year', 'category', 'status'],
            $filters['sort_by'] ?? null,
            'created_at',
        );
        $sortOrder = SafeSort::direction($filters['sort_dir'] ?? null);
        $perPage = $filters['per_page'] ?? 15;

        return $query->orderBy($sortField, $sortOrder)->paginate($perPage);
    }

    public function create(array $data): Vehicle
    {
        return DB::transaction(function () use ($data): Vehicle {
            if (($data['category'] ?? '') === 'contracted_private' && empty($data['owner_id'])) {
                throw new BusinessRuleException(
                    message: 'Contracted private vehicles must have an owner.',
                    rule: 'contracted_private_requires_owner',
                );
            }

            $data['created_by'] = Auth::id();
            $vehicle = Vehicle::create($data);

            $this->auditLogService->log('vehicle_created', $vehicle, null, null, $vehicle->toArray());

            return $vehicle;
        });
    }

    public function update(Vehicle $vehicle, array $data): Vehicle
    {
        return DB::transaction(function () use ($vehicle, $data): Vehicle {
            $category = $data['category'] ?? $vehicle->category->value;
            if ($category === 'contracted_private' && ! isset($data['owner_id']) && ! $vehicle->owner_id) {
                throw new BusinessRuleException(
                    message: 'Contracted private vehicles must have an owner.',
                    rule: 'contracted_private_requires_owner',
                );
            }

            $old = $vehicle->toArray();
            $data['updated_by'] = Auth::id();
            $vehicle->update($data);

            $this->auditLogService->log('vehicle_updated', $vehicle, null, $old, $vehicle->fresh()->toArray());

            return $vehicle->fresh();
        });
    }

    public function delete(Vehicle $vehicle): void
    {
        DB::transaction(function () use ($vehicle): void {
            $hasActiveTrips = Trip::where('vehicle_id', $vehicle->id)
                ->whereIn('status', ['scheduled', 'in_progress'])
                ->exists();

            if ($hasActiveTrips) {
                throw new BusinessRuleException(
                    message: "Vehicle {$vehicle->plate_number} has active or scheduled trips.",
                    rule: 'vehicle_has_active_trips',
                );
            }

            $hasInProgressMaintenance = MaintenanceRecord::where('vehicle_id', $vehicle->id)
                ->where('status', 'in_progress')
                ->exists();

            if ($hasInProgressMaintenance) {
                throw new BusinessRuleException(
                    message: "Vehicle {$vehicle->plate_number} has in-progress maintenance.",
                    rule: 'vehicle_has_in_progress_maintenance',
                );
            }

            $hasActiveContract = Contract::where('vehicle_id', $vehicle->id)
                ->where('status', 'active')
                ->exists();

            if ($hasActiveContract) {
                throw new BusinessRuleException(
                    message: "Vehicle {$vehicle->plate_number} has an active contract.",
                    rule: 'vehicle_has_active_contract',
                );
            }

            $vehicle->delete();

            $this->auditLogService->log('vehicle_deleted', $vehicle);
        });
    }

    public function getWithRelations(Vehicle $vehicle): Vehicle
    {
        return $vehicle->load(['owner']);
    }
}
