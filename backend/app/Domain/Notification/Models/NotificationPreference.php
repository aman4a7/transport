<?php

namespace App\Domain\Notification\Models;

use App\Domain\Notification\Enums\NotificationType;
use App\Models\User;
use Database\Factories\NotificationPreferenceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'preferences'])]
class NotificationPreference extends Model
{
    /** @use HasFactory<NotificationPreferenceFactory> */
    use HasFactory;

    protected $table = 'notification_preferences';

    protected static function newFactory(): NotificationPreferenceFactory
    {
        return NotificationPreferenceFactory::new();
    }

    protected function casts(): array
    {
        return [
            'preferences' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function enabled(NotificationType $type): bool
    {
        return (bool) ($this->preferences[$type->value] ?? true);
    }

    public static function defaultPreferences(): array
    {
        return collect(NotificationType::cases())
            ->mapWithKeys(fn (NotificationType $type): array => [$type->value => true])
            ->all();
    }

    public static function enabledFor(int $userId, NotificationType $type): bool
    {
        $preference = static::where('user_id', $userId)->first();

        return $preference === null || $preference->enabled($type);
    }
}
