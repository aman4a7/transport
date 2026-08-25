<?php

namespace App\Domain\Garage\Services;

use App\Domain\Garage\Enums\MaintenanceStatus;
use App\Domain\Garage\Models\MaintenanceRecord;
use App\Domain\Shared\Exceptions\BusinessRuleException;
use App\Domain\Shared\Services\AuditLogService;
use App\Domain\Shared\Support\SafeSort;
use App\Domain\Trip\Enums\TripStatus;
use App\Domain\Trip\Models\Trip;
use App\Domain\Vehicle\Models\Vehicle;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class GarageService
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
    ) {}

    public function list(array $filters = []): LengthAwarePaginator
    {
        $query = MaintenanceRecord::query()->with(['vehicle:id,plate_number', 'performedBy:id,name']);

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['maintenance_type'])) {
            $query->where('maintenance_type', $filters['maintenance_type']);
        }

        if (! empty($filters['vehicle_id'])) {
            $query->where('vehicle_id', $filters['vehicle_id']);
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('scheduled_date', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('scheduled_date', '<=', $filters['date_to']);
        }

        $sortField = SafeSort::field(
            ['created_at', 'scheduled_date', 'maintenance_type', 'status'],
            $filters['sort_by'] ?? null,
            'scheduled_date',
        );
        $sortOrder = SafeSort::direction($filters['sort_dir'] ?? null);
        $perPage = $filters['per_page'] ?? 15;

        return $query->orderBy($sortField, $sortOrder)->paginate($perPage);
    }

    public function create(array $data): MaintenanceRecord
    {
        return DB::transaction(function () use ($data): MaintenanceRecord {
            $vehicle = Vehicle::findOrFail($data['vehicle_id']);

            $this->assertVehicleEligible($vehicle);

            $data['status'] = MaintenanceStatus::Pending->value;
            $data['created_by'] = Auth::id();

            $record = MaintenanceRecord::create($data);

            $this->auditLogService->log('maintenance_created', $record);

            return $record->fresh()->load(['vehicle:id,plate_number', 'performedBy:id,name']);
        });
    }

    public function update(MaintenanceRecord $record, array $data): MaintenanceRecord
    {
        return DB::transaction(function () use ($record, $data): MaintenanceRecord {
            if (isset($data['vehicle_id']) && $data['vehicle_id'] !== $record->vehicle_id) {
                $this->assertVehicleEligible(Vehicle::findOrFail($data['vehicle_id']));
            }

            $data['updated_by'] = Auth::id();
            $record->update($data);

            $this->auditLogService->log('maintenance_updated', $record);

            return $record->fresh()->load(['vehicle:id,plate_number', 'performedBy:id,name']);
        });
    }

    public function delete(MaintenanceRecord $record): void
    {
        if ($record->status === MaintenanceStatus::InProgress) {
            throw new BusinessRuleException(
                message: 'Cannot delete an in-progress maintenance record.',
                rule: 'maintenance_cannot_delete_in_progress',
            );
        }

        $record->delete();
    }

    public function start(MaintenanceRecord $record): MaintenanceRecord
    {
        return DB::transaction(function () use ($record): MaintenanceRecord {
            if ($record->status !== MaintenanceStatus::Pending) {
                throw new BusinessRuleException(
                    message: 'Only pending maintenance can be started.',
                    rule: 'maintenance_start_invalid_status',
                );
            }

            $this->assertVehicleEligible($record->vehicle, requireActive: true);

            $hasActiveTrips = Trip::where('vehicle_id', $record->vehicle_id)
                ->whereIn('status', [TripStatus::Scheduled->value, TripStatus::InProgress->value])
                ->exists();

            if ($hasActiveTrips) {
                throw new BusinessRuleException(
                    message: 'Cannot start maintenance on a vehicle with active or scheduled trips.',
                    rule: 'maintenance_vehicle_has_active_trips',
                );
            }

            $record->update([
                'status' => MaintenanceStatus::InProgress->value,
                'started_at' => now(),
                'updated_by' => Auth::id(),
            ]);

            $this->auditLogService->log('maintenance_started', $record, null, ['status' => 'pending'], ['status' => 'in_progress', 'started_at' => now()]);

            return $record->fresh()->load(['vehicle:id,plate_number', 'performedBy:id,name']);
        });
    }

    public function complete(MaintenanceRecord $record): MaintenanceRecord
    {
        return DB::transaction(function () use ($record): MaintenanceRecord {
            if ($record->status !== MaintenanceStatus::InProgress) {
                throw new BusinessRuleException(
                    message: 'Only in-progress maintenance can be completed.',
                    rule: 'maintenance_complete_invalid_status',
                );
            }

            $record->update([
                'status' => MaintenanceStatus::Completed->value,
                'completed_at' => now(),
                'updated_by' => Auth::id(),
            ]);

            $this->auditLogService->log('maintenance_completed', $record, null, ['status' => 'in_progress'], ['status' => 'completed', 'completed_at' => now()]);

            return $record->fresh()->load(['vehicle:id,plate_number', 'performedBy:id,name']);
        });
    }

    public function cancel(MaintenanceRecord $record): MaintenanceRecord
    {
        return DB::transaction(function () use ($record): MaintenanceRecord {
            if ($record->status === MaintenanceStatus::Completed || $record->status === MaintenanceStatus::Cancelled) {
                throw new BusinessRuleException(
                    message: 'Cannot cancel a completed or already cancelled maintenance record.',
                    rule: 'maintenance_cancel_invalid_status',
                );
            }

            $record->update([
                'status' => MaintenanceStatus::Cancelled->value,
                'updated_by' => Auth::id(),
            ]);

            $this->auditLogService->log('maintenance_cancelled', $record, null, ['status' => $record->getOriginal('status')->value], ['status' => 'cancelled']);

            return $record->fresh()->load(['vehicle:id,plate_number', 'performedBy:id,name']);
        });
    }

    public function getWithRelations(MaintenanceRecord $record): MaintenanceRecord
    {
        return $record->load(['vehicle', 'performedBy', 'createdBy', 'updatedBy']);
    }

    private function assertVehicleEligible(Vehicle $vehicle, bool $requireActive = false): void
    {
        if ($vehicle->category->value !== 'defence_plated') {
            throw new BusinessRuleException(
                message: 'Only defence-plated vehicles can use garage services.',
                rule: 'garage_vehicle_not_defence_plated',
            );
        }

        if ($requireActive && $vehicle->status->value !== 'active') {
            throw new BusinessRuleException(
                message: "Vehicle {$vehicle->plate_number} is not operational (status: {$vehicle->status->value}).",
                rule: 'garage_vehicle_not_operational',
            );
        }

        if ($vehicle->registration_expiry && $vehicle->registration_expiry->isPast()) {
            throw new BusinessRuleException(
                message: "Vehicle {$vehicle->plate_number} registration has expired.",
                rule: 'garage_vehicle_registration_expired',
            );
        }

        if ($vehicle->insurance_expiry && $vehicle->insurance_expiry->isPast()) {
            throw new BusinessRuleException(
                message: "Vehicle {$vehicle->plate_number} insurance has expired.",
                rule: 'garage_vehicle_insurance_expired',
            );
        }
    }
}
