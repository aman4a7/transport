<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Passenger\Models\Passenger;
use App\Domain\Passenger\Requests\StorePassengerRequest;
use App\Domain\Passenger\Requests\UpdatePassengerRequest;
use App\Domain\Passenger\Services\PassengerService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PassengerController extends Controller
{
    public function __construct(
        private readonly PassengerService $passengerService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Passenger::class);
        $result = $this->passengerService->list($request->all());

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

    public function store(StorePassengerRequest $request): JsonResponse
    {
        $this->authorize('create', Passenger::class);
        $passenger = $this->passengerService->create($request->validated());

        return response()->json(['success' => true, 'data' => $passenger, 'message' => 'Passenger created successfully.'], 201);
    }

    public function show(Passenger $passenger): JsonResponse
    {
        $this->authorize('view', $passenger);
        $passenger = $this->passengerService->getWithRelations($passenger);

        return response()->json(['success' => true, 'data' => $passenger]);
    }

    public function update(UpdatePassengerRequest $request, Passenger $passenger): JsonResponse
    {
        $this->authorize('update', $passenger);
        $passenger = $this->passengerService->update($passenger, $request->validated());

        return response()->json(['success' => true, 'data' => $passenger, 'message' => 'Passenger updated successfully.']);
    }

    public function destroy(Passenger $passenger): JsonResponse
    {
        $this->authorize('delete', $passenger);
        $this->passengerService->delete($passenger);

        return response()->json(['success' => true, 'message' => 'Passenger deleted successfully.']);
    }
}
