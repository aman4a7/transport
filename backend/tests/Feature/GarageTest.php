<?php

use App\Domain\Driver\Models\Driver;
use App\Domain\Garage\Models\MaintenanceRecord;
use App\Domain\Route\Models\Route;
use App\Domain\Trip\Enums\TripStatus;
use App\Domain\Trip\Models\Trip;
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

function garageOfficer(): User
{
    $role = Role::where('slug', 'garage_officer')->first();
    $user = User::factory()->create();
    $user->roles()->attach($role->id, ['assigned_at' => now()]);

    return $user;
}

function defenceVehicle(): Vehicle
{
    return Vehicle::factory()->create(['category' => 'defence_plated', 'status' => 'active']);
}

test('garage officer can list maintenance records', function (): void {
    $user = garageOfficer();
    MaintenanceRecord::factory()->count(3)->create();

    $response = $this->actingAs($user)->getJson('/api/v1/maintenance');

    $response->assertOk()->assertJsonStructure(['success', 'data', 'meta']);
    expect($response['data'])->toHaveCount(3);
});

test('garage officer can create a maintenance record', function (): void {
    $user = garageOfficer();
    $vehicle = defenceVehicle();

    $response = $this->actingAs($user)->postJson('/api/v1/maintenance', [
        'vehicle_id' => $vehicle->id,
        'maintenance_type' => 'scheduled',
        'description' => 'Regular oil change and inspection',
        'scheduled_date' => '2026-07-15',
        'cost' => 500.00,
        'notes' => 'Use synthetic oil',
    ]);

    $response->assertCreated()->assertJson(['success' => true]);
    expect($response['data']['maintenance_type'])->toBe('scheduled');
    expect($response['data']['status'])->toBe('pending');
});

test('garage officer cannot create maintenance for contracted vehicle', function (): void {
    $user = garageOfficer();
    $vehicle = Vehicle::factory()->create(['category' => 'contracted_private', 'status' => 'active']);

    $response = $this->actingAs($user)->postJson('/api/v1/maintenance', [
        'vehicle_id' => $vehicle->id,
        'maintenance_type' => 'repair',
        'description' => 'Engine repair',
        'scheduled_date' => '2026-07-15',
    ]);

    $response->assertStatus(422);
});

test('garage officer can show a maintenance record', function (): void {
    $user = garageOfficer();
    $record = MaintenanceRecord::factory()->create();

    $response = $this->actingAs($user)->getJson("/api/v1/maintenance/{$record->id}");

    $response->assertOk()->assertJsonStructure(['success', 'data']);
});

test('garage officer can update a maintenance record', function (): void {
    $user = garageOfficer();
    $record = MaintenanceRecord::factory()->create();

    $response = $this->actingAs($user)->putJson("/api/v1/maintenance/{$record->id}", [
        'cost' => 750.00,
        'notes' => 'Updated cost estimate',
    ]);

    $response->assertOk()->assertJson(['success' => true]);
    expect($response['data']['cost'])->toBe('750.00');
});

test('garage officer can delete a pending maintenance record', function (): void {
    $user = garageOfficer();
    $record = MaintenanceRecord::factory()->pending()->create();

    $response = $this->actingAs($user)->deleteJson("/api/v1/maintenance/{$record->id}");

    $response->assertOk();
    expect(MaintenanceRecord::find($record->id))->toBeNull();
});

test('garage officer cannot delete an in-progress maintenance record', function (): void {
    $user = garageOfficer();
    $record = MaintenanceRecord::factory()->inProgress()->create();

    $response = $this->actingAs($user)->deleteJson("/api/v1/maintenance/{$record->id}");

    $response->assertStatus(422);
});

test('garage officer can start a pending maintenance record', function (): void {
    $user = garageOfficer();
    $vehicle = defenceVehicle();
    $record = MaintenanceRecord::factory()->pending()->create(['vehicle_id' => $vehicle->id]);

    $response = $this->actingAs($user)->postJson("/api/v1/maintenance/{$record->id}/start");

    $response->assertOk()->assertJson(['success' => true]);
    expect($response['data']['status'])->toBe('in_progress');
    expect($response['data']['started_at'])->not->toBeNull();
});

test('garage officer cannot start maintenance on vehicle with active trip', function (): void {
    $user = garageOfficer();
    $vehicle = defenceVehicle();
    Trip::factory()->create([
        'vehicle_id' => $vehicle->id,
        'status' => TripStatus::InProgress->value,
    ]);
    $record = MaintenanceRecord::factory()->pending()->create(['vehicle_id' => $vehicle->id]);

    $response = $this->actingAs($user)->postJson("/api/v1/maintenance/{$record->id}/start");

    $response->assertStatus(422);
});

test('garage officer can complete an in-progress maintenance record', function (): void {
    $user = garageOfficer();
    $record = MaintenanceRecord::factory()->inProgress()->create();

    $response = $this->actingAs($user)->postJson("/api/v1/maintenance/{$record->id}/complete");

    $response->assertOk()->assertJson(['success' => true]);
    expect($response['data']['status'])->toBe('completed');
    expect($response['data']['completed_at'])->not->toBeNull();
});

test('garage officer can cancel a pending maintenance record', function (): void {
    $user = garageOfficer();
    $record = MaintenanceRecord::factory()->pending()->create();

    $response = $this->actingAs($user)->postJson("/api/v1/maintenance/{$record->id}/cancel");

    $response->assertOk()->assertJson(['success' => true]);
    expect($response['data']['status'])->toBe('cancelled');
});

test('garage officer cannot cancel a completed record', function (): void {
    $user = garageOfficer();
    $record = MaintenanceRecord::factory()->completed()->create();

    $response = $this->actingAs($user)->postJson("/api/v1/maintenance/{$record->id}/cancel");

    $response->assertStatus(422);
});

test('unauthorized user cannot list maintenance records', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->getJson('/api/v1/maintenance');

    $response->assertStatus(403);
});

test('unauthenticated request to maintenance is rejected', function (): void {
    $response = $this->getJson('/api/v1/maintenance');

    $response->assertStatus(401);
});

test('vehicle under maintenance cannot be used for trips', function (): void {
    $role = Role::where('slug', 'transport_manager')->first();
    $user = User::factory()->create();
    $user->roles()->attach($role->id, ['assigned_at' => now()]);
    $vehicle = defenceVehicle();
    $driver = Driver::factory()->create(['status' => 'active']);
    $route = Route::factory()->create(['status' => 'active']);
    MaintenanceRecord::factory()->inProgress()->create(['vehicle_id' => $vehicle->id]);

    $response = $this->actingAs($user)->postJson('/api/v1/trips', [
        'vehicle_id' => $vehicle->id,
        'driver_id' => $driver->id,
        'route_id' => $route->id,
        'scheduled_date' => '2026-07-20',
        'departure_time' => '08:00',
    ]);

    $response->assertStatus(422);
});

test('garage officer cannot update maintenance record to a contracted vehicle', function (): void {
    $user = garageOfficer();
    $record = MaintenanceRecord::factory()->pending()->create();
    $contracted = Vehicle::factory()->create(['category' => 'contracted_private', 'status' => 'active']);

    $response = $this->actingAs($user)->putJson("/api/v1/maintenance/{$record->id}", [
        'vehicle_id' => $contracted->id,
    ]);

    $response->assertStatus(422);
});

test('garage officer cannot update maintenance record to a vehicle with expired registration', function (): void {
    $user = garageOfficer();
    $record = MaintenanceRecord::factory()->pending()->create();
    $vehicle = Vehicle::factory()->create([
        'category' => 'defence_plated',
        'status' => 'active',
        'registration_expiry' => now()->subDay(),
        'insurance_expiry' => now()->addYear(),
    ]);

    $response = $this->actingAs($user)->putJson("/api/v1/maintenance/{$record->id}", [
        'vehicle_id' => $vehicle->id,
    ]);

    $response->assertStatus(422);
});

test('garage officer cannot update maintenance record to a vehicle with expired insurance', function (): void {
    $user = garageOfficer();
    $record = MaintenanceRecord::factory()->pending()->create();
    $vehicle = Vehicle::factory()->create([
        'category' => 'defence_plated',
        'status' => 'active',
        'registration_expiry' => now()->addYear(),
        'insurance_expiry' => now()->subDay(),
    ]);

    $response = $this->actingAs($user)->putJson("/api/v1/maintenance/{$record->id}", [
        'vehicle_id' => $vehicle->id,
    ]);

    $response->assertStatus(422);
});

test('garage officer cannot start maintenance on a non-operational vehicle', function (): void {
    $user = garageOfficer();
    $vehicle = Vehicle::factory()->create(['category' => 'defence_plated', 'status' => 'in_maintenance']);
    $record = MaintenanceRecord::factory()->pending()->create(['vehicle_id' => $vehicle->id]);

    $response = $this->actingAs($user)->postJson("/api/v1/maintenance/{$record->id}/start");

    $response->assertStatus(422);
});

test('garage officer cannot start maintenance on a vehicle with expired registration', function (): void {
    $user = garageOfficer();
    $vehicle = Vehicle::factory()->create([
        'category' => 'defence_plated',
        'status' => 'active',
        'registration_expiry' => now()->subDay(),
        'insurance_expiry' => now()->addYear(),
    ]);
    $record = MaintenanceRecord::factory()->pending()->create(['vehicle_id' => $vehicle->id]);

    $response = $this->actingAs($user)->postJson("/api/v1/maintenance/{$record->id}/start");

    $response->assertStatus(422);
});
