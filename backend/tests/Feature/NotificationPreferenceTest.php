<?php

use App\Domain\Contract\Models\Contract;
use App\Domain\Fuel\Models\FuelStock;
use App\Domain\Notification\Enums\NotificationType;
use App\Domain\Notification\Models\Notification;
use App\Domain\Notification\Models\NotificationPreference;
use App\Domain\Notification\Services\NotificationPreferenceService;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed([
        RoleSeeder::class,
        PermissionSeeder::class,
        RolePermissionSeeder::class,
    ]);
});

function prefUser(string $slug): User
{
    $role = Role::where('slug', $slug)->first();
    $user = User::factory()->create();
    $user->roles()->attach($role->id, ['assigned_at' => now()]);

    return $user;
}

function prefAdmin(): User
{
    return prefUser('system_administrator');
}

test('default preferences are created with all notification types enabled', function (): void {
    $user = prefUser('transport_manager');
    $service = app(NotificationPreferenceService::class);

    $preference = $service->getForUser($user);

    $this->assertDatabaseHas('notification_preferences', ['user_id' => $user->id]);
    expect($preference->preferences)->toBe(NotificationPreference::defaultPreferences());

    foreach (NotificationType::cases() as $type) {
        expect($preference->enabled($type))->toBeTrue();
    }
});

test('authenticated user can retrieve their notification preferences', function (): void {
    $user = prefUser('transport_manager');
    NotificationPreference::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->getJson('/api/v1/notifications/preferences');

    $response->assertOk()->assertJsonStructure(['success', 'data']);
    expect($response['data']['user_id'])->toBe($user->id);
    expect($response['data']['preferences']['compliance_expiring'])->toBeTrue();
    expect($response['data']['preferences']['fuel_low_stock'])->toBeTrue();
});

test('authenticated user can update their notification preferences', function (): void {
    $user = prefUser('transport_manager');
    NotificationPreference::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->patchJson('/api/v1/notifications/preferences', [
        'preferences' => [
            'compliance_expiring' => false,
            'contract_expiring' => true,
            'maintenance_due' => false,
            'fuel_low_stock' => true,
        ],
    ]);

    $response->assertOk()->assertJson(['success' => true, 'message' => 'Notification preferences updated successfully.']);
    expect($response['data']['preferences']['compliance_expiring'])->toBeFalse();
    expect($response['data']['preferences']['contract_expiring'])->toBeTrue();
    $this->assertDatabaseHas('notification_preferences', ['user_id' => $user->id]);
});

test('updating preferences preserves unspecified types', function (): void {
    $user = prefUser('transport_manager');
    NotificationPreference::factory()->create([
        'user_id' => $user->id,
        'preferences' => array_merge(NotificationPreference::defaultPreferences(), [
            'compliance_expiring' => false,
            'contract_expiring' => false,
        ]),
    ]);

    $this->actingAs($user)->patchJson('/api/v1/notifications/preferences', [
        'preferences' => ['compliance_expiring' => true],
    ]);

    $preference = NotificationPreference::where('user_id', $user->id)->first();
    expect($preference->enabled(NotificationType::ComplianceExpiring))->toBeTrue();
    expect($preference->enabled(NotificationType::ContractExpiring))->toBeFalse();
});

test('validation rejects unsupported notification types', function (): void {
    $user = prefUser('transport_manager');

    $response = $this->actingAs($user)->patchJson('/api/v1/notifications/preferences', [
        'preferences' => ['silly_type' => true],
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors('preferences.silly_type');
});

test('validation rejects non-boolean preference values', function (): void {
    $user = prefUser('transport_manager');

    $response = $this->actingAs($user)->patchJson('/api/v1/notifications/preferences', [
        'preferences' => ['compliance_expiring' => 'yes'],
    ]);

    $response->assertStatus(422);
});

test('user cannot modify another users preferences', function (): void {
    $user = prefUser('transport_manager');
    $other = prefUser('transport_manager');
    $preference = NotificationPreference::factory()->create(['user_id' => $other->id]);

    $this->assertFalse(Gate::forUser($user)->allows('update', $preference));
    $this->assertFalse(Gate::forUser($user)->allows('view', $preference));
});

test('user without notifications permission cannot view preferences', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)->getJson('/api/v1/notifications/preferences')->assertStatus(403);
});

test('unauthenticated request to preferences is rejected', function (): void {
    $this->getJson('/api/v1/notifications/preferences')->assertStatus(401);
    $this->patchJson('/api/v1/notifications/preferences', ['preferences' => []])->assertStatus(401);
});

test('preference update creates audit log', function (): void {
    $user = prefUser('transport_manager');
    NotificationPreference::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)->patchJson('/api/v1/notifications/preferences', [
        'preferences' => ['compliance_expiring' => false],
    ]);

    $this->assertDatabaseHas('audit_logs', [
        'action' => 'notification_preferences_updated',
        'actor_id' => $user->id,
    ]);
});

test('scheduler skips muted notification types', function (): void {
    $admin = prefAdmin();
    NotificationPreference::factory()->create([
        'user_id' => $admin->id,
        'preferences' => array_merge(NotificationPreference::defaultPreferences(), [
            'fuel_low_stock' => false,
        ]),
    ]);

    FuelStock::factory()->create([
        'fuel_type' => 'diesel',
        'current_quantity' => 100,
        'minimum_quantity' => 500,
    ]);

    $this->artisan('notifications:check')->assertSuccessful();

    expect(Notification::where('user_id', $admin->id)->where('type', 'fuel_low_stock')->count())->toBe(0);
});

test('scheduler respects per-user preferences', function (): void {
    $admin = prefAdmin();
    NotificationPreference::factory()->create([
        'user_id' => $admin->id,
        'preferences' => array_merge(NotificationPreference::defaultPreferences(), [
            'fuel_low_stock' => false,
        ]),
    ]);

    $attendant = prefUser('fuel_attendant');

    FuelStock::factory()->create([
        'fuel_type' => 'diesel',
        'current_quantity' => 100,
        'minimum_quantity' => 500,
    ]);

    $this->artisan('notifications:check')->assertSuccessful();

    expect(Notification::where('user_id', $admin->id)->where('type', 'fuel_low_stock')->count())->toBe(0);
    expect(Notification::where('user_id', $attendant->id)->where('type', 'fuel_low_stock')->count())->toBe(1);
});

test('enabled notification types continue to generate', function (): void {
    $admin = prefAdmin();
    NotificationPreference::factory()->create([
        'user_id' => $admin->id,
        'preferences' => array_merge(NotificationPreference::defaultPreferences(), [
            'fuel_low_stock' => false,
        ]),
    ]);

    FuelStock::factory()->create([
        'fuel_type' => 'diesel',
        'current_quantity' => 100,
        'minimum_quantity' => 500,
    ]);
    Contract::factory()->active()->create(['end_date' => now()->addDays(14)]);

    $this->artisan('notifications:check')->assertSuccessful();

    expect(Notification::where('user_id', $admin->id)->where('type', 'fuel_low_stock')->count())->toBe(0);
    expect(Notification::where('user_id', $admin->id)->where('type', 'contract_expiring')->count())->toBe(1);
});
