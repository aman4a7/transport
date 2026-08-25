<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Notification\Models\Notification;
use App\Domain\Notification\Services\NotificationService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function __construct(
        private readonly NotificationService $notificationService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Notification::class);
        $user = $request->user();
        $result = $this->notificationService->listForUser($user, $request->all());

        return response()->json([
            'success' => true,
            'data' => $result->items(),
            'meta' => [
                'pagination' => [
                    'current_page' => $result->currentPage(),
                    'last_page' => $result->lastPage(),
                    'per_page' => $result->perPage(),
                    'total' => $result->total(),
                ],
                'unread_count' => $this->notificationService->unreadCount($user),
            ],
        ]);
    }

    public function markRead(Notification $notification): JsonResponse
    {
        $this->authorize('markRead', $notification);

        return response()->json([
            'success' => true,
            'data' => $this->notificationService->markRead(request()->user(), $notification->id),
        ]);
    }

    public function markAllRead(): JsonResponse
    {
        $this->authorize('markAllRead', Notification::class);
        $count = $this->notificationService->markAllRead(request()->user());

        return response()->json([
            'success' => true,
            'data' => ['marked' => $count],
            'message' => 'All notifications marked as read.',
        ]);
    }
}
