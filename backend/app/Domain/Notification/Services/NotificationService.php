<?php

namespace App\Domain\Notification\Services;

use App\Domain\Notification\Enums\NotificationType;
use App\Domain\Notification\Models\Notification;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class NotificationService
{
    public function listForUser(User $user, array $filters = []): LengthAwarePaginator
    {
        $query = Notification::query()->where('user_id', $user->id);

        if (! empty($filters['status'])) {
            if ($filters['status'] === 'unread') {
                $query->unread();
            } elseif ($filters['status'] === 'read') {
                $query->read();
            }
        }

        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        $perPage = $filters['per_page'] ?? 15;

        return $query->orderByDesc('created_at')->paginate($perPage);
    }

    public function unreadCount(User $user): int
    {
        return Notification::query()
            ->where('user_id', $user->id)
            ->unread()
            ->count();
    }

    public function markRead(User $user, int $id): Notification
    {
        $notification = Notification::query()
            ->where('user_id', $user->id)
            ->where('id', $id)
            ->firstOrFail();

        if (! $notification->isRead()) {
            $notification->update(['read_at' => now()]);
        }

        return $notification->fresh();
    }

    public function markAllRead(User $user): int
    {
        return Notification::query()
            ->where('user_id', $user->id)
            ->unread()
            ->update(['read_at' => now()]);
    }

    public function create(
        int $userId,
        NotificationType $type,
        string $title,
        ?string $body = null,
        array $data = [],
    ): Notification {
        return Notification::create([
            'user_id' => $userId,
            'type' => $type->value,
            'title' => $title,
            'body' => $body,
            'data' => $data,
        ]);
    }

    public function createUnique(
        int $userId,
        NotificationType $type,
        string $title,
        ?string $body = null,
        array $data = [],
    ): ?Notification {
        $exists = Notification::query()
            ->where('user_id', $userId)
            ->where('type', $type->value)
            ->unread()
            ->whereNotNull('data->entity_type')
            ->where('data->entity_type', $data['entity_type'] ?? null)
            ->where('data->entity_id', $data['entity_id'] ?? null)
            ->exists();

        if ($exists) {
            return null;
        }

        return $this->create($userId, $type, $title, $body, $data);
    }
}
