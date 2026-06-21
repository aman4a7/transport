<?php

use App\Domain\Owner\Models\Owner;
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

test('transport manager can list owners', function (): void {
    $role = Role::where('slug', 'transport_manager')->first();
    $user = User::factory()->create();
    $user->roles()->attach($role->id, ['assigned_at' => now()]);
    Owner::factory()->create(['company_name' => 'Test Transport Co']);

    $response = $this->actingAs($user)->getJson('/api/v1/owners');

    $response->assertOk()->assertJsonStructure(['success', 'data', 'meta']);
    expect($response['data'])->toHaveCount(1);
});

test('transport manager can create an owner', function (): void {
    $role = Role::where('slug', 'transport_manager')->first();
    $user = User::factory()->create();
    $user->roles()->attach($role->id, ['assigned_at' => now()]);

    $response = $this->actingAs($user)->postJson('/api/v1/owners', [
        'company_name' => 'New Transport Plc',
        'contact_person' => 'John Doe',
        'phone' => '+251-911-000000',
        'email' => 'contact@newtransport.et',
    ]);

    $response->assertCreated()->assertJson(['success' => true]);
    expect($response['data']['company_name'])->toBe('New Transport Plc');
});

test('transport manager can show an owner', function (): void {
    $role = Role::where('slug', 'transport_manager')->first();
    $user = User::factory()->create();
    $user->roles()->attach($role->id, ['assigned_at' => now()]);
    $owner = Owner::factory()->create();

    $response = $this->actingAs($user)->getJson("/api/v1/owners/{$owner->id}");

    $response->assertOk()->assertJsonStructure(['success', 'data']);
});

test('transport manager can update an owner', function (): void {
    $role = Role::where('slug', 'transport_manager')->first();
    $user = User::factory()->create();
    $user->roles()->attach($role->id, ['assigned_at' => now()]);
    $owner = Owner::factory()->create();

    $response = $this->actingAs($user)->putJson("/api/v1/owners/{$owner->id}", [
        'contact_person' => 'Jane Smith',
    ]);

    $response->assertOk()->assertJson(['success' => true]);
    expect($response['data']['contact_person'])->toBe('Jane Smith');
});

test('transport manager can delete an owner', function (): void {
    $role = Role::where('slug', 'transport_manager')->first();
    $user = User::factory()->create();
    $user->roles()->attach($role->id, ['assigned_at' => now()]);
    $owner = Owner::factory()->create();

    $response = $this->actingAs($user)->deleteJson("/api/v1/owners/{$owner->id}");

    $response->assertOk()->assertJson(['success' => true]);
    expect(Owner::find($owner->id))->toBeNull();
});

test('unauthorized user cannot list owners', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->getJson('/api/v1/owners');

    $response->assertStatus(403);
});

test('unauthenticated request to owners is rejected', function (): void {
    $response = $this->getJson('/api/v1/owners');

    $response->assertStatus(401);
});

test('creating owner with duplicate email fails', function (): void {
    $role = Role::where('slug', 'transport_manager')->first();
    $user = User::factory()->create();
    $user->roles()->attach($role->id, ['assigned_at' => now()]);

    Owner::factory()->create(['email' => 'dupe@example.com']);

    $response = $this->actingAs($user)->postJson('/api/v1/owners', [
        'company_name' => 'Another Co',
        'contact_person' => 'Test Person',
        'phone' => '+251-911-111111',
        'email' => 'dupe@example.com',
    ]);

    $response->assertStatus(422);
});
