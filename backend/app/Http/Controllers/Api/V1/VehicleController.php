<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Vehicle\Models\Vehicle;
use App\Domain\Vehicle\Requests\StoreVehicleRequest;
use App\Domain\Vehicle\Requests\UpdateVehicleRequest;
use App\Domain\Vehicle\Services\VehicleService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VehicleController extends Controller
{
    public function __construct(
        private readonly VehicleService $vehicleService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Vehicle::class);
        $result = $this->vehicleService->list($request->all());

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

    public function store(StoreVehicleRequest $request): JsonResponse
    {
        $this->authorize('create', Vehicle::class);
        $vehicle = $this->vehicleService->create($request->validated());

        return response()->json(['success' => true, 'data' => $vehicle, 'message' => 'Vehicle created successfully.'], 201);
    }

    public function show(Vehicle $vehicle): JsonResponse
    {
        $this->authorize('view', $vehicle);
        $vehicle = $this->vehicleService->getWithRelations($vehicle);

        return response()->json(['success' => true, 'data' => $vehicle]);
    }

    public function update(UpdateVehicleRequest $request, Vehicle $vehicle): JsonResponse
    {
        $this->authorize('update', $vehicle);
        $vehicle = $this->vehicleService->update($vehicle, $request->validated());

        return response()->json(['success' => true, 'data' => $vehicle, 'message' => 'Vehicle updated successfully.']);
    }

    public function destroy(Vehicle $vehicle): JsonResponse
    {
        $this->authorize('delete', $vehicle);
        $this->vehicleService->delete($vehicle);

        return response()->json(['success' => true, 'message' => 'Vehicle deleted successfully.']);
    }
}
