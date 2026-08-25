<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Fuel\Models\FuelStock;
use App\Domain\Fuel\Models\FuelTransaction;
use App\Domain\Fuel\Requests\AdjustFuelStockRequest;
use App\Domain\Fuel\Requests\IssueFuelRequest;
use App\Domain\Fuel\Requests\RestockFuelRequest;
use App\Domain\Fuel\Services\FuelService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FuelController extends Controller
{
    public function __construct(
        private readonly FuelService $fuelService,
    ) {}

    public function transactions(Request $request): JsonResponse
    {
        $this->authorize('viewTransactions', FuelTransaction::class);
        $result = $this->fuelService->listTransactions($request->all());

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

    public function transaction(int $id): JsonResponse
    {
        $this->authorize('viewTransaction', FuelTransaction::class);
        $transaction = $this->fuelService->getTransaction($id);

        return response()->json(['success' => true, 'data' => $transaction]);
    }

    public function issue(IssueFuelRequest $request): JsonResponse
    {
        $this->authorize('issue', FuelTransaction::class);
        $transaction = $this->fuelService->issueFuel($request->validated());

        return response()->json(['success' => true, 'data' => $transaction, 'message' => 'Fuel issued successfully.'], 201);
    }

    public function restock(RestockFuelRequest $request): JsonResponse
    {
        $this->authorize('restock', FuelTransaction::class);
        $transaction = $this->fuelService->restock($request->validated());

        return response()->json(['success' => true, 'data' => $transaction, 'message' => 'Fuel restocked successfully.'], 201);
    }

    public function adjust(AdjustFuelStockRequest $request): JsonResponse
    {
        $this->authorize('adjustStock', FuelStock::class);
        $transaction = $this->fuelService->adjustStock($request->validated());

        return response()->json(['success' => true, 'data' => $transaction, 'message' => 'Stock adjusted successfully.'], 201);
    }

    public function stocks(): JsonResponse
    {
        $this->authorize('viewStock', FuelStock::class);
        $stocks = $this->fuelService->getStocks();

        return response()->json(['success' => true, 'data' => $stocks]);
    }

    public function stock(string $fuelType): JsonResponse
    {
        $this->authorize('viewStock', FuelStock::class);
        $stock = $this->fuelService->getStock($fuelType);

        return response()->json(['success' => true, 'data' => $stock]);
    }
}
