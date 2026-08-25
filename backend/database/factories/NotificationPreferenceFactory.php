<?php

namespace Database\Factories;

use App\Domain\Notification\Models\NotificationPreference;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NotificationPreference>
 */
class NotificationPreferenceFactory extends Factory
{
    protected $model = NotificationPreference::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'preferences' => NotificationPreference::defaultPreferences(),
        ];
    }

    public function disabled(string $type): static
    {
        return $this->state(fn (): array => [
            'preferences' => array_merge(NotificationPreference::defaultPreferences(), [$type => false]),
        ]);
    }
}
