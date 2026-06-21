<?php

use App\Domain\{Module}\Models\{Model};
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

test('{role} can list {entities}', function (): void {
    $role = Role::where('slug', '{role_slug}')->first();
    $user = User::factory()->create();
    $user->roles()->attach($role->id, ['assigned_at' => now()]);
    {Model}::factory()->create();

    $response = $this->actingAs($user)->getJson('/api/v1/{entities}');

    $response->assertOk()->assertJsonStructure(['success', 'data', 'meta']);
});

test('{role} can create a {entity}', function (): void {
    $role = Role::where('slug', '{role_slug}')->first();
    $user = User::factory()->create();
    $user->roles()->attach($role->id, ['assigned_at' => now()]);

    $response = $this->actingAs($user)->postJson('/api/v1/{entities}', [
        // required fields
    ]);

    $response->assertCreated()->assertJson(['success' => true]);
});

test('creating {entity} without required field fails validation', function (): void {
    $role = Role::where('slug', '{role_slug}')->first();
    $user = User::factory()->create();
    $user->roles()->attach($role->id, ['assigned_at' => now()]);

    $response = $this->actingAs($user)->postJson('/api/v1/{entities}', []);

    $response->assertStatus(422);
});

test('{role} can show a {entity}', function (): void {
    $role = Role::where('slug', '{role_slug}')->first();
    $user = User::factory()->create();
    $user->roles()->attach($role->id, ['assigned_at' => now()]);
    ${entity} = {Model}::factory()->create();

    $response = $this->actingAs($user)->getJson("/api/v1/{entities}/{{$entity}->id}");

    $response->assertOk()->assertJsonStructure(['success', 'data']);
});

test('{role} can update a {entity}', function (): void {
    $role = Role::where('slug', '{role_slug}')->first();
    $user = User::factory()->create();
    $user->roles()->attach($role->id, ['assigned_at' => now()]);
    ${entity} = {Model}::factory()->create();

    $response = $this->actingAs($user)->putJson("/api/v1/{entities}/{{$entity}->id}", [
        'field' => 'new value',
    ]);

    $response->assertOk()->assertJson(['success' => true]);
});

test('{role} can delete a {entity}', function (): void {
    $role = Role::where('slug', '{role_slug}')->first();
    $user = User::factory()->create();
    $user->roles()->attach($role->id, ['assigned_at' => now()]);
    ${entity} = {Model}::factory()->create();

    $response = $this->actingAs($user)->deleteJson("/api/v1/{entities}/{{$entity}->id}");

    $response->assertOk()->assertJson(['success' => true]);
});

test('unauthorized user cannot list {entities}', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->getJson('/api/v1/{entities}');

    $response->assertStatus(403);
});

test('unauthenticated request to {entities} is rejected', function (): void {
    $response = $this->getJson('/api/v1/{entities}');

    $response->assertStatus(401);
});
