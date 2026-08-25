<?php

use App\Domain\Compliance\Enums\ComplianceDocumentType;
use App\Domain\Compliance\Models\ComplianceDocument;
use App\Domain\Compliance\Services\ComplianceDocumentService;
use App\Domain\Contract\Models\Contract;
use App\Domain\Driver\Models\Driver;
use App\Domain\Fuel\Models\FuelStock;
use App\Domain\Fuel\Models\FuelTransaction;
use App\Domain\Garage\Models\MaintenanceRecord;
use App\Domain\Owner\Models\Owner;
use App\Domain\Passenger\Models\Passenger;
use App\Domain\Route\Models\Route;
use App\Domain\Shared\Exceptions\BusinessRuleException;
use App\Domain\Trip\Enums\TripStatus;
use App\Domain\Trip\Models\Trip;
use App\Domain\Trip\Models\TripAssignment;
use App\Domain\Vehicle\Models\Vehicle;
use App\Models\Role;
use App\Models\User;
use Database\Factories\TripAssignmentFactory;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed([
        RoleSeeder::class,
        PermissionSeeder::class,
        RolePermissionSeeder::class,
    ]);
});

// ─── C1: User deletion preserves audit records ───

test('deleting user does not cascade delete audit logs', function (): void {
    $user = User::factory()->create();
    DB::table('audit_logs')->insert([
        'action' => 'test_action',
        'subject_type' => User::class,
        'subject_id' => $user->id,
        'actor_id' => $user->id,
        'description' => 'C1 test log',
        'created_at' => now(),
    ]);
    $logId = DB::table('audit_logs')->max('id');

    $user->delete();

    $this->assertDatabaseHas('audit_logs', ['id' => $logId]);
});

// ─── C2: Audit records for Vehicle/Driver/Owner/Route/Passenger CRUD ───

function hardeningTransportManager(): User
{
    $role = Role::where('slug', 'transport_manager')->first();
    $user = User::factory()->create();
    $user->roles()->attach($role->id, ['assigned_at' => now()]);

    return $user;
}

// Vehicle audit

test('vehicle created creates audit log', function (): void {
    $user = hardeningTransportManager();

    $response = $this->actingAs($user)->postJson('/api/v1/vehicles', [
        'plate_number' => 'AUDIT-001',
        'make' => 'Toyota',
        'model' => 'Hiace',
        'year' => 2024,
        'category' => 'defence_plated',
        'fuel_type' => 'diesel',
        'seating_capacity' => 10,
    ]);

    $response->assertCreated();
    $this->assertDatabaseHas('audit_logs', [
        'action' => 'vehicle_created',
        'subject_type' => Vehicle::class,
        'actor_id' => $user->id,
    ]);
});

test('vehicle updated creates audit log', function (): void {
    $user = hardeningTransportManager();
    $vehicle = Vehicle::factory()->create();

    $response = $this->actingAs($user)->putJson("/api/v1/vehicles/{$vehicle->id}", [
        'make' => 'Isuzu',
    ]);

    $response->assertOk();
    $this->assertDatabaseHas('audit_logs', [
        'action' => 'vehicle_updated',
        'subject_type' => Vehicle::class,
        'subject_id' => $vehicle->id,
        'actor_id' => $user->id,
    ]);
});

test('vehicle deleted creates audit log', function (): void {
    $user = hardeningTransportManager();
    $vehicle = Vehicle::factory()->create();

    $response = $this->actingAs($user)->deleteJson("/api/v1/vehicles/{$vehicle->id}");

    $response->assertOk();
    $this->assertDatabaseHas('audit_logs', [
        'action' => 'vehicle_deleted',
        'subject_type' => Vehicle::class,
        'subject_id' => $vehicle->id,
        'actor_id' => $user->id,
    ]);
});

// Driver audit

test('driver created creates audit log', function (): void {
    $user = hardeningTransportManager();

    $response = $this->actingAs($user)->postJson('/api/v1/drivers', [
        'license_number' => 'AUDIT-LIC-001',
        'license_category' => 'heavy',
        'license_expiry' => '2028-12-31',
    ]);

    $response->assertCreated();
    $this->assertDatabaseHas('audit_logs', [
        'action' => 'driver_created',
        'subject_type' => Driver::class,
        'actor_id' => $user->id,
    ]);
});

test('driver updated creates audit log', function (): void {
    $user = hardeningTransportManager();
    $driver = Driver::factory()->create();

    $response = $this->actingAs($user)->putJson("/api/v1/drivers/{$driver->id}", [
        'license_category' => 'light',
    ]);

    $response->assertOk();
    $this->assertDatabaseHas('audit_logs', [
        'action' => 'driver_updated',
        'subject_type' => Driver::class,
        'subject_id' => $driver->id,
        'actor_id' => $user->id,
    ]);
});

test('driver deleted creates audit log', function (): void {
    $user = hardeningTransportManager();
    $driver = Driver::factory()->create();

    $this->actingAs($user)->deleteJson("/api/v1/drivers/{$driver->id}");

    $this->assertDatabaseHas('audit_logs', [
        'action' => 'driver_deleted',
        'subject_type' => Driver::class,
        'subject_id' => $driver->id,
        'actor_id' => $user->id,
    ]);
});

// Owner audit

test('owner created creates audit log', function (): void {
    $user = hardeningTransportManager();

    $response = $this->actingAs($user)->postJson('/api/v1/owners', [
        'company_name' => 'AUDIT Co.',
        'contact_person' => 'Test Person',
        'phone' => '123456789',
        'email' => 'audit@example.com',
    ]);

    $response->assertCreated();
    $this->assertDatabaseHas('audit_logs', [
        'action' => 'owner_created',
        'subject_type' => Owner::class,
        'actor_id' => $user->id,
    ]);
});

test('owner updated creates audit log', function (): void {
    $user = hardeningTransportManager();
    $owner = Owner::factory()->create();

    $response = $this->actingAs($user)->putJson("/api/v1/owners/{$owner->id}", [
        'company_name' => 'Updated Co.',
    ]);

    $response->assertOk();
    $this->assertDatabaseHas('audit_logs', [
        'action' => 'owner_updated',
        'subject_type' => Owner::class,
        'subject_id' => $owner->id,
        'actor_id' => $user->id,
    ]);
});

test('owner deleted creates audit log', function (): void {
    $user = hardeningTransportManager();
    $owner = Owner::factory()->create();

    $this->actingAs($user)->deleteJson("/api/v1/owners/{$owner->id}");

    $this->assertDatabaseHas('audit_logs', [
        'action' => 'owner_deleted',
        'subject_type' => Owner::class,
        'subject_id' => $owner->id,
        'actor_id' => $user->id,
    ]);
});

// Route audit

test('route created creates audit log', function (): void {
    $user = hardeningTransportManager();

    $response = $this->actingAs($user)->postJson('/api/v1/routes', [
        'name' => 'AUDIT Route',
        'code' => 'AUDIT-R-001',
        'origin' => 'Point A',
        'destination' => 'Point B',
        'distance_km' => 100,
    ]);

    $response->assertCreated();
    $this->assertDatabaseHas('audit_logs', [
        'action' => 'route_created',
        'subject_type' => Route::class,
        'actor_id' => $user->id,
    ]);
});

test('route updated creates audit log', function (): void {
    $user = hardeningTransportManager();
    $route = Route::factory()->create();

    $response = $this->actingAs($user)->putJson("/api/v1/routes/{$route->id}", [
        'name' => 'Updated Route Name',
    ]);

    $response->assertOk();
    $this->assertDatabaseHas('audit_logs', [
        'action' => 'route_updated',
        'subject_type' => Route::class,
        'subject_id' => $route->id,
        'actor_id' => $user->id,
    ]);
});

test('route deleted creates audit log', function (): void {
    $user = hardeningTransportManager();
    $route = Route::factory()->create(['status' => 'inactive']);

    $this->actingAs($user)->deleteJson("/api/v1/routes/{$route->id}");

    $this->assertDatabaseHas('audit_logs', [
        'action' => 'route_deleted',
        'subject_type' => Route::class,
        'subject_id' => $route->id,
        'actor_id' => $user->id,
    ]);
});

// Passenger audit

test('passenger created creates audit log', function (): void {
    $user = hardeningTransportManager();

    $response = $this->actingAs($user)->postJson('/api/v1/passengers', [
        'first_name' => 'Audit',
        'last_name' => 'User',
        'employee_id' => 'AUD-EMP-001',
        'email' => 'audit.passenger@example.com',
        'phone' => '1234567890',
    ]);

    $response->assertCreated();
    $this->assertDatabaseHas('audit_logs', [
        'action' => 'passenger_created',
        'subject_type' => Passenger::class,
        'actor_id' => $user->id,
    ]);
});

test('passenger updated creates audit log', function (): void {
    $user = hardeningTransportManager();
    $passenger = Passenger::factory()->create();

    $response = $this->actingAs($user)->putJson("/api/v1/passengers/{$passenger->id}", [
        'first_name' => 'Updated',
    ]);

    $response->assertOk();
    $this->assertDatabaseHas('audit_logs', [
        'action' => 'passenger_updated',
        'subject_type' => Passenger::class,
        'subject_id' => $passenger->id,
        'actor_id' => $user->id,
    ]);
});

test('passenger deleted creates audit log', function (): void {
    $user = hardeningTransportManager();
    $passenger = Passenger::factory()->create();

    $this->actingAs($user)->deleteJson("/api/v1/passengers/{$passenger->id}");

    $this->assertDatabaseHas('audit_logs', [
        'action' => 'passenger_deleted',
        'subject_type' => Passenger::class,
        'subject_id' => $passenger->id,
        'actor_id' => $user->id,
    ]);
});

// ─── C3: Audit records for Trip create/update/delete ───

test('trip created creates audit log', function (): void {
    $user = hardeningTransportManager();
    $route = Route::factory()->create(['status' => 'active']);
    $vehicle = Vehicle::factory()->create(['category' => 'defence_plated', 'status' => 'active', 'registration_expiry' => now()->addYear(), 'insurance_expiry' => now()->addYear()]);
    $driver = Driver::factory()->create(['status' => 'active', 'license_expiry' => now()->addYear()]);

    $response = $this->actingAs($user)->postJson('/api/v1/trips', [
        'route_id' => $route->id,
        'vehicle_id' => $vehicle->id,
        'driver_id' => $driver->id,
        'scheduled_date' => now()->addDay()->format('Y-m-d'),
        'departure_time' => '08:00',
    ]);

    $response->assertCreated();
    $this->assertDatabaseHas('audit_logs', [
        'action' => 'trip_created',
        'subject_type' => Trip::class,
        'actor_id' => $user->id,
    ]);
});

test('trip updated creates audit log', function (): void {
    $user = hardeningTransportManager();
    $route = Route::factory()->create(['status' => 'active']);
    $vehicle = Vehicle::factory()->create(['category' => 'defence_plated', 'status' => 'active', 'registration_expiry' => now()->addYear(), 'insurance_expiry' => now()->addYear()]);
    $driver = Driver::factory()->create(['status' => 'active', 'license_expiry' => now()->addYear()]);
    $trip = Trip::factory()->create(['route_id' => $route->id, 'vehicle_id' => $vehicle->id, 'driver_id' => $driver->id]);

    $response = $this->actingAs($user)->putJson("/api/v1/trips/{$trip->id}", ['notes' => 'C3 audit notes']);

    $response->assertOk();
    $this->assertDatabaseHas('audit_logs', [
        'action' => 'trip_updated',
        'subject_type' => Trip::class,
        'subject_id' => $trip->id,
        'actor_id' => $user->id,
    ]);
});

test('trip deleted creates audit log', function (): void {
    $user = hardeningTransportManager();
    $route = Route::factory()->create(['status' => 'active']);
    $vehicle = Vehicle::factory()->create(['category' => 'defence_plated', 'status' => 'active', 'registration_expiry' => now()->addYear(), 'insurance_expiry' => now()->addYear()]);
    $driver = Driver::factory()->create(['status' => 'active', 'license_expiry' => now()->addYear()]);
    $trip = Trip::factory()->create(['route_id' => $route->id, 'vehicle_id' => $vehicle->id, 'driver_id' => $driver->id]);

    $this->actingAs($user)->deleteJson("/api/v1/trips/{$trip->id}");

    $this->assertDatabaseHas('audit_logs', [
        'action' => 'trip_deleted',
        'subject_type' => Trip::class,
        'subject_id' => $trip->id,
        'actor_id' => $user->id,
    ]);
});

// ─── C4: Blocked deletion for entities with dependencies ───

test('cannot delete vehicle with active scheduled trips', function (): void {
    $user = hardeningTransportManager();
    $vehicle = Vehicle::factory()->create(['category' => 'defence_plated', 'status' => 'active']);
    Trip::factory()->create([
        'vehicle_id' => $vehicle->id,
        'status' => TripStatus::Scheduled->value,
    ]);

    $response = $this->actingAs($user)->deleteJson("/api/v1/vehicles/{$vehicle->id}");

    $response->assertStatus(422);
    $this->assertDatabaseHas('vehicles', ['id' => $vehicle->id]);
});

test('cannot delete vehicle with in-progress maintenance', function (): void {
    $user = hardeningTransportManager();
    $vehicle = Vehicle::factory()->create(['category' => 'defence_plated', 'status' => 'active']);
    MaintenanceRecord::factory()->inProgress()->create(['vehicle_id' => $vehicle->id]);

    $response = $this->actingAs($user)->deleteJson("/api/v1/vehicles/{$vehicle->id}");

    $response->assertStatus(422);
    $this->assertDatabaseHas('vehicles', ['id' => $vehicle->id]);
});

test('cannot delete driver with active trips', function (): void {
    $user = hardeningTransportManager();
    $driver = Driver::factory()->create(['status' => 'active']);
    $vehicle = Vehicle::factory()->create(['category' => 'defence_plated', 'status' => 'active']);
    $route = Route::factory()->create(['status' => 'active']);
    Trip::factory()->create([
        'vehicle_id' => $vehicle->id,
        'driver_id' => $driver->id,
        'route_id' => $route->id,
        'status' => TripStatus::InProgress->value,
    ]);

    $response = $this->actingAs($user)->deleteJson("/api/v1/drivers/{$driver->id}");

    $response->assertStatus(422);
    $this->assertDatabaseHas('drivers', ['id' => $driver->id]);
});

test('cannot delete owner with associated vehicles', function (): void {
    $user = hardeningTransportManager();
    $owner = Owner::factory()->create();
    Vehicle::factory()->create(['owner_id' => $owner->id, 'category' => 'defence_plated']);

    $response = $this->actingAs($user)->deleteJson("/api/v1/owners/{$owner->id}");

    $response->assertStatus(422);
    $this->assertDatabaseHas('owners', ['id' => $owner->id]);
});

test('cannot delete owner with active contracts', function (): void {
    $user = hardeningTransportManager();
    $owner = Owner::factory()->create();
    $vehicle = Vehicle::factory()->create(['category' => 'contracted_private', 'owner_id' => $owner->id]);
    Contract::factory()->create([
        'vehicle_id' => $vehicle->id,
        'owner_id' => $owner->id,
        'status' => 'active',
    ]);

    $response = $this->actingAs($user)->deleteJson("/api/v1/owners/{$owner->id}");

    $response->assertStatus(422);
    $this->assertDatabaseHas('owners', ['id' => $owner->id]);
});

test('cannot delete route with assigned trips', function (): void {
    $user = hardeningTransportManager();
    $route = Route::factory()->create(['status' => 'active']);
    $vehicle = Vehicle::factory()->create(['category' => 'defence_plated', 'status' => 'active']);
    $driver = Driver::factory()->create(['status' => 'active']);
    Trip::factory()->create([
        'route_id' => $route->id,
        'vehicle_id' => $vehicle->id,
        'driver_id' => $driver->id,
        'status' => TripStatus::Scheduled->value,
    ]);

    $response = $this->actingAs($user)->deleteJson("/api/v1/routes/{$route->id}");

    $response->assertStatus(422);
    $this->assertDatabaseHas('routes', ['id' => $route->id]);
});

test('cannot delete passenger with trip assignments', function (): void {
    $user = hardeningTransportManager();
    $passenger = Passenger::factory()->create();
    $route = Route::factory()->create(['status' => 'active']);
    $vehicle = Vehicle::factory()->create(['category' => 'defence_plated', 'status' => 'active', 'registration_expiry' => now()->addYear(), 'insurance_expiry' => now()->addYear()]);
    $driver = Driver::factory()->create(['status' => 'active', 'license_expiry' => now()->addYear()]);
    $trip = Trip::factory()->create(['route_id' => $route->id, 'vehicle_id' => $vehicle->id, 'driver_id' => $driver->id]);
    TripAssignmentFactory::new()->create([
        'trip_id' => $trip->id,
        'passenger_id' => $passenger->id,
    ]);

    $response = $this->actingAs($user)->deleteJson("/api/v1/passengers/{$passenger->id}");

    $response->assertStatus(422);
    $this->assertDatabaseHas('passengers', ['id' => $passenger->id]);
});

test('cannot delete active contract', function (): void {
    $role = Role::where('slug', 'finance_officer')->first();
    $user = User::factory()->create();
    $user->roles()->attach($role->id, ['assigned_at' => now()]);
    $contract = Contract::factory()->create(['status' => 'active']);

    $response = $this->actingAs($user)->deleteJson("/api/v1/contracts/{$contract->id}");

    $response->assertStatus(422);
    $this->assertDatabaseHas('contracts', ['id' => $contract->id]);
});

test('cannot delete approved compliance document via service', function (): void {
    $document = ComplianceDocument::factory()->create(['status' => 'approved']);

    $service = app(ComplianceDocumentService::class);
    $service->delete($document);
})->throws(BusinessRuleException::class, 'Cannot delete an approved compliance document.');

// ─── H1: Auditable trait on ComplianceDocument, TripAssignment, FuelTransaction ───

test('compliance document created via model fires auditable trait', function (): void {
    $document = ComplianceDocument::factory()->create();

    $this->assertDatabaseHas('audit_logs', [
        'action' => 'created',
        'subject_type' => ComplianceDocument::class,
        'subject_id' => $document->id,
    ]);
});

test('compliance document deleted via model fires auditable trait', function (): void {
    $document = ComplianceDocument::factory()->create();
    $docId = $document->id;

    $document->delete();

    $this->assertDatabaseHas('audit_logs', [
        'action' => 'deleted',
        'subject_type' => ComplianceDocument::class,
        'subject_id' => $docId,
    ]);
});

test('trip assignment created fires auditable trait', function (): void {
    $assignment = TripAssignmentFactory::new()->create();

    $this->assertDatabaseHas('audit_logs', [
        'action' => 'created',
        'subject_type' => TripAssignment::class,
        'subject_id' => $assignment->id,
    ]);
});

test('fuel transaction created fires auditable trait', function (): void {
    $transaction = FuelTransaction::factory()->create();

    $this->assertDatabaseHas('audit_logs', [
        'action' => 'created',
        'subject_type' => FuelTransaction::class,
        'subject_id' => $transaction->id,
    ]);
});

// ─── H3: Driver license compliance for contracted_private trips ───

test('contracted vehicle trip succeeds with valid driver license compliance', function (): void {
    $user = hardeningTransportManager();
    $route = Route::factory()->create(['status' => 'active']);
    $vehicle = Vehicle::factory()->create(['category' => 'contracted_private', 'status' => 'active', 'seating_capacity' => 10]);
    $driver = Driver::factory()->create(['status' => 'active', 'license_expiry' => now()->addYear()]);

    ComplianceDocument::factory()->create([
        'documentable_type' => Vehicle::class,
        'documentable_id' => $vehicle->id,
        'type' => ComplianceDocumentType::VehicleRegistration->value,
        'status' => 'approved',
        'expires_at' => now()->addYear(),
    ]);
    ComplianceDocument::factory()->create([
        'documentable_type' => Vehicle::class,
        'documentable_id' => $vehicle->id,
        'type' => ComplianceDocumentType::Insurance->value,
        'status' => 'approved',
        'expires_at' => now()->addYear(),
    ]);
    ComplianceDocument::factory()->create([
        'documentable_type' => Driver::class,
        'documentable_id' => $driver->id,
        'type' => ComplianceDocumentType::DriverLicense->value,
        'status' => 'approved',
        'expires_at' => now()->addYear(),
    ]);
    Contract::factory()->create([
        'vehicle_id' => $vehicle->id,
        'owner_id' => Owner::factory()->create()->id,
        'status' => 'active',
        'end_date' => now()->addYear(),
    ]);

    $response = $this->actingAs($user)->postJson('/api/v1/trips', [
        'route_id' => $route->id,
        'vehicle_id' => $vehicle->id,
        'driver_id' => $driver->id,
        'scheduled_date' => now()->addDay()->format('Y-m-d'),
        'departure_time' => '08:00',
    ]);

    $response->assertCreated();
    expect($response['data']['status'])->toBe('scheduled');
});

test('contracted vehicle trip fails when driver lacks valid license compliance', function (): void {
    $user = hardeningTransportManager();
    $route = Route::factory()->create(['status' => 'active']);
    $vehicle = Vehicle::factory()->create(['category' => 'contracted_private', 'status' => 'active', 'seating_capacity' => 10]);
    $driver = Driver::factory()->create(['status' => 'active', 'license_expiry' => now()->addYear()]);

    ComplianceDocument::factory()->create([
        'documentable_type' => Vehicle::class,
        'documentable_id' => $vehicle->id,
        'type' => ComplianceDocumentType::Insurance->value,
        'status' => 'approved',
        'expires_at' => now()->addYear(),
    ]);
    Contract::factory()->create([
        'vehicle_id' => $vehicle->id,
        'owner_id' => Owner::factory()->create()->id,
        'status' => 'active',
        'end_date' => now()->addYear(),
    ]);

    $response = $this->actingAs($user)->postJson('/api/v1/trips', [
        'route_id' => $route->id,
        'vehicle_id' => $vehicle->id,
        'driver_id' => $driver->id,
        'scheduled_date' => now()->addDay()->format('Y-m-d'),
        'departure_time' => '08:00',
    ]);

    $response->assertStatus(422);
});

test('contracted vehicle trip fails when vehicle registration compliance document is missing', function (): void {
    $user = hardeningTransportManager();
    $route = Route::factory()->create(['status' => 'active']);
    $vehicle = Vehicle::factory()->create(['category' => 'contracted_private', 'status' => 'active', 'seating_capacity' => 10]);
    $driver = Driver::factory()->create(['status' => 'active', 'license_expiry' => now()->addYear()]);

    ComplianceDocument::factory()->create([
        'documentable_type' => Vehicle::class,
        'documentable_id' => $vehicle->id,
        'type' => ComplianceDocumentType::Insurance->value,
        'status' => 'approved',
        'expires_at' => now()->addYear(),
    ]);
    ComplianceDocument::factory()->create([
        'documentable_type' => Driver::class,
        'documentable_id' => $driver->id,
        'type' => ComplianceDocumentType::DriverLicense->value,
        'status' => 'approved',
        'expires_at' => now()->addYear(),
    ]);
    Contract::factory()->create([
        'vehicle_id' => $vehicle->id,
        'owner_id' => Owner::factory()->create()->id,
        'status' => 'active',
        'end_date' => now()->addYear(),
    ]);

    $response = $this->actingAs($user)->postJson('/api/v1/trips', [
        'route_id' => $route->id,
        'vehicle_id' => $vehicle->id,
        'driver_id' => $driver->id,
        'scheduled_date' => now()->addDay()->format('Y-m-d'),
        'departure_time' => '08:00',
    ]);

    $response->assertStatus(422);
});

test('contracted vehicle trip fails when insurance compliance document is missing', function (): void {
    $user = hardeningTransportManager();
    $route = Route::factory()->create(['status' => 'active']);
    $vehicle = Vehicle::factory()->create(['category' => 'contracted_private', 'status' => 'active', 'seating_capacity' => 10]);
    $driver = Driver::factory()->create(['status' => 'active', 'license_expiry' => now()->addYear()]);

    ComplianceDocument::factory()->create([
        'documentable_type' => Vehicle::class,
        'documentable_id' => $vehicle->id,
        'type' => ComplianceDocumentType::VehicleRegistration->value,
        'status' => 'approved',
        'expires_at' => now()->addYear(),
    ]);
    ComplianceDocument::factory()->create([
        'documentable_type' => Driver::class,
        'documentable_id' => $driver->id,
        'type' => ComplianceDocumentType::DriverLicense->value,
        'status' => 'approved',
        'expires_at' => now()->addYear(),
    ]);
    Contract::factory()->create([
        'vehicle_id' => $vehicle->id,
        'owner_id' => Owner::factory()->create()->id,
        'status' => 'active',
        'end_date' => now()->addYear(),
    ]);

    $response = $this->actingAs($user)->postJson('/api/v1/trips', [
        'route_id' => $route->id,
        'vehicle_id' => $vehicle->id,
        'driver_id' => $driver->id,
        'scheduled_date' => now()->addDay()->format('Y-m-d'),
        'departure_time' => '08:00',
    ]);

    $response->assertStatus(422);
});

test('contracted vehicle trip fails when a required compliance document has no expiry date', function (): void {
    $user = hardeningTransportManager();
    $route = Route::factory()->create(['status' => 'active']);
    $vehicle = Vehicle::factory()->create(['category' => 'contracted_private', 'status' => 'active', 'seating_capacity' => 10]);
    $driver = Driver::factory()->create(['status' => 'active', 'license_expiry' => now()->addYear()]);

    ComplianceDocument::factory()->create([
        'documentable_type' => Vehicle::class,
        'documentable_id' => $vehicle->id,
        'type' => ComplianceDocumentType::VehicleRegistration->value,
        'status' => 'approved',
        'expires_at' => null,
    ]);
    ComplianceDocument::factory()->create([
        'documentable_type' => Vehicle::class,
        'documentable_id' => $vehicle->id,
        'type' => ComplianceDocumentType::Insurance->value,
        'status' => 'approved',
        'expires_at' => now()->addYear(),
    ]);
    ComplianceDocument::factory()->create([
        'documentable_type' => Driver::class,
        'documentable_id' => $driver->id,
        'type' => ComplianceDocumentType::DriverLicense->value,
        'status' => 'approved',
        'expires_at' => now()->addYear(),
    ]);
    Contract::factory()->create([
        'vehicle_id' => $vehicle->id,
        'owner_id' => Owner::factory()->create()->id,
        'status' => 'active',
        'end_date' => now()->addYear(),
    ]);

    $response = $this->actingAs($user)->postJson('/api/v1/trips', [
        'route_id' => $route->id,
        'vehicle_id' => $vehicle->id,
        'driver_id' => $driver->id,
        'scheduled_date' => now()->addDay()->format('Y-m-d'),
        'departure_time' => '08:00',
    ]);

    $response->assertStatus(422);
});

test('contracted vehicle trip fails when a required compliance document has expired', function (): void {
    $user = hardeningTransportManager();
    $route = Route::factory()->create(['status' => 'active']);
    $vehicle = Vehicle::factory()->create(['category' => 'contracted_private', 'status' => 'active', 'seating_capacity' => 10]);
    $driver = Driver::factory()->create(['status' => 'active', 'license_expiry' => now()->addYear()]);

    ComplianceDocument::factory()->create([
        'documentable_type' => Vehicle::class,
        'documentable_id' => $vehicle->id,
        'type' => ComplianceDocumentType::VehicleRegistration->value,
        'status' => 'approved',
        'expires_at' => now()->subDay(),
    ]);
    ComplianceDocument::factory()->create([
        'documentable_type' => Vehicle::class,
        'documentable_id' => $vehicle->id,
        'type' => ComplianceDocumentType::Insurance->value,
        'status' => 'approved',
        'expires_at' => now()->addYear(),
    ]);
    ComplianceDocument::factory()->create([
        'documentable_type' => Driver::class,
        'documentable_id' => $driver->id,
        'type' => ComplianceDocumentType::DriverLicense->value,
        'status' => 'approved',
        'expires_at' => now()->addYear(),
    ]);
    Contract::factory()->create([
        'vehicle_id' => $vehicle->id,
        'owner_id' => Owner::factory()->create()->id,
        'status' => 'active',
        'end_date' => now()->addYear(),
    ]);

    $response = $this->actingAs($user)->postJson('/api/v1/trips', [
        'route_id' => $route->id,
        'vehicle_id' => $vehicle->id,
        'driver_id' => $driver->id,
        'scheduled_date' => now()->addDay()->format('Y-m-d'),
        'departure_time' => '08:00',
    ]);

    $response->assertStatus(422);
});

// ─── H4: Expired registration/insurance blocks fuel/garage ───

test('issuing fuel to vehicle with expired registration fails', function (): void {
    $role = Role::where('slug', 'fuel_attendant')->first();
    $user = User::factory()->create();
    $user->roles()->attach($role->id, ['assigned_at' => now()]);
    $vehicle = Vehicle::factory()->create([
        'category' => 'defence_plated',
        'status' => 'active',
        'registration_expiry' => now()->subDay(),
        'insurance_expiry' => now()->addYear(),
    ]);
    FuelStock::factory()->create(['fuel_type' => 'diesel', 'current_quantity' => 1000]);

    $response = $this->actingAs($user)->postJson('/api/v1/fuel/issue', [
        'vehicle_id' => $vehicle->id,
        'fuel_type' => 'diesel',
        'quantity' => 50,
    ]);

    $response->assertStatus(422);
});

test('issuing fuel to vehicle with expired insurance fails', function (): void {
    $role = Role::where('slug', 'fuel_attendant')->first();
    $user = User::factory()->create();
    $user->roles()->attach($role->id, ['assigned_at' => now()]);
    $vehicle = Vehicle::factory()->create([
        'category' => 'defence_plated',
        'status' => 'active',
        'registration_expiry' => now()->addYear(),
        'insurance_expiry' => now()->subDay(),
    ]);
    FuelStock::factory()->create(['fuel_type' => 'diesel', 'current_quantity' => 1000]);

    $response = $this->actingAs($user)->postJson('/api/v1/fuel/issue', [
        'vehicle_id' => $vehicle->id,
        'fuel_type' => 'diesel',
        'quantity' => 50,
    ]);

    $response->assertStatus(422);
});

test('creating maintenance for vehicle with expired registration fails', function (): void {
    $role = Role::where('slug', 'garage_officer')->first();
    $user = User::factory()->create();
    $user->roles()->attach($role->id, ['assigned_at' => now()]);
    $vehicle = Vehicle::factory()->create([
        'category' => 'defence_plated',
        'status' => 'active',
        'registration_expiry' => now()->subDay(),
        'insurance_expiry' => now()->addYear(),
    ]);

    $response = $this->actingAs($user)->postJson('/api/v1/maintenance', [
        'vehicle_id' => $vehicle->id,
        'maintenance_type' => 'scheduled',
        'description' => 'Oil change',
        'scheduled_date' => now()->addDay()->format('Y-m-d'),
    ]);

    $response->assertStatus(422);
});

test('creating maintenance for vehicle with expired insurance fails', function (): void {
    $role = Role::where('slug', 'garage_officer')->first();
    $user = User::factory()->create();
    $user->roles()->attach($role->id, ['assigned_at' => now()]);
    $vehicle = Vehicle::factory()->create([
        'category' => 'defence_plated',
        'status' => 'active',
        'registration_expiry' => now()->addYear(),
        'insurance_expiry' => now()->subDay(),
    ]);

    $response = $this->actingAs($user)->postJson('/api/v1/maintenance', [
        'vehicle_id' => $vehicle->id,
        'maintenance_type' => 'scheduled',
        'description' => 'Oil change',
        'scheduled_date' => now()->addDay()->format('Y-m-d'),
    ]);

    $response->assertStatus(422);
});
