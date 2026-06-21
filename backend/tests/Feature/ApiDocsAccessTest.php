<?php

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

test('system administrator can access api docs outside local environment', function (): void {
    app()->detectEnvironment(fn () => 'production');

    $role = Role::where('slug', 'system_administrator')->first();
    $user = User::factory()->create();
    $user->roles()->attach($role->id, ['assigned_at' => now()]);

    $response = $this->actingAs($user)->get('/docs/api');

    $response->assertOk();
});

test('non-admin user cannot access api docs outside local environment', function (): void {
    app()->detectEnvironment(fn () => 'production');

    $role = Role::where('slug', 'driver')->first();
    $user = User::factory()->create();
    $user->roles()->attach($role->id, ['assigned_at' => now()]);

    $response = $this->actingAs($user)->get('/docs/api');

    $response->assertStatus(403);
});

test('unauthenticated user cannot access api docs outside local environment', function (): void {
    app()->detectEnvironment(fn () => 'production');

    $response = $this->get('/docs/api');

    $response->assertStatus(403);
});
