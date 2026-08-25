<?php

use App\Domain\Compliance\Models\ComplianceDocument;
use App\Domain\Compliance\Services\ComplianceDocumentService;
use App\Domain\Driver\Models\Driver;
use App\Domain\Owner\Models\Owner;
use App\Domain\Vehicle\Models\Vehicle;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Storage::fake('compliance');

    $this->seed([
        RoleSeeder::class,
        PermissionSeeder::class,
        RolePermissionSeeder::class,
    ]);
});

test('compliance officer can list documents', function (): void {
    $role = Role::where('slug', 'compliance_officer')->first();
    $user = User::factory()->create();
    $user->roles()->attach($role->id, ['assigned_at' => now()]);
    ComplianceDocument::factory()->count(3)->create();

    $response = $this->actingAs($user)->getJson('/api/v1/compliance/documents');

    $response->assertOk()->assertJsonStructure(['success', 'data', 'meta']);
    expect($response['data'])->toHaveCount(3);
});

test('upload document succeeds', function (): void {
    $role = Role::where('slug', 'compliance_officer')->first();
    $user = User::factory()->create();
    $user->roles()->attach($role->id, ['assigned_at' => now()]);
    $vehicle = Vehicle::factory()->create();

    $file = UploadedFile::fake()->create('document.pdf', 100);

    $response = $this->actingAs($user)->postJson('/api/v1/compliance/documents', [
        'documentable_type' => Vehicle::class,
        'documentable_id' => $vehicle->id,
        'type' => 'vehicle_registration',
        'file' => $file,
        'issued_at' => now()->toDateString(),
        'expires_at' => now()->addYear()->toDateString(),
    ]);

    $response->assertCreated()->assertJson(['success' => true, 'message' => 'Document uploaded successfully.']);
    expect($response['data']['type'])->toBe('vehicle_registration');
    expect($response['data']['status'])->toBe('pending');
    Storage::disk('compliance')->assertExists($response['data']['file_path']);
});

test('invalid file type is rejected', function (): void {
    $role = Role::where('slug', 'compliance_officer')->first();
    $user = User::factory()->create();
    $user->roles()->attach($role->id, ['assigned_at' => now()]);
    $vehicle = Vehicle::factory()->create();

    $file = UploadedFile::fake()->create('document.exe', 100);

    $response = $this->actingAs($user)->postJson('/api/v1/compliance/documents', [
        'documentable_type' => Vehicle::class,
        'documentable_id' => $vehicle->id,
        'type' => 'vehicle_registration',
        'file' => $file,
    ]);

    $response->assertStatus(422);
});

test('file larger than 10MB is rejected', function (): void {
    $role = Role::where('slug', 'compliance_officer')->first();
    $user = User::factory()->create();
    $user->roles()->attach($role->id, ['assigned_at' => now()]);
    $vehicle = Vehicle::factory()->create();

    $file = UploadedFile::fake()->create('document.pdf', 12000);

    $response = $this->actingAs($user)->postJson('/api/v1/compliance/documents', [
        'documentable_type' => Vehicle::class,
        'documentable_id' => $vehicle->id,
        'type' => 'vehicle_registration',
        'file' => $file,
    ]);

    $response->assertStatus(422);
});

test('compliance officer can show document', function (): void {
    $role = Role::where('slug', 'compliance_officer')->first();
    $user = User::factory()->create();
    $user->roles()->attach($role->id, ['assigned_at' => now()]);
    $document = ComplianceDocument::factory()->create();

    $response = $this->actingAs($user)->getJson("/api/v1/compliance/documents/{$document->id}");

    $response->assertOk()->assertJsonStructure(['success', 'data']);
});

test('compliance officer can approve a document', function (): void {
    $role = Role::where('slug', 'compliance_officer')->first();
    $user = User::factory()->create();
    $user->roles()->attach($role->id, ['assigned_at' => now()]);
    $document = ComplianceDocument::factory()->create();

    $response = $this->actingAs($user)->postJson("/api/v1/compliance/documents/{$document->id}/approve");

    $response->assertOk()->assertJson(['success' => true, 'message' => 'Document approved successfully.']);
    expect($response['data']['status'])->toBe('approved');
    expect($response['data']['reviewed_by']['id'])->toBe($user->id);
    expect($response['data']['reviewed_by'])->toBeArray();
});

test('non-authorized user cannot approve a document', function (): void {
    $driverRole = Role::where('slug', 'driver')->first();
    $user = User::factory()->create();
    $user->roles()->attach($driverRole->id, ['assigned_at' => now()]);
    $document = ComplianceDocument::factory()->create();

    $response = $this->actingAs($user)->postJson("/api/v1/compliance/documents/{$document->id}/approve");

    $response->assertStatus(403);
});

test('compliance officer can reject a document', function (): void {
    $role = Role::where('slug', 'compliance_officer')->first();
    $user = User::factory()->create();
    $user->roles()->attach($role->id, ['assigned_at' => now()]);
    $document = ComplianceDocument::factory()->create();

    $response = $this->actingAs($user)->postJson("/api/v1/compliance/documents/{$document->id}/reject", [
        'reason' => 'Document is illegible and needs to be re-scanned.',
    ]);

    $response->assertOk()->assertJson(['success' => true, 'message' => 'Document rejected.']);
    expect($response['data']['status'])->toBe('rejected');
    expect($response['data']['rejection_reason'])->toBe('Document is illegible and needs to be re-scanned.');
});

test('rejection requires a reason', function (): void {
    $role = Role::where('slug', 'compliance_officer')->first();
    $user = User::factory()->create();
    $user->roles()->attach($role->id, ['assigned_at' => now()]);
    $document = ComplianceDocument::factory()->create();

    $response = $this->actingAs($user)->postJson("/api/v1/compliance/documents/{$document->id}/reject", []);

    $response->assertStatus(422);
});

test('rejection reason must be at least 10 characters', function (): void {
    $role = Role::where('slug', 'compliance_officer')->first();
    $user = User::factory()->create();
    $user->roles()->attach($role->id, ['assigned_at' => now()]);
    $document = ComplianceDocument::factory()->create();

    $response = $this->actingAs($user)->postJson("/api/v1/compliance/documents/{$document->id}/reject", [
        'reason' => 'Short',
    ]);

    $response->assertStatus(422);
});

test('already approved document cannot be approved again', function (): void {
    $role = Role::where('slug', 'compliance_officer')->first();
    $user = User::factory()->create();
    $user->roles()->attach($role->id, ['assigned_at' => now()]);
    $document = ComplianceDocument::factory()->approved()->create();

    $response = $this->actingAs($user)->postJson("/api/v1/compliance/documents/{$document->id}/approve");

    $response->assertStatus(422);
});

test('expired document can be detected via service', function (): void {
    $role = Role::where('slug', 'compliance_officer')->first();
    $user = User::factory()->create();
    $user->roles()->attach($role->id, ['assigned_at' => now()]);
    $document = ComplianceDocument::factory()->approved()->create([
        'expires_at' => now()->subDay(),
    ]);

    $service = app(ComplianceDocumentService::class);
    $service->markExpired($document);

    $document->refresh();
    expect($document->status->value)->toBe('expired');
});

test('audit log created on approve', function (): void {
    $role = Role::where('slug', 'compliance_officer')->first();
    $user = User::factory()->create();
    $user->roles()->attach($role->id, ['assigned_at' => now()]);
    $document = ComplianceDocument::factory()->create();

    $this->actingAs($user)->postJson("/api/v1/compliance/documents/{$document->id}/approve");

    $this->assertDatabaseHas('audit_logs', [
        'action' => 'compliance_document.approved',
        'subject_type' => ComplianceDocument::class,
        'subject_id' => $document->id,
        'actor_id' => $user->id,
    ]);
});

test('audit log created on reject', function (): void {
    $role = Role::where('slug', 'compliance_officer')->first();
    $user = User::factory()->create();
    $user->roles()->attach($role->id, ['assigned_at' => now()]);
    $document = ComplianceDocument::factory()->create();

    $this->actingAs($user)->postJson("/api/v1/compliance/documents/{$document->id}/reject", [
        'reason' => 'Document needs to be re-scanned for clarity.',
    ]);

    $this->assertDatabaseHas('audit_logs', [
        'action' => 'compliance_document.rejected',
        'subject_type' => ComplianceDocument::class,
        'subject_id' => $document->id,
        'actor_id' => $user->id,
    ]);
});

test('unauthorized user cannot list documents', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->getJson('/api/v1/compliance/documents');

    $response->assertStatus(403);
});

test('unauthenticated request to documents is rejected', function (): void {
    $response = $this->getJson('/api/v1/compliance/documents');

    $response->assertStatus(401);
});

test('driver can upload a compliance document for their own driver record', function (): void {
    $role = Role::where('slug', 'driver')->first();
    $user = User::factory()->create();
    $user->roles()->attach($role->id, ['assigned_at' => now()]);
    $driver = Driver::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->postJson('/api/v1/compliance/documents', [
        'documentable_type' => Driver::class,
        'documentable_id' => $driver->id,
        'type' => 'driver_license',
        'file' => UploadedFile::fake()->create('license.pdf', 100),
        'issued_at' => now()->toDateString(),
        'expires_at' => now()->addYear()->toDateString(),
    ]);

    $response->assertCreated();
    expect($response['data']['documentable_id'])->toBe($driver->id);
});

test('driver cannot upload documents for another driver', function (): void {
    $role = Role::where('slug', 'driver')->first();
    $user = User::factory()->create();
    $user->roles()->attach($role->id, ['assigned_at' => now()]);
    Driver::factory()->create(['user_id' => $user->id]);
    $otherDriver = Driver::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/v1/compliance/documents', [
        'documentable_type' => Driver::class,
        'documentable_id' => $otherDriver->id,
        'type' => 'driver_license',
        'file' => UploadedFile::fake()->create('license.pdf', 100),
    ]);

    $response->assertStatus(422);
});

test('driver cannot upload compliance documents for vehicles', function (): void {
    $role = Role::where('slug', 'driver')->first();
    $user = User::factory()->create();
    $user->roles()->attach($role->id, ['assigned_at' => now()]);
    Driver::factory()->create(['user_id' => $user->id]);
    $vehicle = Vehicle::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/v1/compliance/documents', [
        'documentable_type' => Vehicle::class,
        'documentable_id' => $vehicle->id,
        'type' => 'vehicle_registration',
        'file' => UploadedFile::fake()->create('registration.pdf', 100),
    ]);

    $response->assertStatus(422);
});

test('contractor can upload a compliance document for their own company', function (): void {
    $role = Role::where('slug', 'contractor')->first();
    $user = User::factory()->create();
    $user->roles()->attach($role->id, ['assigned_at' => now()]);
    $owner = Owner::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->postJson('/api/v1/compliance/documents', [
        'documentable_type' => Owner::class,
        'documentable_id' => $owner->id,
        'type' => 'contract_document',
        'file' => UploadedFile::fake()->create('contract.pdf', 100),
        'issued_at' => now()->toDateString(),
        'expires_at' => now()->addYear()->toDateString(),
    ]);

    $response->assertCreated();
});

test('contractor can upload a compliance document for a vehicle they own', function (): void {
    $role = Role::where('slug', 'contractor')->first();
    $user = User::factory()->create();
    $user->roles()->attach($role->id, ['assigned_at' => now()]);
    $owner = Owner::factory()->create(['user_id' => $user->id]);
    $vehicle = Vehicle::factory()->create(['owner_id' => $owner->id]);

    $response = $this->actingAs($user)->postJson('/api/v1/compliance/documents', [
        'documentable_type' => Vehicle::class,
        'documentable_id' => $vehicle->id,
        'type' => 'vehicle_registration',
        'file' => UploadedFile::fake()->create('registration.pdf', 100),
        'issued_at' => now()->toDateString(),
        'expires_at' => now()->addYear()->toDateString(),
    ]);

    $response->assertCreated();
});

test('contractor cannot upload documents for a vehicle they do not own', function (): void {
    $role = Role::where('slug', 'contractor')->first();
    $user = User::factory()->create();
    $user->roles()->attach($role->id, ['assigned_at' => now()]);
    Owner::factory()->create(['user_id' => $user->id]);
    $otherOwner = Owner::factory()->create();
    $vehicle = Vehicle::factory()->create(['owner_id' => $otherOwner->id]);

    $response = $this->actingAs($user)->postJson('/api/v1/compliance/documents', [
        'documentable_type' => Vehicle::class,
        'documentable_id' => $vehicle->id,
        'type' => 'vehicle_registration',
        'file' => UploadedFile::fake()->create('registration.pdf', 100),
    ]);

    $response->assertStatus(422);
});

test('contractor cannot upload documents for another company', function (): void {
    $role = Role::where('slug', 'contractor')->first();
    $user = User::factory()->create();
    $user->roles()->attach($role->id, ['assigned_at' => now()]);
    Owner::factory()->create(['user_id' => $user->id]);
    $otherOwner = Owner::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/v1/compliance/documents', [
        'documentable_type' => Owner::class,
        'documentable_id' => $otherOwner->id,
        'type' => 'contract_document',
        'file' => UploadedFile::fake()->create('contract.pdf', 100),
    ]);

    $response->assertStatus(422);
});

test('document type must match the target entity', function (): void {
    $role = Role::where('slug', 'compliance_officer')->first();
    $user = User::factory()->create();
    $user->roles()->attach($role->id, ['assigned_at' => now()]);
    $vehicle = Vehicle::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/v1/compliance/documents', [
        'documentable_type' => Vehicle::class,
        'documentable_id' => $vehicle->id,
        'type' => 'driver_license',
        'file' => UploadedFile::fake()->create('license.pdf', 100),
    ]);

    $response->assertStatus(422);
});

test('driver can list only their own documents', function (): void {
    $role = Role::where('slug', 'driver')->first();
    $user = User::factory()->create();
    $user->roles()->attach($role->id, ['assigned_at' => now()]);
    $ownDriver = Driver::factory()->create(['user_id' => $user->id]);
    $otherDriver = Driver::factory()->create();
    ComplianceDocument::factory()->create(['documentable_type' => Driver::class, 'documentable_id' => $ownDriver->id]);
    ComplianceDocument::factory()->create(['documentable_type' => Driver::class, 'documentable_id' => $otherDriver->id]);

    $response = $this->actingAs($user)->getJson('/api/v1/compliance/documents');

    $response->assertOk();
    expect($response['data'])->toHaveCount(1);
    expect($response['data'][0]['documentable_id'])->toBe($ownDriver->id);
});

test('contractor can list only documents for their own company and vehicles', function (): void {
    $role = Role::where('slug', 'contractor')->first();
    $user = User::factory()->create();
    $user->roles()->attach($role->id, ['assigned_at' => now()]);
    $owner = Owner::factory()->create(['user_id' => $user->id]);
    $otherOwner = Owner::factory()->create();
    $ownVehicle = Vehicle::factory()->create(['owner_id' => $owner->id]);
    $otherVehicle = Vehicle::factory()->create(['owner_id' => $otherOwner->id]);

    ComplianceDocument::factory()->create(['documentable_type' => Owner::class, 'documentable_id' => $owner->id]);
    ComplianceDocument::factory()->create(['documentable_type' => Vehicle::class, 'documentable_id' => $ownVehicle->id]);
    ComplianceDocument::factory()->create(['documentable_type' => Vehicle::class, 'documentable_id' => $otherVehicle->id]);
    ComplianceDocument::factory()->create(['documentable_type' => Owner::class, 'documentable_id' => $otherOwner->id]);

    $response = $this->actingAs($user)->getJson('/api/v1/compliance/documents');

    $response->assertOk();
    expect($response['data'])->toHaveCount(2);
});
