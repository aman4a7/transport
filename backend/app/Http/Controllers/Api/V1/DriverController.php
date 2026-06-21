<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Driver\Models\Driver;
use App\Domain\Driver\Requests\StoreDriverRequest;
use App\Domain\Driver\Requests\UpdateDriverRequest;
use App\Domain\Driver\Services\DriverService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DriverController extends Controller
{
    public function __construct(
        private readonly DriverService $driverService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Driver::class);
        $result = $this->driverService->list($request->all());

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

    public function store(StoreDriverRequest $request): JsonResponse
    {
        $this->authorize('create', Driver::class);
        $driver = $this->driverService->create($request->validated());

        return response()->json(['success' => true, 'data' => $driver, 'message' => 'Driver created successfully.'], 201);
    }

    public function show(Driver $driver): JsonResponse
    {
        $this->authorize('view', $driver);
        $driver = $this->driverService->getWithRelations($driver);

        return response()->json(['success' => true, 'data' => $driver]);
    }

    public function update(UpdateDriverRequest $request, Driver $driver): JsonResponse
    {
        $this->authorize('update', $driver);
        $driver = $this->driverService->update($driver, $request->validated());

        return response()->json(['success' => true, 'data' => $driver, 'message' => 'Driver updated successfully.']);
    }

    public function destroy(Driver $driver): JsonResponse
    {
        $this->authorize('delete', $driver);
        $this->driverService->delete($driver);

        return response()->json(['success' => true, 'message' => 'Driver deleted successfully.']);
    }
}
