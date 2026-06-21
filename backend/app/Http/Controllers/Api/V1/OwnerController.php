<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Owner\Models\Owner;
use App\Domain\Owner\Requests\StoreOwnerRequest;
use App\Domain\Owner\Requests\UpdateOwnerRequest;
use App\Domain\Owner\Services\OwnerService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OwnerController extends Controller
{
    public function __construct(
        private readonly OwnerService $ownerService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Owner::class);
        $result = $this->ownerService->list($request->all());

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

    public function store(StoreOwnerRequest $request): JsonResponse
    {
        $this->authorize('create', Owner::class);
        $owner = $this->ownerService->create($request->validated());

        return response()->json(['success' => true, 'data' => $owner, 'message' => 'Owner created successfully.'], 201);
    }

    public function show(Owner $owner): JsonResponse
    {
        $this->authorize('view', $owner);
        $owner = $this->ownerService->getWithRelations($owner);

        return response()->json(['success' => true, 'data' => $owner]);
    }

    public function update(UpdateOwnerRequest $request, Owner $owner): JsonResponse
    {
        $this->authorize('update', $owner);
        $owner = $this->ownerService->update($owner, $request->validated());

        return response()->json(['success' => true, 'data' => $owner, 'message' => 'Owner updated successfully.']);
    }

    public function destroy(Owner $owner): JsonResponse
    {
        $this->authorize('delete', $owner);
        $this->ownerService->delete($owner);

        return response()->json(['success' => true, 'message' => 'Owner deleted successfully.']);
    }
}
