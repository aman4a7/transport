<?php

namespace App\Domain\Notification\Policies;

use App\Domain\Notification\Models\Notification;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class NotificationPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermission('notifications.view');
    }

    public function view(User $user, Notification $notification): bool
    {
        return $user->hasPermission('notifications.view') && $notification->user_id === $user->id;
    }

    public function markRead(User $user, Notification $notification): bool
    {
        return $user->hasPermission('notifications.view') && $notification->user_id === $user->id;
    }

    public function markAllRead(User $user): bool
    {
        return $user->hasPermission('notifications.view');
    }
}
