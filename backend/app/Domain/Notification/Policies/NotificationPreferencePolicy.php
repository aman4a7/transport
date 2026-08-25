<?php

namespace App\Domain\Notification\Policies;

use App\Domain\Notification\Models\NotificationPreference;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class NotificationPreferencePolicy
{
    use HandlesAuthorization;

    public function view(User $user, NotificationPreference $preference): bool
    {
        return $user->hasPermission('notifications.view') && $preference->user_id === $user->id;
    }

    public function update(User $user, NotificationPreference $preference): bool
    {
        return $user->hasPermission('notifications.view') && $preference->user_id === $user->id;
    }
}
