<?php

namespace App\Domain\Route\Services;

use App\Domain\Route\Models\Route;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class RouteService
{
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

        $sortField = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_dir'] ?? 'desc';
        $perPage = $filters['per_page'] ?? 15;

        return $query->orderBy($sortField, $sortOrder)->paginate($perPage);
    }

    public function create(array $data): Route
    {
        return DB::transaction(function () use ($data): Route {
            return Route::create($data);
        });
    }

    public function update(Route $route, array $data): Route
    {
        return DB::transaction(function () use ($route, $data): Route {
            $route->update($data);

            return $route->fresh();
        });
    }

    public function delete(Route $route): void
    {
        $route->delete();
    }

    public function getWithRelations(Route $route): Route
    {
        return $route;
    }
}
