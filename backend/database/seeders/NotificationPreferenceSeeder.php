<?php

namespace Database\Seeders;

use App\Domain\Notification\Models\NotificationPreference;
use App\Models\User;
use Illuminate\Database\Seeder;

class NotificationPreferenceSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->select('id')->each(function (User $user): void {
            NotificationPreference::firstOrCreate(
                ['user_id' => $user->id],
                ['preferences' => NotificationPreference::defaultPreferences()],
            );
        });
    }
}
