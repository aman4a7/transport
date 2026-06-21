<?php

use App\Domain\Driver\Models\Driver;
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

test('transport manager can list drivers', function (): void {
    $role = Role::where('slug', 'transport_manager')->first();
    $user = User::factory()->create();
    $user->roles()->attach($role->id, ['assigned_at' => now()]);
    Driver::factory()->create(['license_number' => 'LIC-001']);

    $response = $this->actingAs($user)->getJson('/api/v1/drivers');

    $response->assertOk()->assertJsonStructure(['success', 'data', 'meta']);
    expect($response['data'])->toHaveCount(1);
});

test('transport manager can create a driver', function (): void {
    $role = Role::where('slug', 'transport_manager')->first();
    $user = User::factory()->create();
    $user->roles()->attach($role->id, ['assigned_at' => now()]);

    $response = $this->actingAs($user)->postJson('/api/v1/drivers', [
        'license_number' => 'LIC-002',
        'license_category' => 'heavy',
        'license_expiry' => '2028-12-31',
    ]);

    $response->assertCreated()->assertJson(['success' => true]);
    expect($response['data']['license_number'])->toBe('LIC-002');
});

test('transport manager can show a driver', function (): void {
    $role = Role::where('slug', 'transport_manager')->first();
    $user = User::factory()->create();
    $user->roles()->attach($role->id, ['assigned_at' => now()]);
    $driver = Driver::factory()->create();

    $response = $this->actingAs($user)->getJson("/api/v1/drivers/{$driver->id}");

    $response->assertOk()->assertJsonStructure(['success', 'data']);
});

test('transport manager can update a driver', function (): void {
    $role = Role::where('slug', 'transport_manager')->first();
    $user = User::factory()->create();
    $user->roles()->attach($role->id, ['assigned_at' => now()]);
    $driver = Driver::factory()->create();

    $response = $this->actingAs($user)->putJson("/api/v1/drivers/{$driver->id}", [
        'license_category' => 'light',
    ]);

    $response->assertOk()->assertJson(['success' => true]);
    expect($response['data']['license_category'])->toBe('light');
});

test('transport manager can delete a driver', function (): void {
    $role = Role::where('slug', 'transport_manager')->first();
    $user = User::factory()->create();
    $user->roles()->attach($role->id, ['assigned_at' => now()]);
    $driver = Driver::factory()->create();

    $response = $this->actingAs($user)->deleteJson("/api/v1/drivers/{$driver->id}");

    $response->assertOk()->assertJson(['success' => true]);
    expect(Driver::find($driver->id))->toBeNull();
});

test('unauthorized user cannot list drivers', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->getJson('/api/v1/drivers');

    $response->assertStatus(403);
});

test('unauthenticated request to drivers is rejected', function (): void {
    $response = $this->getJson('/api/v1/drivers');

    $response->assertStatus(401);
});
