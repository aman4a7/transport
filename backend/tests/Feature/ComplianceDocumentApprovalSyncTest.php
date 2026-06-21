<?php

use App\Domain\Compliance\Models\ComplianceDocument;
use App\Domain\Driver\Models\Driver;
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

test('approving registration document updates vehicle registration_expiry', function (): void {
    $role = Role::where('slug', 'compliance_officer')->first();
    $user = User::factory()->create();
    $user->roles()->attach($role->id, ['assigned_at' => now()]);
    $vehicle = Vehicle::factory()->create();
    $expiryDate = now()->addYear()->toDateString();

    $document = ComplianceDocument::factory()->create([
        'documentable_type' => Vehicle::class,
        'documentable_id' => $vehicle->id,
        'type' => 'vehicle_registration',
        'expires_at' => $expiryDate,
    ]);

    $this->actingAs($user)->postJson("/api/v1/compliance/documents/{$document->id}/approve");

    $vehicle->refresh();
    expect($vehicle->registration_expiry->toDateString())->toBe($expiryDate);
});

test('approving insurance document updates vehicle insurance_expiry', function (): void {
    $role = Role::where('slug', 'compliance_officer')->first();
    $user = User::factory()->create();
    $user->roles()->attach($role->id, ['assigned_at' => now()]);
    $vehicle = Vehicle::factory()->create();
    $expiryDate = now()->addYear()->toDateString();

    $document = ComplianceDocument::factory()->create([
        'documentable_type' => Vehicle::class,
        'documentable_id' => $vehicle->id,
        'type' => 'insurance',
        'expires_at' => $expiryDate,
    ]);

    $this->actingAs($user)->postJson("/api/v1/compliance/documents/{$document->id}/approve");

    $vehicle->refresh();
    expect($vehicle->insurance_expiry->toDateString())->toBe($expiryDate);
});

test('approving driver license document updates driver license_expiry', function (): void {
    $role = Role::where('slug', 'compliance_officer')->first();
    $user = User::factory()->create();
    $user->roles()->attach($role->id, ['assigned_at' => now()]);
    $driver = Driver::factory()->create();
    $expiryDate = now()->addYear()->toDateString();

    $document = ComplianceDocument::factory()->create([
        'documentable_type' => Driver::class,
        'documentable_id' => $driver->id,
        'type' => 'driver_license',
        'expires_at' => $expiryDate,
    ]);

    $this->actingAs($user)->postJson("/api/v1/compliance/documents/{$document->id}/approve");

    $driver->refresh();
    expect($driver->license_expiry->toDateString())->toBe($expiryDate);
});

test('approving document for non-vehicle non-driver entity does not throw', function (): void {
    $role = Role::where('slug', 'compliance_officer')->first();
    $user = User::factory()->create();
    $user->roles()->attach($role->id, ['assigned_at' => now()]);
    $owner = Owner::factory()->create();

    $document = ComplianceDocument::factory()->create([
        'documentable_type' => Owner::class,
        'documentable_id' => $owner->id,
        'type' => 'contract_document',
        'expires_at' => now()->addYear()->toDateString(),
    ]);

    $response = $this->actingAs($user)->postJson("/api/v1/compliance/documents/{$document->id}/approve");

    $response->assertOk();
});

test('vehicle status_changed_at is not modified by expiry sync', function (): void {
    $role = Role::where('slug', 'compliance_officer')->first();
    $user = User::factory()->create();
    $user->roles()->attach($role->id, ['assigned_at' => now()]);
    $vehicle = Vehicle::factory()->create(['status_changed_at' => now()->subMonth()]);
    $originalStatusChangedAt = (string) $vehicle->status_changed_at;

    $document = ComplianceDocument::factory()->create([
        'documentable_type' => Vehicle::class,
        'documentable_id' => $vehicle->id,
        'type' => 'vehicle_registration',
        'expires_at' => now()->addYear()->toDateString(),
    ]);

    $this->actingAs($user)->postJson("/api/v1/compliance/documents/{$document->id}/approve");

    $vehicle->refresh();
    expect((string) $vehicle->status_changed_at)->toBe($originalStatusChangedAt);
});
