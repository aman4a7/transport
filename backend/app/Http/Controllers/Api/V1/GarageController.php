<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Garage\Models\MaintenanceRecord;
use App\Domain\Garage\Requests\StoreMaintenanceRecordRequest;
use App\Domain\Garage\Requests\UpdateMaintenanceRecordRequest;
use App\Domain\Garage\Services\GarageService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GarageController extends Controller
{
    public function __construct(
        private readonly GarageService $garageService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', MaintenanceRecord::class);
        $result = $this->garageService->list($request->all());

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

    public function show(MaintenanceRecord $maintenanceRecord): JsonResponse
    {
        $this->authorize('view', $maintenanceRecord);
        $record = $this->garageService->getWithRelations($maintenanceRecord);

        return response()->json(['success' => true, 'data' => $record]);
    }

    public function store(StoreMaintenanceRecordRequest $request): JsonResponse
    {
        $this->authorize('create', MaintenanceRecord::class);
        $record = $this->garageService->create($request->validated());

        return response()->json(['success' => true, 'data' => $record, 'message' => 'Maintenance record created.'], 201);
    }

    public function update(UpdateMaintenanceRecordRequest $request, MaintenanceRecord $maintenanceRecord): JsonResponse
    {
        $this->authorize('update', $maintenanceRecord);
        $record = $this->garageService->update($maintenanceRecord, $request->validated());

        return response()->json(['success' => true, 'data' => $record, 'message' => 'Maintenance record updated.']);
    }

    public function destroy(MaintenanceRecord $maintenanceRecord): JsonResponse
    {
        $this->authorize('delete', $maintenanceRecord);
        $this->garageService->delete($maintenanceRecord);

        return response()->json(['success' => true, 'data' => null, 'message' => 'Maintenance record deleted.']);
    }

    public function start(MaintenanceRecord $maintenanceRecord): JsonResponse
    {
        $this->authorize('start', $maintenanceRecord);
        $record = $this->garageService->start($maintenanceRecord);

        return response()->json(['success' => true, 'data' => $record, 'message' => 'Maintenance started.']);
    }

    public function complete(MaintenanceRecord $maintenanceRecord): JsonResponse
    {
        $this->authorize('complete', $maintenanceRecord);
        $record = $this->garageService->complete($maintenanceRecord);

        return response()->json(['success' => true, 'data' => $record, 'message' => 'Maintenance completed.']);
    }

    public function cancel(MaintenanceRecord $maintenanceRecord): JsonResponse
    {
        $this->authorize('cancel', $maintenanceRecord);
        $record = $this->garageService->cancel($maintenanceRecord);

        return response()->json(['success' => true, 'data' => $record, 'message' => 'Maintenance cancelled.']);
    }
}
