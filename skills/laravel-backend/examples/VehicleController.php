<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Vehicle\Models\Vehicle;
use App\Domain\Vehicle\Services\VehicleService;
use App\Domain\Vehicle\Requests\StoreVehicleRequest;
use App\Domain\Vehicle\Requests\UpdateVehicleRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * EXAMPLE: Vehicle controller using the laravel-backend skill templates.
 *
 * Key patterns demonstrated:
 * - Thin controller (each method ≤ 10 lines)
 * - Policy authorization on every action
 * - Form Request validation for store/update
 * - Service delegation for all domain logic
 * - Standard API envelope responses
 */
class VehicleController extends Controller
{
    public function __construct(
        private readonly VehicleService $vehicleService,
    ) {}

    /**
     * List all vehicles with filtering and pagination.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Vehicle::class);

        $result = $this->vehicleService->list($request->query());

        return response()->json([
            'success' => true,
            'data' => $result->items(),
            'meta' => ['pagination' => $result->toArray()],
        ]);
    }

    /**
     * Create a new vehicle.
     */
    public function store(StoreVehicleRequest $request): JsonResponse
    {
        $this->authorize('create', Vehicle::class);

        $vehicle = $this->vehicleService->create($request->validated());

        return response()->json([
            'success' => true,
            'data' => $vehicle,
            'message' => 'Vehicle created successfully.',
        ], 201);
    }

    /**
     * Show a single vehicle with related data.
     */
    public function show(Vehicle $vehicle): JsonResponse
    {
        $this->authorize('view', $vehicle);

        $vehicle = $this->vehicleService->getWithRelations($vehicle);

        return response()->json([
            'success' => true,
            'data' => $vehicle,
        ]);
    }

    /**
     * Update an existing vehicle.
     */
    public function update(UpdateVehicleRequest $request, Vehicle $vehicle): JsonResponse
    {
        $this->authorize('update', $vehicle);

        $vehicle = $this->vehicleService->update($vehicle, $request->validated());

        return response()->json([
            'success' => true,
            'data' => $vehicle,
            'message' => 'Vehicle updated successfully.',
        ]);
    }

    /**
     * Delete a vehicle.
     */
    public function destroy(Vehicle $vehicle): JsonResponse
    {
        $this->authorize('delete', $vehicle);

        $this->vehicleService->delete($vehicle);

        return response()->json([
            'success' => true,
            'message' => 'Vehicle deleted successfully.',
        ]);
    }

    /**
     * Update vehicle operational status.
     * Custom action beyond standard CRUD.
     */
    public function updateStatus(Request $request, Vehicle $vehicle): JsonResponse
    {
        $this->authorize('updateStatus', $vehicle);

        $vehicle = $this->vehicleService->updateStatus(
            $vehicle,
            $request->input('status'),
            $request->input('reason'),
        );

        return response()->json([
            'success' => true,
            'data' => $vehicle,
            'message' => 'Vehicle status updated successfully.',
        ]);
    }
}
