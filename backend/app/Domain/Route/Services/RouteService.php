<?php

namespace App\Domain\Route\Services;

use App\Domain\Route\Models\Route;
use App\Domain\Shared\Exceptions\BusinessRuleException;
use App\Domain\Shared\Services\AuditLogService;
use App\Domain\Shared\Support\SafeSort;
use App\Domain\Trip\Models\Trip;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RouteService
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
    ) {}

    public function list(array $filters = []): LengthAwarePaginator
    {
        $query = Route::query();

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'ilike', "%{$search}%")
                    ->orWhere('origin', 'ilike', "%{$search}%")
                    ->orWhere('destination', 'ilike', "%{$search}%")
                    ->orWhere('code', 'ilike', "%{$search}%");
            });
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $sortField = SafeSort::field(
            ['created_at', 'name', 'code', 'origin', 'destination', 'distance_km', 'capacity', 'status'],
            $filters['sort_by'] ?? null,
            'created_at',
        );
        $sortOrder = SafeSort::direction($filters['sort_dir'] ?? null);
        $perPage = $filters['per_page'] ?? 15;

        return $query->orderBy($sortField, $sortOrder)->paginate($perPage);
    }

    public function create(array $data): Route
    {
        return DB::transaction(function () use ($data): Route {
            $data['created_by'] = Auth::id();
            $route = Route::create($data);

            $this->auditLogService->log('route_created', $route, null, null, $route->toArray());

            return $route;
        });
    }

    public function update(Route $route, array $data): Route
    {
        return DB::transaction(function () use ($route, $data): Route {
            $old = $route->toArray();
            $data['updated_by'] = Auth::id();
            $route->update($data);

            $this->auditLogService->log('route_updated', $route, null, $old, $route->fresh()->toArray());

            return $route->fresh();
        });
    }

    public function delete(Route $route): void
    {
        DB::transaction(function () use ($route): void {
            $hasTrips = Trip::where('route_id', $route->id)->exists();

            if ($hasTrips) {
                throw new BusinessRuleException(
                    message: 'Route has assigned trips.',
                    rule: 'route_has_trips',
                );
            }

            $route->delete();

            $this->auditLogService->log('route_deleted', $route);
        });
    }

    public function getWithRelations(Route $route): Route
    {
        return $route;
    }
}
