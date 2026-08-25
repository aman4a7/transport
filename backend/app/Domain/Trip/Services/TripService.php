<?php

namespace App\Domain\Trip\Services;

use App\Domain\Compliance\Enums\ComplianceStatus;
use App\Domain\Compliance\Models\ComplianceDocument;
use App\Domain\Contract\Enums\ContractStatus;
use App\Domain\Contract\Models\Contract;
use App\Domain\Driver\Models\Driver;
use App\Domain\Garage\Enums\MaintenanceStatus;
use App\Domain\Garage\Models\MaintenanceRecord;
use App\Domain\Passenger\Models\Passenger;
use App\Domain\Route\Models\Route;
use App\Domain\Shared\Exceptions\BusinessRuleException;
use App\Domain\Shared\Services\AuditLogService;
use App\Domain\Shared\Support\SafeSort;
use App\Domain\Trip\Enums\TripAssignmentStatus;
use App\Domain\Trip\Enums\TripStatus;
use App\Domain\Trip\Models\Trip;
use App\Domain\Trip\Models\TripAssignment;
use App\Domain\Vehicle\Models\Vehicle;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TripService
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
    ) {}

    public function list(array $filters = [], ?User $user = null): LengthAwarePaginator
    {
        $query = Trip::query()->with(['route:id,name', 'vehicle:id,plate_number', 'driver:id']);

        if ($user !== null && $user->hasRole('passenger')) {
            $passengerId = Passenger::where('user_id', $user->id)->value('id');

            if ($passengerId === null) {
                $query->whereRaw('1 = 0');
            } else {
                $query->whereHas('assignments', fn ($q) => $q->where('passenger_id', $passengerId));
            }
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['route_id'])) {
            $query->where('route_id', $filters['route_id']);
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
            ['created_at', 'scheduled_date', 'departure_time', 'status'],
            $filters['sort_by'] ?? null,
            'scheduled_date',
        );
        $sortOrder = SafeSort::direction($filters['sort_dir'] ?? null);
        $perPage = $filters['per_page'] ?? 15;

        return $query->orderBy($sortField, $sortOrder)->paginate($perPage);
    }

    public function create(array $data): Trip
    {
        return DB::transaction(function () use ($data): Trip {
            $vehicle = Vehicle::findOrFail($data['vehicle_id']);
            $driver = Driver::findOrFail($data['driver_id']);
            $route = Route::findOrFail($data['route_id']);

            $this->validateVehicle($vehicle);
            $this->validateDriver($driver);
            $this->validateRoute($route);

            $passengerIds = $data['passenger_ids'] ?? [];
            $this->validateCapacity($vehicle, $route, $passengerIds);

            if ($vehicle->category->value === 'contracted_private') {
                $this->validateContractorCompliance($vehicle, $driver);
            }

            $data['status'] = TripStatus::Scheduled->value;
            $data['created_by'] = Auth::id();

            $trip = Trip::create($data);

            if (! empty($passengerIds)) {
                $this->assignPassengers($trip, $passengerIds);
            }

            $this->auditLogService->log('trip_created', $trip, null, null, $trip->toArray());

            return $trip->fresh()->load(['route:id,name', 'vehicle:id,plate_number', 'driver:id', 'assignments.passenger:id,first_name,last_name']);
        });
    }

    public function update(Trip $trip, array $data): Trip
    {
        return DB::transaction(function () use ($trip, $data): Trip {
            $passengerIds = $data['passenger_ids'] ?? null;

            unset($data['passenger_ids']);

            $newVehicle = $trip->vehicle;
            if (isset($data['vehicle_id'])) {
                $newVehicle = Vehicle::findOrFail($data['vehicle_id']);
            }

            $newDriver = $trip->driver;
            if (isset($data['driver_id'])) {
                $newDriver = Driver::findOrFail($data['driver_id']);
            }

            $newRoute = $trip->route;
            if (isset($data['route_id'])) {
                $newRoute = Route::findOrFail($data['route_id']);
            }

            $this->validateVehicle($newVehicle);
            $this->validateDriver($newDriver);
            $this->validateRoute($newRoute);

            if ($passengerIds !== null) {
                $this->validateCapacity($newVehicle, $newRoute, $passengerIds);
            }

            if ($newVehicle->category->value === 'contracted_private') {
                $this->validateContractorCompliance($newVehicle, $newDriver);
            }

            $old = $trip->toArray();
            $data['updated_by'] = Auth::id();
            $trip->update($data);

            if ($passengerIds !== null) {
                $trip->assignments()->delete();
                $this->assignPassengers($trip, $passengerIds);
            }

            $this->auditLogService->log('trip_updated', $trip, null, $old, $trip->fresh()->toArray());

            return $trip->fresh()->load(['route:id,name', 'vehicle:id,plate_number', 'driver:id', 'assignments.passenger:id,first_name,last_name']);
        });
    }

    public function delete(Trip $trip): void
    {
        DB::transaction(function () use ($trip): void {
            if ($trip->status === TripStatus::InProgress || $trip->status === TripStatus::Completed) {
                throw new BusinessRuleException(
                    message: 'Cannot delete a trip that is in progress or completed.',
                    rule: 'trip_cannot_delete_active',
                );
            }

            $trip->delete();

            $this->auditLogService->log('trip_deleted', $trip);
        });
    }

    public function getWithRelations(Trip $trip): Trip
    {
        return $trip->load(['route', 'vehicle', 'driver', 'assignments.passenger', 'createdBy', 'updatedBy']);
    }

    public function start(Trip $trip): Trip
    {
        return DB::transaction(function () use ($trip): Trip {
            if ($trip->status !== TripStatus::Scheduled) {
                throw new BusinessRuleException(
                    message: 'Only scheduled trips can be started.',
                    rule: 'trip_start_invalid_status',
                );
            }

            $this->validateVehicle($trip->vehicle);
            $this->validateDriver($trip->driver);
            $this->validateRoute($trip->route);

            if ($trip->vehicle->category->value === 'contracted_private') {
                $this->validateContractorCompliance($trip->vehicle, $trip->driver);
            }

            $trip->update([
                'status' => TripStatus::InProgress->value,
                'actual_departure_time' => now(),
                'updated_by' => Auth::id(),
            ]);

            $this->auditLogService->log('trip_started', $trip, null, ['status' => 'scheduled'], ['status' => 'in_progress', 'actual_departure_time' => now()]);

            return $trip->fresh()->load(['route:id,name', 'vehicle:id,plate_number', 'driver:id']);
        });
    }

    public function complete(Trip $trip): Trip
    {
        return DB::transaction(function () use ($trip): Trip {
            if ($trip->status !== TripStatus::InProgress) {
                throw new BusinessRuleException(
                    message: 'Only in-progress trips can be completed.',
                    rule: 'trip_complete_invalid_status',
                );
            }

            $trip->update([
                'status' => TripStatus::Completed->value,
                'actual_arrival_time' => now(),
                'updated_by' => Auth::id(),
            ]);

            $this->auditLogService->log('trip_completed', $trip, null, ['status' => 'in_progress'], ['status' => 'completed', 'actual_arrival_time' => now()]);

            return $trip->fresh()->load(['route:id,name', 'vehicle:id,plate_number', 'driver:id']);
        });
    }

    public function cancel(Trip $trip): Trip
    {
        return DB::transaction(function () use ($trip): Trip {
            if ($trip->status === TripStatus::Completed || $trip->status === TripStatus::Cancelled) {
                throw new BusinessRuleException(
                    message: 'Cannot cancel a completed or already cancelled trip.',
                    rule: 'trip_cancel_invalid_status',
                );
            }

            $trip->update([
                'status' => TripStatus::Cancelled->value,
                'updated_by' => Auth::id(),
            ]);

            $this->auditLogService->log('trip_cancelled', $trip, null, ['status' => $trip->getOriginal('status')->value], ['status' => 'cancelled']);

            return $trip->fresh()->load(['route:id,name', 'vehicle:id,plate_number', 'driver:id']);
        });
    }

    private function validateVehicle(Vehicle $vehicle): void
    {
        if ($vehicle->status->value !== 'active') {
            throw new BusinessRuleException(
                message: "Vehicle {$vehicle->plate_number} is not operational (status: {$vehicle->status->value}).",
                rule: 'vehicle_not_operational',
            );
        }

        if ($vehicle->registration_expiry && $vehicle->registration_expiry->isPast()) {
            throw new BusinessRuleException(
                message: "Vehicle {$vehicle->plate_number} registration has expired.",
                rule: 'vehicle_registration_expired',
            );
        }

        if ($vehicle->insurance_expiry && $vehicle->insurance_expiry->isPast()) {
            throw new BusinessRuleException(
                message: "Vehicle {$vehicle->plate_number} insurance has expired.",
                rule: 'vehicle_insurance_expired',
            );
        }

        $activeMaintenance = MaintenanceRecord::where('vehicle_id', $vehicle->id)
            ->where('status', MaintenanceStatus::InProgress)
            ->exists();

        if ($activeMaintenance) {
            throw new BusinessRuleException(
                message: "Vehicle {$vehicle->plate_number} is currently under maintenance.",
                rule: 'vehicle_under_maintenance',
            );
        }

        if ($vehicle->category->value === 'contracted_private') {
            $hasActiveContract = Contract::where('vehicle_id', $vehicle->id)
                ->where('status', ContractStatus::Active)
                ->whereDate('end_date', '>=', now())
                ->exists();

            if (! $hasActiveContract) {
                throw new BusinessRuleException(
                    message: "Contracted vehicle {$vehicle->plate_number} has no active contract.",
                    rule: 'vehicle_no_active_contract',
                );
            }
        }
    }

    private function validateDriver(Driver $driver): void
    {
        if ($driver->status->value !== 'active') {
            throw new BusinessRuleException(
                message: 'Driver is not eligible (status: '.$driver->status->value.').',
                rule: 'driver_not_eligible',
            );
        }

        if ($driver->license_expiry && $driver->license_expiry->isPast()) {
            throw new BusinessRuleException(
                message: 'Driver license has expired.',
                rule: 'driver_license_expired',
            );
        }
    }

    private function validateRoute(Route $route): void
    {
        if ($route->status->value !== 'active') {
            throw new BusinessRuleException(
                message: 'Route is not active.',
                rule: 'route_not_active',
            );
        }
    }

    private function validateCapacity(Vehicle $vehicle, Route $route, array $passengerIds): void
    {
        if (empty($passengerIds)) {
            return;
        }

        $count = count($passengerIds);
        $capacity = $vehicle->seating_capacity ?? 0;

        if ($count > $capacity) {
            throw new BusinessRuleException(
                message: 'Passenger count ('.$count.") exceeds vehicle capacity ({$capacity}).",
                rule: 'passenger_capacity_exceeded',
            );
        }

        if ($route->capacity !== null && $count > $route->capacity) {
            throw new BusinessRuleException(
                message: 'Passenger count ('.$count.") exceeds route capacity ({$route->capacity}).",
                rule: 'route_capacity_exceeded',
            );
        }
    }

    private function validateContractorCompliance(Vehicle $vehicle, ?Driver $driver = null): void
    {
        foreach (['vehicle_registration', 'insurance'] as $type) {
            $hasValidDocument = ComplianceDocument::query()
                ->where('documentable_type', Vehicle::class)
                ->where('documentable_id', $vehicle->id)
                ->where('type', $type)
                ->where('status', ComplianceStatus::Approved->value)
                ->whereNotNull('expires_at')
                ->where('expires_at', '>', now())
                ->exists();

            if (! $hasValidDocument) {
                throw new BusinessRuleException(
                    message: "Contracted vehicle {$vehicle->plate_number} has no valid {$type} compliance document.",
                    rule: 'contractor_compliance_missing',
                );
            }
        }

        if ($driver !== null) {
            $driverHasValidLicense = ComplianceDocument::query()
                ->where('documentable_type', Driver::class)
                ->where('documentable_id', $driver->id)
                ->where('type', 'driver_license')
                ->where('status', ComplianceStatus::Approved->value)
                ->whereNotNull('expires_at')
                ->where('expires_at', '>', now())
                ->exists();

            if (! $driverHasValidLicense) {
                throw new BusinessRuleException(
                    message: 'Driver has no valid license compliance document.',
                    rule: 'driver_license_compliance_missing',
                );
            }
        }
    }

    private function assignPassengers(Trip $trip, array $passengerIds): void
    {
        foreach ($passengerIds as $passengerId) {
            TripAssignment::create([
                'trip_id' => $trip->id,
                'passenger_id' => $passengerId,
                'status' => TripAssignmentStatus::Confirmed->value,
            ]);
        }
    }
}
