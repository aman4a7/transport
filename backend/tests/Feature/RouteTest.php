<?php

use App\Domain\Route\Models\Route;
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

test('transport manager can list routes', function (): void {
    $role = Role::where('slug', 'transport_manager')->first();
    $user = User::factory()->create();
    $user->roles()->attach($role->id, ['assigned_at' => now()]);
    Route::factory()->create(['name' => 'Addis - Bahir Dar']);

    $response = $this->actingAs($user)->getJson('/api/v1/routes');

    $response->assertOk()->assertJsonStructure(['success', 'data', 'meta']);
    expect($response['data'])->toHaveCount(1);
    expect($response['data'][0]['name'])->toBe('Addis - Bahir Dar');
});

test('transport manager can create a route', function (): void {
    $role = Role::where('slug', 'transport_manager')->first();
    $user = User::factory()->create();
    $user->roles()->attach($role->id, ['assigned_at' => now()]);

    $response = $this->actingAs($user)->postJson('/api/v1/routes', [
        'name' => 'Addis Ababa - Bahir Dar',
        'code' => 'RTE-001',
        'origin' => 'Addis Ababa',
        'destination' => 'Bahir Dar',
        'distance_km' => 560.5,
        'estimated_duration_minutes' => 420,
        'capacity' => 40,
    ]);

    $response->assertCreated()->assertJson(['success' => true, 'message' => 'Route created successfully.']);
    expect($response['data']['name'])->toBe('Addis Ababa - Bahir Dar');
});

test('creating route with duplicate code fails', function (): void {
    $role = Role::where('slug', 'transport_manager')->first();
    $user = User::factory()->create();
    $user->roles()->attach($role->id, ['assigned_at' => now()]);
    Route::factory()->create(['code' => 'RTE-001']);

    $response = $this->actingAs($user)->postJson('/api/v1/routes', [
        'name' => 'Another Route',
        'code' => 'RTE-001',
        'origin' => 'City A',
        'destination' => 'City B',
    ]);

    $response->assertStatus(422);
});

test('creating route without required fields fails', function (): void {
    $role = Role::where('slug', 'transport_manager')->first();
    $user = User::factory()->create();
    $user->roles()->attach($role->id, ['assigned_at' => now()]);

    $response = $this->actingAs($user)->postJson('/api/v1/routes', []);

    $response->assertStatus(422);
});

test('transport manager can show a route', function (): void {
    $role = Role::where('slug', 'transport_manager')->first();
    $user = User::factory()->create();
    $user->roles()->attach($role->id, ['assigned_at' => now()]);
    $route = Route::factory()->create();

    $response = $this->actingAs($user)->getJson("/api/v1/routes/{$route->id}");

    $response->assertOk()->assertJsonStructure(['success', 'data']);
});

test('transport manager can update a route', function (): void {
    $role = Role::where('slug', 'transport_manager')->first();
    $user = User::factory()->create();
    $user->roles()->attach($role->id, ['assigned_at' => now()]);
    $route = Route::factory()->create();

    $response = $this->actingAs($user)->putJson("/api/v1/routes/{$route->id}", [
        'capacity' => 50,
    ]);

    $response->assertOk()->assertJson(['success' => true]);
    expect($response['data']['capacity'])->toBe(50);
});

test('transport manager can delete a route', function (): void {
    $role = Role::where('slug', 'transport_manager')->first();
    $user = User::factory()->create();
    $user->roles()->attach($role->id, ['assigned_at' => now()]);
    $route = Route::factory()->create();

    $response = $this->actingAs($user)->deleteJson("/api/v1/routes/{$route->id}");

    $response->assertOk()->assertJson(['success' => true, 'message' => 'Route deleted successfully.']);
    expect(Route::find($route->id))->toBeNull();
});

test('unauthorized user cannot list routes', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->getJson('/api/v1/routes');

    $response->assertStatus(403);
});

test('unauthenticated request to routes is rejected', function (): void {
    $response = $this->getJson('/api/v1/routes');

    $response->assertStatus(401);
});
