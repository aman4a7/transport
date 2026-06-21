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

test('login succeeds with valid credentials', function (): void {
    $role = Role::where('slug', 'transport_manager')->first();
    $user = User::factory()->create([
        'email' => 'transport@example.com',
        'password' => bcrypt('password'),
    ]);
    $user->roles()->attach($role->id, ['assigned_at' => now()]);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'transport@example.com',
        'password' => 'password',
    ]);

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'message' => 'Login successful.',
        ])
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'id',
                'name',
                'email',
                'roles',
            ],
        ]);

    expect($response['data']['email'])->toBe('transport@example.com');
    expect($response['data']['roles'])->toHaveCount(1);
    expect($response['data']['roles'][0]['slug'])->toBe('transport_manager');
    expect($response['data']['roles'][0]['permissions'])->toHaveCount(27);
    expect($response['data']['roles'][0]['permissions'][0])->toHaveKeys(['id', 'name', 'slug']);
});

test('login fails with invalid credentials', function (): void {
    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'nonexistent@example.com',
        'password' => 'wrong-password',
    ]);

    $response->assertStatus(422)
        ->assertJson([
            'success' => false,
        ])
        ->assertJsonStructure([
            'success',
            'message',
        ]);
});

test('login fails with missing fields', function (): void {
    $response = $this->postJson('/api/v1/auth/login', []);

    $response->assertStatus(422)
        ->assertJsonStructure([
            'message',
            'errors' => ['email', 'password'],
        ]);
});

test('unauthenticated access to me returns error', function (): void {
    $response = $this->getJson('/api/v1/auth/me');

    $response->assertStatus(401);
});

test('authenticated user can access me endpoint', function (): void {
    $role = Role::where('slug', 'system_administrator')->first();
    $user = User::factory()->create([
        'email' => 'admin@example.com',
    ]);
    $user->roles()->attach($role->id, ['assigned_at' => now()]);

    $response = $this->actingAs($user)->getJson('/api/v1/auth/me');

    $response->assertOk()
        ->assertJson([
            'success' => true,
        ])
        ->assertJsonStructure([
            'success',
            'data' => [
                'id',
                'name',
                'email',
                'roles',
            ],
        ]);

    expect($response['data']['email'])->toBe('admin@example.com');
    expect($response['data']['roles'])->toHaveCount(1);
    expect($response['data']['roles'][0]['slug'])->toBe('system_administrator');
    expect($response['data']['roles'][0]['permissions'])->toHaveCount(57);
});

test('logout succeeds when authenticated', function (): void {
    $user = User::factory()->create([
        'email' => 'driver@example.com',
    ]);

    $response = $this->actingAs($user)->postJson('/api/v1/auth/logout');

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'message' => 'Logged out successfully.',
        ]);
});

test('logout fails when unauthenticated', function (): void {
    $response = $this->postJson('/api/v1/auth/logout');

    $response->assertStatus(401);
});

test('can login and then access me endpoint', function (): void {
    $role = Role::where('slug', 'transport_manager')->first();
    $user = User::factory()->create([
        'email' => 'operator@example.com',
        'password' => bcrypt('password'),
    ]);
    $user->roles()->attach($role->id, ['assigned_at' => now()]);

    $this->postJson('/api/v1/auth/login', [
        'email' => 'operator@example.com',
        'password' => 'password',
    ]);

    $response = $this->getJson('/api/v1/auth/me');

    $response->assertOk();
    expect($response['data']['email'])->toBe('operator@example.com');
});

test('login is throttled after 5 attempts', function (): void {
    for ($i = 0; $i < 5; $i++) {
        $this->postJson('/api/v1/auth/login', [
            'email' => 'nobody@example.com',
            'password' => 'wrong-password',
        ]);
    }

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'nobody@example.com',
        'password' => 'wrong-password',
    ]);

    $response->assertStatus(429);
});
