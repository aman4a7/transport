<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Compliance\Models\ComplianceDocument;
use App\Domain\Compliance\Requests\ApproveComplianceDocumentRequest;
use App\Domain\Compliance\Requests\RejectComplianceDocumentRequest;
use App\Domain\Compliance\Requests\StoreComplianceDocumentRequest;
use App\Domain\Compliance\Services\ComplianceDocumentService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ComplianceDocumentController extends Controller
{
    public function __construct(
        private readonly ComplianceDocumentService $complianceDocumentService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', ComplianceDocument::class);
        $result = $this->complianceDocumentService->list($request->all(), $request->user());

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

    public function store(StoreComplianceDocumentRequest $request): JsonResponse
    {
        $this->authorize('create', ComplianceDocument::class);
        $document = $this->complianceDocumentService->upload(
            $request->safe()->except('file'),
            $request->file('file'),
            $request->user(),
        );

        return response()->json(['success' => true, 'data' => $document, 'message' => 'Document uploaded successfully.'], 201);
    }

    public function show(ComplianceDocument $complianceDocument): JsonResponse
    {
        $this->authorize('view', $complianceDocument);
        $complianceDocument->load(['documentable', 'submittedBy', 'reviewedBy']);

        return response()->json(['success' => true, 'data' => $complianceDocument]);
    }

    public function approve(ApproveComplianceDocumentRequest $request, ComplianceDocument $complianceDocument): JsonResponse
    {
        $this->authorize('approve', $complianceDocument);
        $document = $this->complianceDocumentService->approve($complianceDocument, $request->user());

        return response()->json(['success' => true, 'data' => $document, 'message' => 'Document approved successfully.']);
    }

    public function reject(RejectComplianceDocumentRequest $request, ComplianceDocument $complianceDocument): JsonResponse
    {
        $this->authorize('reject', $complianceDocument);
        $document = $this->complianceDocumentService->reject($complianceDocument, $request->user(), $request->input('reason'));

        return response()->json(['success' => true, 'data' => $document, 'message' => 'Document rejected.']);
    }
}
