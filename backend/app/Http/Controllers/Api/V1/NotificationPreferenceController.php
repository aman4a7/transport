<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Notification\Requests\UpdateNotificationPreferenceRequest;
use App\Domain\Notification\Services\NotificationPreferenceService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationPreferenceController extends Controller
{
    public function __construct(
        private readonly NotificationPreferenceService $notificationPreferenceService,
    ) {}

    public function show(Request $request): JsonResponse
    {
        $preference = $this->notificationPreferenceService->getForUser($request->user());
        $this->authorize('view', $preference);

        return response()->json(['success' => true, 'data' => $preference]);
    }

    public function update(UpdateNotificationPreferenceRequest $request): JsonResponse
    {
        $preference = $this->notificationPreferenceService->getForUser($request->user());
        $this->authorize('update', $preference);

        $preference = $this->notificationPreferenceService->updateForUser(
            $request->user(),
            $request->validated('preferences'),
        );

        return response()->json([
            'success' => true,
            'data' => $preference,
            'message' => 'Notification preferences updated successfully.',
        ]);
    }
}
