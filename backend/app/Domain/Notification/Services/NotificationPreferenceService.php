<?php

namespace App\Domain\Notification\Services;

use App\Domain\Notification\Models\NotificationPreference;
use App\Domain\Shared\Services\AuditLogService;
use App\Models\User;

class NotificationPreferenceService
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
    ) {}

    public function getForUser(User $user): NotificationPreference
    {
        return NotificationPreference::firstOrCreate(
            ['user_id' => $user->id],
            ['preferences' => NotificationPreference::defaultPreferences()],
        );
    }

    public function updateForUser(User $user, array $preferences): NotificationPreference
    {
        $preference = $this->getForUser($user);
        $old = $preference->preferences;

        $merged = array_merge(
            $preference->preferences ?? NotificationPreference::defaultPreferences(),
            $preferences,
        );

        $preference->update(['preferences' => $merged]);

        $this->auditLogService->log(
            'notification_preferences_updated',
            $preference,
            null,
            $old,
            $preference->fresh()->preferences,
            "Updated notification preferences for user #{$user->id}",
        );

        return $preference->fresh();
    }
}
