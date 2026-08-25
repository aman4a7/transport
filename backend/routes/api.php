<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ComplianceDocumentController;
use App\Http\Controllers\Api\V1\ContractController;
use App\Http\Controllers\Api\V1\DriverController;
use App\Http\Controllers\Api\V1\FuelController;
use App\Http\Controllers\Api\V1\GarageController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\NotificationPreferenceController;
use App\Http\Controllers\Api\V1\OwnerController;
use App\Http\Controllers\Api\V1\PassengerController;
use App\Http\Controllers\Api\V1\ReportController;
use App\Http\Controllers\Api\V1\RouteController;
use App\Http\Controllers\Api\V1\TripController;
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
        Route::apiResource('trips', TripController::class);
        Route::post('trips/{trip}/start', [TripController::class, 'start'])->name('trips.start');
        Route::post('trips/{trip}/complete', [TripController::class, 'complete'])->name('trips.complete');
        Route::post('trips/{trip}/cancel', [TripController::class, 'cancel'])->name('trips.cancel');

        Route::apiResource('maintenance', GarageController::class)->parameters(['maintenance' => 'maintenanceRecord']);
        Route::post('maintenance/{maintenanceRecord}/start', [GarageController::class, 'start'])->name('maintenance.start');
        Route::post('maintenance/{maintenanceRecord}/complete', [GarageController::class, 'complete'])->name('maintenance.complete');
        Route::post('maintenance/{maintenanceRecord}/cancel', [GarageController::class, 'cancel'])->name('maintenance.cancel');

        Route::apiResource('contracts', ContractController::class);
        Route::post('contracts/{contract}/activate', [ContractController::class, 'activate'])->name('contracts.activate');
        Route::post('contracts/{contract}/terminate', [ContractController::class, 'terminate'])->name('contracts.terminate');

        Route::prefix('fuel')->group(function (): void {
            Route::get('transactions', [FuelController::class, 'transactions'])->name('fuel.transactions.index');
            Route::get('transactions/{id}', [FuelController::class, 'transaction'])->name('fuel.transactions.show');
            Route::post('issue', [FuelController::class, 'issue'])->name('fuel.issue');
            Route::post('restock', [FuelController::class, 'restock'])->name('fuel.restock');
            Route::post('adjust', [FuelController::class, 'adjust'])->name('fuel.adjust');
            Route::get('stocks', [FuelController::class, 'stocks'])->name('fuel.stocks.index');
            Route::get('stocks/{fuelType}', [FuelController::class, 'stock'])->name('fuel.stocks.show');
        });

        Route::prefix('reports')->group(function (): void {
            Route::get('/', [ReportController::class, 'index'])->name('reports.index');
            Route::get('{type}', [ReportController::class, 'show'])->name('reports.show');
        });

        Route::prefix('compliance')->group(function (): void {
            Route::get('documents', [ComplianceDocumentController::class, 'index'])->name('compliance.documents.index');
            Route::post('documents', [ComplianceDocumentController::class, 'store'])->name('compliance.documents.store');
            Route::get('documents/{compliance_document}', [ComplianceDocumentController::class, 'show'])->name('compliance.documents.show');
            Route::post('documents/{compliance_document}/approve', [ComplianceDocumentController::class, 'approve'])->name('compliance.documents.approve');
            Route::post('documents/{compliance_document}/reject', [ComplianceDocumentController::class, 'reject'])->name('compliance.documents.reject');
        });

        Route::get('notifications/preferences', [NotificationPreferenceController::class, 'show'])->name('notifications.preferences.show');
        Route::patch('notifications/preferences', [NotificationPreferenceController::class, 'update'])->name('notifications.preferences.update');
        Route::patch('notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
        Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
        Route::patch('notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    });
});
