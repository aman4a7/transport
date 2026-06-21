<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ComplianceDocumentController;
use App\Http\Controllers\Api\V1\DriverController;
use App\Http\Controllers\Api\V1\OwnerController;
use App\Http\Controllers\Api\V1\PassengerController;
use App\Http\Controllers\Api\V1\RouteController;
use App\Http\Controllers\Api\V1\VehicleController;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Route;

Route::get('/health', function (): JsonResponse {
    return response()->json([
        'success' => true,
        'message' => 'OK',
        'data' => [
            'app' => config('app.name'),
            'version' => '1.0.0',
            'timestamp' => now()->toIso8601String(),
        ],
    ]);
})->name('api.health');

Route::prefix('v1')->group(function (): void {
    Route::post('auth/login', [AuthController::class, 'login'])
        ->middleware('throttle:5,1')
        ->name('auth.login');

    Route::post('auth/logout', [AuthController::class, 'logout'])
        ->middleware('auth:sanctum')
        ->name('auth.logout');

    Route::get('auth/me', [AuthController::class, 'me'])
        ->middleware('auth:sanctum')
        ->name('auth.me');

    Route::post('auth/forgot-password', [AuthController::class, 'forgotPassword'])
        ->middleware('throttle:3,1')
        ->name('auth.forgot-password');

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::apiResource('vehicles', VehicleController::class);
        Route::apiResource('drivers', DriverController::class);
        Route::apiResource('owners', OwnerController::class);
        Route::apiResource('passengers', PassengerController::class);
        Route::apiResource('routes', RouteController::class);

        Route::prefix('compliance')->group(function (): void {
            Route::get('documents', [ComplianceDocumentController::class, 'index'])->name('compliance.documents.index');
            Route::post('documents', [ComplianceDocumentController::class, 'store'])->name('compliance.documents.store');
            Route::get('documents/{compliance_document}', [ComplianceDocumentController::class, 'show'])->name('compliance.documents.show');
            Route::post('documents/{compliance_document}/approve', [ComplianceDocumentController::class, 'approve'])->name('compliance.documents.approve');
            Route::post('documents/{compliance_document}/reject', [ComplianceDocumentController::class, 'reject'])->name('compliance.documents.reject');
        });
    });
});
