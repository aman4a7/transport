<?php

use App\Domain\Passenger\Models\Passenger;
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

test('transport manager can list passengers', function (): void {
    $role = Role::where('slug', 'transport_manager')->first();
    $user = User::factory()->create();
    $user->roles()->attach($role->id, ['assigned_at' => now()]);
    Passenger::factory()->create(['first_name' => 'John', 'last_name' => 'Doe']);

    $response = $this->actingAs($user)->getJson('/api/v1/passengers');

    $response->assertOk()->assertJsonStructure(['success', 'data', 'meta']);
    expect($response['data'])->toHaveCount(1);
});

test('transport manager can create a passenger', function (): void {
    $role = Role::where('slug', 'transport_manager')->first();
    $user = User::factory()->create();
    $user->roles()->attach($role->id, ['assigned_at' => now()]);

    $response = $this->actingAs($user)->postJson('/api/v1/passengers', [
        'first_name' => 'Jane',
        'last_name' => 'Smith',
        'email' => 'jane.smith@university.edu.et',
    ]);

    $response->assertCreated()->assertJson(['success' => true]);
    expect($response['data']['first_name'])->toBe('Jane');
});

test('transport manager can show a passenger', function (): void {
    $role = Role::where('slug', 'transport_manager')->first();
    $user = User::factory()->create();
    $user->roles()->attach($role->id, ['assigned_at' => now()]);
    $passenger = Passenger::factory()->create();

    $response = $this->actingAs($user)->getJson("/api/v1/passengers/{$passenger->id}");

    $response->assertOk()->assertJsonStructure(['success', 'data']);
});

test('transport manager can update a passenger', function (): void {
    $role = Role::where('slug', 'transport_manager')->first();
    $user = User::factory()->create();
    $user->roles()->attach($role->id, ['assigned_at' => now()]);
    $passenger = Passenger::factory()->create();

    $response = $this->actingAs($user)->putJson("/api/v1/passengers/{$passenger->id}", [
        'first_name' => 'Updated',
    ]);

    $response->assertOk()->assertJson(['success' => true]);
    expect($response['data']['first_name'])->toBe('Updated');
});

test('transport manager can delete a passenger', function (): void {
    $role = Role::where('slug', 'transport_manager')->first();
    $user = User::factory()->create();
    $user->roles()->attach($role->id, ['assigned_at' => now()]);
    $passenger = Passenger::factory()->create();

    $response = $this->actingAs($user)->deleteJson("/api/v1/passengers/{$passenger->id}");

    $response->assertOk()->assertJson(['success' => true]);
    expect(Passenger::find($passenger->id))->toBeNull();
});

test('unauthorized user cannot list passengers', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->getJson('/api/v1/passengers');

    $response->assertStatus(403);
});

test('unauthenticated request to passengers is rejected', function (): void {
    $response = $this->getJson('/api/v1/passengers');

    $response->assertStatus(401);
});

test('creating passenger with duplicate email fails', function (): void {
    $role = Role::where('slug', 'transport_manager')->first();
    $user = User::factory()->create();
    $user->roles()->attach($role->id, ['assigned_at' => now()]);

    Passenger::factory()->create(['email' => 'dupe@university.edu.et']);

    $response = $this->actingAs($user)->postJson('/api/v1/passengers', [
        'first_name' => 'Another',
        'last_name' => 'Person',
        'email' => 'dupe@university.edu.et',
    ]);

    $response->assertStatus(422);
});

test('creating passenger with duplicate employee_id fails', function (): void {
    $role = Role::where('slug', 'transport_manager')->first();
    $user = User::factory()->create();
    $user->roles()->attach($role->id, ['assigned_at' => now()]);

    Passenger::factory()->create(['employee_id' => 'EMP-001', 'email' => 'a@b.com']);

    $response = $this->actingAs($user)->postJson('/api/v1/passengers', [
        'first_name' => 'Another',
        'last_name' => 'Person',
        'email' => 'another@b.com',
        'employee_id' => 'EMP-001',
    ]);

    $response->assertStatus(422);
});
