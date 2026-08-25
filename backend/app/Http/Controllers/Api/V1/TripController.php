<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Trip\Models\Trip;
use App\Domain\Trip\Requests\StoreTripRequest;
use App\Domain\Trip\Requests\UpdateTripRequest;
use App\Domain\Trip\Services\TripService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TripController extends Controller
{
    public function __construct(
        private readonly TripService $tripService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Trip::class);
        $result = $this->tripService->list($request->all(), $request->user());

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

    public function store(StoreTripRequest $request): JsonResponse
    {
        $this->authorize('create', Trip::class);
        $trip = $this->tripService->create($request->validated());

        return response()->json(['success' => true, 'data' => $trip, 'message' => 'Trip created successfully.'], 201);
    }

    public function show(Trip $trip): JsonResponse
    {
        $this->authorize('view', $trip);
        $trip = $this->tripService->getWithRelations($trip);

        return response()->json(['success' => true, 'data' => $trip]);
    }

    public function update(UpdateTripRequest $request, Trip $trip): JsonResponse
    {
        $this->authorize('update', $trip);
        $trip = $this->tripService->update($trip, $request->validated());

        return response()->json(['success' => true, 'data' => $trip, 'message' => 'Trip updated successfully.']);
    }

    public function destroy(Trip $trip): JsonResponse
    {
        $this->authorize('delete', $trip);
        $this->tripService->delete($trip);

        return response()->json(['success' => true, 'message' => 'Trip deleted successfully.']);
    }

    public function start(Trip $trip): JsonResponse
    {
        $this->authorize('start', $trip);
        $trip = $this->tripService->start($trip);

        return response()->json(['success' => true, 'data' => $trip, 'message' => 'Trip started.']);
    }

    public function complete(Trip $trip): JsonResponse
    {
        $this->authorize('complete', $trip);
        $trip = $this->tripService->complete($trip);

        return response()->json(['success' => true, 'data' => $trip, 'message' => 'Trip completed.']);
    }

    public function cancel(Trip $trip): JsonResponse
    {
        $this->authorize('cancel', $trip);
        $trip = $this->tripService->cancel($trip);

        return response()->json(['success' => true, 'data' => $trip, 'message' => 'Trip cancelled.']);
    }
}
