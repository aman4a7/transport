<?php

use App\Http\Controllers\Api\V1\AuthController;
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
});
