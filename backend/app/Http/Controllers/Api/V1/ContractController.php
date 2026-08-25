<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Contract\Models\Contract;
use App\Domain\Contract\Requests\StoreContractRequest;
use App\Domain\Contract\Requests\UpdateContractRequest;
use App\Domain\Contract\Services\ContractService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContractController extends Controller
{
    public function __construct(
        private readonly ContractService $contractService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Contract::class);
        $result = $this->contractService->list($request->all(), $request->user());

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

    public function show(Contract $contract): JsonResponse
    {
        $this->authorize('view', $contract);
        $record = $this->contractService->getWithRelations($contract);

        return response()->json(['success' => true, 'data' => $record]);
    }

    public function store(StoreContractRequest $request): JsonResponse
    {
        $this->authorize('create', Contract::class);
        $contract = $this->contractService->create($request->validated());

        return response()->json(['success' => true, 'data' => $contract, 'message' => 'Contract created.'], 201);
    }

    public function update(UpdateContractRequest $request, Contract $contract): JsonResponse
    {
        $this->authorize('update', $contract);
        $contract = $this->contractService->update($contract, $request->validated());

        return response()->json(['success' => true, 'data' => $contract, 'message' => 'Contract updated.']);
    }

    public function destroy(Contract $contract): JsonResponse
    {
        $this->authorize('delete', $contract);
        $this->contractService->delete($contract);

        return response()->json(['success' => true, 'data' => null, 'message' => 'Contract deleted.']);
    }

    public function activate(Contract $contract): JsonResponse
    {
        $this->authorize('activate', $contract);
        $contract = $this->contractService->activate($contract);

        return response()->json(['success' => true, 'data' => $contract, 'message' => 'Contract activated.']);
    }

    public function terminate(Contract $contract): JsonResponse
    {
        $this->authorize('terminate', $contract);
        $contract = $this->contractService->terminate($contract);

        return response()->json(['success' => true, 'data' => $contract, 'message' => 'Contract terminated.']);
    }
}
