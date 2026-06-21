<?php

use App\Domain\Compliance\Models\ComplianceDocument;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed([RoleSeeder::class, PermissionSeeder::class, RolePermissionSeeder::class]);
});

test('compliance officer can approve a pending document', function (): void {
    $role = Role::where('slug', 'compliance_officer')->first();
    $user = User::factory()->create();
    $user->roles()->attach($role->id, ['assigned_at' => now()]);

    $document = ComplianceDocument::factory()->create(['status' => 'pending']);

    $response = $this->actingAs($user)->patchJson("/api/v1/compliance/documents/{$document->id}/approve");

    $response->assertOk();
    expect($response['data']['status'])->toBe('approved');
});

test('transport manager cannot approve documents', function (): void {
    $role = Role::where('slug', 'transport_manager')->first();
    $user = User::factory()->create();
    $user->roles()->attach($role->id, ['assigned_at' => now()]);

    $document = ComplianceDocument::factory()->create(['status' => 'pending']);

    $response = $this->actingAs($user)->patchJson("/api/v1/compliance/documents/{$document->id}/approve");

    $response->assertStatus(403);
});
