<?php

namespace App\Domain\Owner\Services;

use App\Domain\Owner\Models\Owner;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class OwnerService
{
    public function list(array $filters = []): LengthAwarePaginator
    {
        $query = Owner::query();

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search): void {
                $q->where('company_name', 'ilike', "%{$search}%")
                    ->orWhere('contact_person', 'ilike', "%{$search}%")
                    ->orWhere('email', 'ilike', "%{$search}%");
            });
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $sortField = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_dir'] ?? 'desc';
        $perPage = $filters['per_page'] ?? 15;

        return $query->orderBy($sortField, $sortOrder)->paginate($perPage);
    }

    public function create(array $data): Owner
    {
        return DB::transaction(function () use ($data): Owner {
            return Owner::create($data);
        });
    }

    public function update(Owner $owner, array $data): Owner
    {
        return DB::transaction(function () use ($owner, $data): Owner {
            $owner->update($data);

            return $owner->fresh();
        });
    }

    public function delete(Owner $owner): void
    {
        $owner->delete();
    }

    public function getWithRelations(Owner $owner): Owner
    {
        return $owner->load(['user', 'vehicles']);
    }
}
