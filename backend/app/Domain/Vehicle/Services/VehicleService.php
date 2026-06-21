<?php

namespace App\Domain\Vehicle\Services;

use App\Domain\Shared\Exceptions\BusinessRuleException;
use App\Domain\Vehicle\Models\Vehicle;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class VehicleService
{
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

        $sortField = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_dir'] ?? 'desc';
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

            return Vehicle::create($data);
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

            $vehicle->update($data);

            return $vehicle->fresh();
        });
    }

    public function delete(Vehicle $vehicle): void
    {
        $vehicle->delete();
    }

    public function getWithRelations(Vehicle $vehicle): Vehicle
    {
        return $vehicle->load(['owner']);
    }
}
