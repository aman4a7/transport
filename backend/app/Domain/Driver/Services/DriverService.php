<?php

namespace App\Domain\Driver\Services;

use App\Domain\Driver\Models\Driver;
use App\Domain\Shared\Exceptions\BusinessRuleException;
use App\Domain\Shared\Services\AuditLogService;
use App\Domain\Shared\Support\SafeSort;
use App\Domain\Trip\Models\Trip;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DriverService
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
    ) {}

    public function list(array $filters = []): LengthAwarePaginator
    {
        $query = Driver::query();

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search): void {
                $q->whereHas('user', function ($uq) use ($search): void {
                    $uq->where('name', 'ilike', "%{$search}%");
                })->orWhere('license_number', 'ilike', "%{$search}%");
            });
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['license_category'])) {
            $query->where('license_category', $filters['license_category']);
        }

        $sortField = SafeSort::field(
            ['created_at', 'license_number', 'license_category', 'status'],
            $filters['sort_by'] ?? null,
            'created_at',
        );
        $sortOrder = SafeSort::direction($filters['sort_dir'] ?? null);
        $perPage = $filters['per_page'] ?? 15;

        return $query->orderBy($sortField, $sortOrder)->paginate($perPage);
    }

    public function create(array $data): Driver
    {
        return DB::transaction(function () use ($data): Driver {
            $data['created_by'] = Auth::id();
            $driver = Driver::create($data);

            $this->auditLogService->log('driver_created', $driver, null, null, $driver->toArray());

            return $driver;
        });
    }

    public function update(Driver $driver, array $data): Driver
    {
        return DB::transaction(function () use ($driver, $data): Driver {
            $old = $driver->toArray();
            $data['updated_by'] = Auth::id();
            $driver->update($data);

            $this->auditLogService->log('driver_updated', $driver, null, $old, $driver->fresh()->toArray());

            return $driver->fresh();
        });
    }

    public function delete(Driver $driver): void
    {
        DB::transaction(function () use ($driver): void {
            $hasActiveTrips = Trip::where('driver_id', $driver->id)
                ->whereIn('status', ['scheduled', 'in_progress'])
                ->exists();

            if ($hasActiveTrips) {
                throw new BusinessRuleException(
                    message: 'Driver has active or scheduled trips.',
                    rule: 'driver_has_active_trips',
                );
            }

            $driver->delete();

            $this->auditLogService->log('driver_deleted', $driver);
        });
    }

    public function getWithRelations(Driver $driver): Driver
    {
        return $driver->load(['user', 'assignedVehicle']);
    }
}
