<?php

namespace App\Domain\Passenger\Services;

use App\Domain\Passenger\Models\Passenger;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class PassengerService
{
    public function list(array $filters = []): LengthAwarePaginator
    {
        $query = Passenger::query();

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search): void {
                $q->where('first_name', 'ilike', "%{$search}%")
                    ->orWhere('last_name', 'ilike', "%{$search}%")
                    ->orWhere('email', 'ilike', "%{$search}%")
                    ->orWhere('employee_id', 'ilike', "%{$search}%");
            });
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['department'])) {
            $query->where('department', 'ilike', "%{$filters['department']}%");
        }

        $sortField = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_dir'] ?? 'desc';
        $perPage = $filters['per_page'] ?? 15;

        return $query->orderBy($sortField, $sortOrder)->paginate($perPage);
    }

    public function create(array $data): Passenger
    {
        return DB::transaction(function () use ($data): Passenger {
            return Passenger::create($data);
        });
    }

    public function update(Passenger $passenger, array $data): Passenger
    {
        return DB::transaction(function () use ($passenger, $data): Passenger {
            $passenger->update($data);

            return $passenger->fresh();
        });
    }

    public function delete(Passenger $passenger): void
    {
        $passenger->delete();
    }

    public function getWithRelations(Passenger $passenger): Passenger
    {
        return $passenger->load(['user']);
    }
}
