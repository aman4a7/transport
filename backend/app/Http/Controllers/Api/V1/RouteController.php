<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Route\Models\Route;
use App\Domain\Route\Requests\StoreRouteRequest;
use App\Domain\Route\Requests\UpdateRouteRequest;
use App\Domain\Route\Services\RouteService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RouteController extends Controller
{
    public function __construct(
        private readonly RouteService $routeService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Route::class);
        $result = $this->routeService->list($request->all());

        return response()->json([
            'success' => true,
            'data' => $result->items(),
            'meta' => ['pagination' => [
                'current_page' => $result->currentPage(),
                'last_page' => $result->lastPage(),
                'per_page' => $result->perPage(),
                'total' => $result->total(),
            ]],
        ]);
    }

    public function store(StoreRouteRequest $request): JsonResponse
    {
        $this->authorize('create', Route::class);
        $route = $this->routeService->create($request->validated());

        return response()->json(['success' => true, 'data' => $route, 'message' => 'Route created successfully.'], 201);
    }

    public function show(Route $route): JsonResponse
    {
        $this->authorize('view', $route);
        $route = $this->routeService->getWithRelations($route);

        return response()->json(['success' => true, 'data' => $route]);
    }

    public function update(UpdateRouteRequest $request, Route $route): JsonResponse
    {
        $this->authorize('update', $route);
        $route = $this->routeService->update($route, $request->validated());

        return response()->json(['success' => true, 'data' => $route, 'message' => 'Route updated successfully.']);
    }

    public function destroy(Route $route): JsonResponse
    {
        $this->authorize('delete', $route);
        $this->routeService->delete($route);

        return response()->json(['success' => true, 'message' => 'Route deleted successfully.']);
    }
}
