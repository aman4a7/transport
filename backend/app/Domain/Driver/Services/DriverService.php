<?php

namespace App\Domain\Driver\Services;

use App\Domain\Driver\Models\Driver;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class DriverService
{
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

        $sortField = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_dir'] ?? 'desc';
        $perPage = $filters['per_page'] ?? 15;

        return $query->orderBy($sortField, $sortOrder)->paginate($perPage);
    }

    public function create(array $data): Driver
    {
        return DB::transaction(function () use ($data): Driver {
            return Driver::create($data);
        });
    }

    public function update(Driver $driver, array $data): Driver
    {
        return DB::transaction(function () use ($driver, $data): Driver {
            $driver->update($data);

            return $driver->fresh();
        });
    }

    public function delete(Driver $driver): void
    {
        $driver->delete();
    }

    public function getWithRelations(Driver $driver): Driver
    {
        return $driver->load(['user', 'assignedVehicle']);
    }
}
