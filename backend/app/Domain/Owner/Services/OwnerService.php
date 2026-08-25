<?php

namespace App\Domain\Owner\Services;

use App\Domain\Contract\Models\Contract;
use App\Domain\Owner\Models\Owner;
use App\Domain\Shared\Exceptions\BusinessRuleException;
use App\Domain\Shared\Services\AuditLogService;
use App\Domain\Shared\Support\SafeSort;
use App\Domain\Vehicle\Models\Vehicle;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OwnerService
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
    ) {}

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

        $sortField = SafeSort::field(
            ['created_at', 'company_name', 'contact_person', 'email', 'status'],
            $filters['sort_by'] ?? null,
            'created_at',
        );
        $sortOrder = SafeSort::direction($filters['sort_dir'] ?? null);
        $perPage = $filters['per_page'] ?? 15;

        return $query->orderBy($sortField, $sortOrder)->paginate($perPage);
    }

    public function create(array $data): Owner
    {
        return DB::transaction(function () use ($data): Owner {
            $data['created_by'] = Auth::id();
            $owner = Owner::create($data);

            $this->auditLogService->log('owner_created', $owner, null, null, $owner->toArray());

            return $owner;
        });
    }

    public function update(Owner $owner, array $data): Owner
    {
        return DB::transaction(function () use ($owner, $data): Owner {
            $old = $owner->toArray();
            $data['updated_by'] = Auth::id();
            $owner->update($data);

            $this->auditLogService->log('owner_updated', $owner, null, $old, $owner->fresh()->toArray());

            return $owner->fresh();
        });
    }

    public function delete(Owner $owner): void
    {
        DB::transaction(function () use ($owner): void {
            $hasVehicles = Vehicle::where('owner_id', $owner->id)->exists();

            if ($hasVehicles) {
                throw new BusinessRuleException(
                    message: 'Owner has associated vehicles.',
                    rule: 'owner_has_vehicles',
                );
            }

            $hasActiveContracts = Contract::where('owner_id', $owner->id)
                ->where('status', 'active')
                ->exists();

            if ($hasActiveContracts) {
                throw new BusinessRuleException(
                    message: 'Owner has active contracts.',
                    rule: 'owner_has_active_contracts',
                );
            }

            $owner->delete();

            $this->auditLogService->log('owner_deleted', $owner);
        });
    }

    public function getWithRelations(Owner $owner): Owner
    {
        return $owner->load(['user', 'vehicles']);
    }
}
