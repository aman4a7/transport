<?php

namespace App\Domain\Passenger\Services;

use App\Domain\Passenger\Models\Passenger;
use App\Domain\Shared\Exceptions\BusinessRuleException;
use App\Domain\Shared\Services\AuditLogService;
use App\Domain\Shared\Support\SafeSort;
use App\Domain\Trip\Models\TripAssignment;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PassengerService
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
    ) {}

    public function list(array $filters = [], ?User $user = null): LengthAwarePaginator
    {
        $query = Passenger::query();

        if ($user !== null && $user->hasRole('passenger')) {
            $query->where('user_id', $user->id);
        }

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

        $sortField = SafeSort::field(
            ['created_at', 'first_name', 'last_name', 'employee_id', 'department', 'status'],
            $filters['sort_by'] ?? null,
            'created_at',
        );
        $sortOrder = SafeSort::direction($filters['sort_dir'] ?? null);
        $perPage = $filters['per_page'] ?? 15;

        return $query->orderBy($sortField, $sortOrder)->paginate($perPage);
    }

    public function create(array $data): Passenger
    {
        return DB::transaction(function () use ($data): Passenger {
            $data['created_by'] = Auth::id();
            $passenger = Passenger::create($data);

            $this->auditLogService->log('passenger_created', $passenger, null, null, $passenger->toArray());

            return $passenger;
        });
    }

    public function update(Passenger $passenger, array $data): Passenger
    {
        return DB::transaction(function () use ($passenger, $data): Passenger {
            $old = $passenger->toArray();
            $data['updated_by'] = Auth::id();
            $passenger->update($data);

            $this->auditLogService->log('passenger_updated', $passenger, null, $old, $passenger->fresh()->toArray());

            return $passenger->fresh();
        });
    }

    public function delete(Passenger $passenger): void
    {
        DB::transaction(function () use ($passenger): void {
            $hasAssignments = TripAssignment::where('passenger_id', $passenger->id)->exists();

            if ($hasAssignments) {
                throw new BusinessRuleException(
                    message: 'Passenger has trip assignments.',
                    rule: 'passenger_has_trip_assignments',
                );
            }

            $passenger->delete();

            $this->auditLogService->log('passenger_deleted', $passenger);
        });
    }

    public function getWithRelations(Passenger $passenger): Passenger
    {
        return $passenger->load(['user']);
    }
}
