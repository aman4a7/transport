<?php

use App\Domain\Compliance\Enums\ComplianceDocumentType;
use App\Domain\Compliance\Models\ComplianceDocument;
use App\Domain\Contract\Models\Contract;
use App\Domain\Fuel\Models\FuelStock;
use App\Domain\Garage\Models\MaintenanceRecord;
use App\Domain\Notification\Enums\NotificationType;
use App\Domain\Notification\Models\Notification;
use App\Domain\Notification\Services\NotificationService;
use App\Domain\Vehicle\Models\Vehicle;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed([
        RoleSeeder::class,
        PermissionSeeder::class,
        RolePermissionSeeder::class,
    ]);
});

function actingAsRole(string $slug): User
{
    $role = Role::where('slug', $slug)->first();
    $user = User::factory()->create();
    $user->roles()->attach($role->id, ['assigned_at' => now()]);

    return $user;
}

function actingAsSystemAdministrator(): User
{
    return actingAsRole('system_administrator');
}

test('unauthenticated request to notifications is rejected', function (): void {
    $this->getJson('/api/v1/notifications')->assertStatus(401);
    $this->patchJson('/api/v1/notifications/read-all')->assertStatus(401);
});

test('user without notifications permission cannot view notifications', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)->getJson('/api/v1/notifications')->assertStatus(403);
});

test('user can list their own notifications', function (): void {
    $user = actingAsRole('transport_manager');
    Notification::factory()->count(3)->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->getJson('/api/v1/notifications');

    $response->assertOk()->assertJsonStructure(['success', 'data', 'meta']);
    expect($response['data'])->toHaveCount(3);
    expect($response['meta']['unread_count'])->toBe(3);
});

test('user only sees their own notifications', function (): void {
    $user = actingAsRole('transport_manager');
    $other = actingAsRole('transport_manager');
    Notification::factory()->count(2)->create(['user_id' => $user->id]);
    Notification::factory()->count(1)->create(['user_id' => $other->id]);

    $response = $this->actingAs($user)->getJson('/api/v1/notifications');

    $response->assertOk();
    expect($response['data'])->toHaveCount(2);
});

test('index reports unread count excluding read notifications', function (): void {
    $user = actingAsRole('transport_manager');
    Notification::factory()->count(2)->create(['user_id' => $user->id, 'read_at' => null]);
    Notification::factory()->count(1)->create(['user_id' => $user->id, 'read_at' => now()]);

    $response = $this->actingAs($user)->getJson('/api/v1/notifications');

    expect($response['data'])->toHaveCount(3);
    expect($response['meta']['unread_count'])->toBe(2);
});

test('index can filter by unread status', function (): void {
    $user = actingAsRole('transport_manager');
    Notification::factory()->count(1)->create(['user_id' => $user->id, 'read_at' => null]);
    Notification::factory()->count(1)->create(['user_id' => $user->id, 'read_at' => now()]);

    $response = $this->actingAs($user)->getJson('/api/v1/notifications?status=unread');

    $response->assertOk();
    expect($response['data'])->toHaveCount(1);
    expect($response['data'][0]['read_at'])->toBeNull();
});

test('user can mark a notification as read', function (): void {
    $user = actingAsRole('transport_manager');
    $notification = Notification::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->patchJson("/api/v1/notifications/{$notification->id}/read");

    $response->assertOk()->assertJson(['success' => true]);
    expect($response['data']['read_at'])->not->toBeNull();
    expect(Notification::find($notification->id)->isRead())->toBeTrue();
});

test('user cannot mark another users notification as read', function (): void {
    $user = actingAsRole('transport_manager');
    $other = actingAsRole('transport_manager');
    $notification = Notification::factory()->create(['user_id' => $other->id]);

    $this->actingAs($user)->patchJson("/api/v1/notifications/{$notification->id}/read")->assertStatus(403);
});

test('user can mark all notifications as read', function (): void {
    $user = actingAsRole('transport_manager');
    Notification::factory()->count(3)->create(['user_id' => $user->id, 'read_at' => null]);
    Notification::factory()->count(1)->create(['user_id' => $user->id, 'read_at' => now()]);

    $response = $this->actingAs($user)->patchJson('/api/v1/notifications/read-all');

    $response->assertOk()->assertJson(['success' => true, 'data' => ['marked' => 3]]);
    expect(Notification::where('user_id', $user->id)->whereNull('read_at')->count())->toBe(0);
});

test('mark all read is a no-op when nothing is unread', function (): void {
    $user = actingAsRole('transport_manager');
    Notification::factory()->count(2)->create(['user_id' => $user->id, 'read_at' => now()]);

    $response = $this->actingAs($user)->patchJson('/api/v1/notifications/read-all');

    $response->assertOk();
    expect($response['data']['marked'])->toBe(0);
});

test('notification service creates a notification', function (): void {
    $user = actingAsRole('transport_manager');
    $service = app(NotificationService::class);

    $notification = $service->create(
        $user->id,
        NotificationType::ComplianceExpiring,
        'Compliance expiring: DEF-001',
        'Expires tomorrow.',
        ['entity_type' => 'compliance_document', 'entity_id' => 42],
    );

    expect($notification->type)->toBe(NotificationType::ComplianceExpiring);
    expect($notification->user_id)->toBe($user->id);
    expect($notification->isRead())->toBeFalse();
    $this->assertDatabaseHas('notifications', ['id' => $notification->id, 'type' => 'compliance_expiring']);
});

test('createUnique does not duplicate unread notifications for the same entity', function (): void {
    $user = actingAsRole('transport_manager');
    $service = app(NotificationService::class);
    $data = ['entity_type' => 'vehicle', 'entity_id' => 7];

    $service->createUnique($user->id, NotificationType::FuelLowStock, 'Low', null, $data);
    $created = $service->createUnique($user->id, NotificationType::FuelLowStock, 'Low', null, $data);

    expect($created)->toBeNull();
    expect(Notification::where('user_id', $user->id)->count())->toBe(1);
});

test('notifications:check creates compliance expiring notifications', function (): void {
    $admin = actingAsSystemAdministrator();
    $vehicle = Vehicle::factory()->create(['category' => 'defence_plated', 'status' => 'active']);
    ComplianceDocument::factory()->approved()->create([
        'documentable_type' => Vehicle::class,
        'documentable_id' => $vehicle->id,
        'type' => ComplianceDocumentType::VehicleRegistration,
        'expires_at' => now()->addDays(10),
    ]);

    $this->artisan('notifications:check')->assertSuccessful();

    $this->assertDatabaseHas('notifications', [
        'user_id' => $admin->id,
        'type' => 'compliance_expiring',
    ]);
});

test('notifications:check ignores compliance documents expiring beyond the window', function (): void {
    actingAsSystemAdministrator();
    $vehicle = Vehicle::factory()->create(['category' => 'defence_plated', 'status' => 'active']);
    ComplianceDocument::factory()->approved()->create([
        'documentable_type' => Vehicle::class,
        'documentable_id' => $vehicle->id,
        'type' => ComplianceDocumentType::VehicleRegistration,
        'expires_at' => now()->addDays(60),
    ]);

    $this->artisan('notifications:check')->assertSuccessful();

    expect(Notification::where('type', 'compliance_expiring')->count())->toBe(0);
});

test('notifications:check creates contract expiring notifications', function (): void {
    $admin = actingAsSystemAdministrator();
    Contract::factory()->active()->create(['end_date' => now()->addDays(14)]);

    $this->artisan('notifications:check')->assertSuccessful();

    $this->assertDatabaseHas('notifications', [
        'user_id' => $admin->id,
        'type' => 'contract_expiring',
    ]);
});

test('notifications:check creates maintenance due notifications', function (): void {
    $admin = actingAsSystemAdministrator();
    $vehicle = Vehicle::factory()->create(['category' => 'defence_plated', 'status' => 'active']);
    MaintenanceRecord::factory()->pending()->create([
        'vehicle_id' => $vehicle->id,
        'scheduled_date' => now()->addDays(3),
    ]);

    $this->artisan('notifications:check')->assertSuccessful();

    $this->assertDatabaseHas('notifications', [
        'user_id' => $admin->id,
        'type' => 'maintenance_due',
    ]);
});

test('notifications:check creates fuel low stock notifications', function (): void {
    $admin = actingAsSystemAdministrator();
    FuelStock::factory()->create([
        'fuel_type' => 'diesel',
        'current_quantity' => 100,
        'minimum_quantity' => 500,
    ]);

    $this->artisan('notifications:check')->assertSuccessful();

    $this->assertDatabaseHas('notifications', [
        'user_id' => $admin->id,
        'type' => 'fuel_low_stock',
    ]);
});

test('notifications:check is idempotent', function (): void {
    actingAsSystemAdministrator();
    Contract::factory()->active()->create(['end_date' => now()->addDays(14)]);

    $this->artisan('notifications:check')->assertSuccessful();
    $this->artisan('notifications:check')->assertSuccessful();

    expect(Notification::where('type', 'contract_expiring')->count())->toBe(1);
});

test('notifications:check does not create notifications when no recipients exist', function (): void {
    Contract::factory()->active()->create(['end_date' => now()->addDays(14)]);

    $this->artisan('notifications:check')->assertSuccessful();

    expect(Notification::count())->toBe(0);
});

test('nonexistent notification returns 404 with standard API envelope', function (): void {
    $user = actingAsRole('transport_manager');

    $response = $this->actingAs($user)->patchJson('/api/v1/notifications/99999/read');

    $response->assertStatus(404);
    $response->assertJsonStructure([
        'success',
        'data',
        'message',
    ]);
    expect($response['success'])->toBeFalse();
    expect($response['data'])->toBeNull();
});
