<?php

use App\Domain\Owner\Models\Owner;
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

test('transport manager can list vehicles', function (): void {
    $role = Role::where('slug', 'transport_manager')->first();
    $user = User::factory()->create();
    $user->roles()->attach($role->id, ['assigned_at' => now()]);
    Vehicle::factory()->create(['category' => 'defence_plated', 'plate_number' => 'DEF-001']);

    $response = $this->actingAs($user)->getJson('/api/v1/vehicles');

    $response->assertOk()->assertJsonStructure(['success', 'data', 'meta']);
    expect($response['data'])->toHaveCount(1);
    expect($response['data'][0]['plate_number'])->toBe('DEF-001');
});

test('transport manager can create a defence_plated vehicle', function (): void {
    $role = Role::where('slug', 'transport_manager')->first();
    $user = User::factory()->create();
    $user->roles()->attach($role->id, ['assigned_at' => now()]);

    $response = $this->actingAs($user)->postJson('/api/v1/vehicles', [
        'plate_number' => 'DEF-002',
        'make' => 'Toyota',
        'model' => 'Hiace',
        'year' => 2023,
        'category' => 'defence_plated',
        'fuel_type' => 'diesel',
    ]);

    $response->assertCreated()->assertJson(['success' => true, 'message' => 'Vehicle created successfully.']);
    expect($response['data']['plate_number'])->toBe('DEF-002');
});

test('creating contracted_private vehicle without owner_id fails', function (): void {
    $role = Role::where('slug', 'transport_manager')->first();
    $user = User::factory()->create();
    $user->roles()->attach($role->id, ['assigned_at' => now()]);

    $response = $this->actingAs($user)->postJson('/api/v1/vehicles', [
        'plate_number' => 'PRV-001',
        'make' => 'Isuzu',
        'model' => 'Fuso',
        'year' => 2022,
        'category' => 'contracted_private',
    ]);

    $response->assertStatus(422);
});

test('transport manager can create contracted_private vehicle with owner', function (): void {
    $role = Role::where('slug', 'transport_manager')->first();
    $user = User::factory()->create();
    $user->roles()->attach($role->id, ['assigned_at' => now()]);

    $owner = Owner::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/v1/vehicles', [
        'plate_number' => 'PRV-002',
        'make' => 'Isuzu',
        'model' => 'Fuso',
        'year' => 2022,
        'category' => 'contracted_private',
        'owner_id' => $owner->id,
    ]);

    $response->assertCreated();
    expect($response['data']['owner_id'])->toBe($owner->id);
});

test('transport manager can show a vehicle', function (): void {
    $role = Role::where('slug', 'transport_manager')->first();
    $user = User::factory()->create();
    $user->roles()->attach($role->id, ['assigned_at' => now()]);
    $vehicle = Vehicle::factory()->create();

    $response = $this->actingAs($user)->getJson("/api/v1/vehicles/{$vehicle->id}");

    $response->assertOk()->assertJsonStructure(['success', 'data']);
});

test('transport manager can update a vehicle', function (): void {
    $role = Role::where('slug', 'transport_manager')->first();
    $user = User::factory()->create();
    $user->roles()->attach($role->id, ['assigned_at' => now()]);
    $vehicle = Vehicle::factory()->create();

    $response = $this->actingAs($user)->putJson("/api/v1/vehicles/{$vehicle->id}", [
        'color' => 'White',
    ]);

    $response->assertOk()->assertJson(['success' => true]);
    expect($response['data']['color'])->toBe('White');
});

test('transport manager can delete a vehicle', function (): void {
    $role = Role::where('slug', 'transport_manager')->first();
    $user = User::factory()->create();
    $user->roles()->attach($role->id, ['assigned_at' => now()]);
    $vehicle = Vehicle::factory()->create();

    $response = $this->actingAs($user)->deleteJson("/api/v1/vehicles/{$vehicle->id}");

    $response->assertOk()->assertJson(['success' => true, 'message' => 'Vehicle deleted successfully.']);
    expect(Vehicle::find($vehicle->id))->toBeNull();
});

test('unauthorized user cannot list vehicles', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->getJson('/api/v1/vehicles');

    $response->assertStatus(403);
});

test('unauthenticated request to vehicles is rejected', function (): void {
    $response = $this->getJson('/api/v1/vehicles');

    $response->assertStatus(401);
});
