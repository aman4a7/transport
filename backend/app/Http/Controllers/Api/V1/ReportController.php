<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Reporting\Enums\ReportType;
use App\Domain\Reporting\Services\ReportService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function __construct(
        private readonly ReportService $reportService,
    ) {}

    public function index(): JsonResponse
    {
        $this->authorize('reports.view');

        $reports = $this->reportService->listAvailable();

        return response()->json(['success' => true, 'data' => $reports]);
    }

    public function show(string $type, Request $request): JsonResponse
    {
        $this->authorize('reports.generate');

        $reportType = ReportType::tryFrom($type);

        if (! $reportType) {
            return response()->json([
                'success' => false,
                'message' => "Invalid report type: {$type}.",
            ], 422);
        }

        $data = $this->reportService->generate($reportType, $request->all());

        return response()->json(['success' => true, 'data' => $data]);
    }
}
