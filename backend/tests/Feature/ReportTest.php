<?php

use App\Domain\Compliance\Models\ComplianceDocument;
use App\Domain\Contract\Models\Contract;
use App\Domain\Driver\Models\Driver;
use App\Domain\Fuel\Models\FuelTransaction;
use App\Domain\Garage\Models\MaintenanceRecord;
use App\Domain\Route\Models\Route;
use App\Domain\Trip\Models\Trip;
use App\Domain\Trip\Models\TripAssignment;
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

function reportsTransportManager(): User
{
    $role = Role::where('slug', 'transport_manager')->first();
    $user = User::factory()->create();
    $user->roles()->attach($role->id, ['assigned_at' => now()]);

    return $user;
}

function reportsFinanceOfficer(): User
{
    $role = Role::where('slug', 'finance_officer')->first();
    $user = User::factory()->create();
    $user->roles()->attach($role->id, ['assigned_at' => now()]);

    return $user;
}

function reportsViewer(): User
{
    $role = Role::where('slug', 'auditor')->first();
    $user = User::factory()->create();
    $user->roles()->attach($role->id, ['assigned_at' => now()]);

    return $user;
}

test('transport manager can list available reports', function (): void {
    $user = reportsTransportManager();

    $response = $this->actingAs($user)->getJson('/api/v1/reports');

    $response->assertOk()->assertJsonStructure(['success', 'data']);
    expect($response['data'])->toBeArray();
    expect(count($response['data']))->toBeGreaterThan(0);
    expect($response['data'][0])->toHaveKeys(['type', 'label', 'description']);
});

test('auditor can list available reports', function (): void {
    $user = reportsViewer();

    $response = $this->actingAs($user)->getJson('/api/v1/reports');

    $response->assertOk();
});

test('unauthorized user cannot list reports', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->getJson('/api/v1/reports');

    $response->assertStatus(403);
});

test('unauthenticated request to reports is rejected', function (): void {
    $response = $this->getJson('/api/v1/reports');

    $response->assertStatus(401);
});

test('transport manager can generate fleet summary report', function (): void {
    $user = reportsTransportManager();
    Vehicle::factory()->count(5)->create();

    $response = $this->actingAs($user)->getJson('/api/v1/reports/fleet_summary');

    $response->assertOk()->assertJsonStructure(['success', 'data']);
    expect($response['data']['type'])->toBe('fleet_summary');
    expect($response['data']['aggregates']['total_vehicles'])->toBe(5);
});

test('finance officer can generate fuel consumption report', function (): void {
    $user = reportsFinanceOfficer();
    $vehicle = Vehicle::factory()->create(['category' => 'defence_plated', 'status' => 'active']);
    FuelTransaction::factory()->count(3)->create([
        'vehicle_id' => $vehicle->id,
        'transaction_type' => 'issue',
        'quantity' => -50,
    ]);

    $response = $this->actingAs($user)->getJson('/api/v1/reports/fuel_consumption');

    $response->assertOk();
    expect($response['data']['type'])->toBe('fuel_consumption');
    expect($response['data']['aggregates']['total_transactions'])->toBe(3);
});

test('transport manager can generate trip analysis report', function (): void {
    $user = reportsTransportManager();
    Trip::factory()->count(2)->create();

    $response = $this->actingAs($user)->getJson('/api/v1/reports/trip_analysis');

    $response->assertOk();
    expect($response['data']['type'])->toBe('trip_analysis');
    expect($response['data']['aggregates']['total_trips'])->toBe(2);
});

test('transport manager can generate maintenance summary report', function (): void {
    $user = reportsTransportManager();
    MaintenanceRecord::factory()->count(2)->create();

    $response = $this->actingAs($user)->getJson('/api/v1/reports/maintenance_summary');

    $response->assertOk();
    expect($response['data']['type'])->toBe('maintenance_summary');
    expect($response['data']['aggregates']['total_requests'])->toBe(2);
});

test('transport manager can generate compliance status report', function (): void {
    $user = reportsTransportManager();
    ComplianceDocument::factory()->count(3)->create();

    $response = $this->actingAs($user)->getJson('/api/v1/reports/compliance_status');

    $response->assertOk();
    expect($response['data']['type'])->toBe('compliance_status');
    expect($response['data']['aggregates']['total_documents'])->toBe(3);
});

test('finance officer can generate contract performance report', function (): void {
    $user = reportsFinanceOfficer();
    Contract::factory()->count(2)->create();

    $response = $this->actingAs($user)->getJson('/api/v1/reports/contract_performance');

    $response->assertOk();
    expect($response['data']['type'])->toBe('contract_performance');
    expect($response['data']['aggregates']['total_contracts'])->toBe(2);
});

test('invalid report type returns error', function (): void {
    $user = reportsTransportManager();

    $response = $this->actingAs($user)->getJson('/api/v1/reports/invalid_type');

    $response->assertStatus(422);
});

test('unauthorized user cannot generate report', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->getJson('/api/v1/reports/fleet_summary');

    $response->assertStatus(403);
});

test('report respects date filters', function (): void {
    $user = reportsTransportManager();
    Vehicle::factory()->create(['created_at' => now()->subMonths(3)]);
    Vehicle::factory()->create(['created_at' => now()->subDay()]);

    $response = $this->actingAs($user)->getJson('/api/v1/reports/fleet_summary?date_from='.now()->subMonth()->format('Y-m-d'));

    $response->assertOk();
    expect($response['data']['aggregates']['total_vehicles'])->toBe(1);
});

test('trip analysis aggregates assignments across multiple filtered trips', function (): void {
    $user = reportsTransportManager();
    $route = Route::factory()->create(['status' => 'active']);
    $trips = Trip::factory()->count(3)->create(['route_id' => $route->id]);

    foreach ($trips as $index => $trip) {
        TripAssignment::factory()->count($index + 1)->create(['trip_id' => $trip->id]);
    }

    $response = $this->actingAs($user)->getJson('/api/v1/reports/trip_analysis?date_from='.now()->subDay()->format('Y-m-d'));

    $response->assertOk();
    expect($response['data']['aggregates']['total_trips'])->toBe(3);
    expect($response['data']['aggregates']['total_assignments'])->toBe(6);
});

test('trip analysis handles large assignment volumes', function (): void {
    $user = reportsTransportManager();
    $route = Route::factory()->create(['status' => 'active']);
    $vehicle = Vehicle::factory()->create(['category' => 'defence_plated']);
    $driver = Driver::factory()->create();

    $trips = Trip::factory()->count(250)->create([
        'route_id' => $route->id,
        'vehicle_id' => $vehicle->id,
        'driver_id' => $driver->id,
    ]);

    foreach ($trips as $trip) {
        TripAssignment::factory()->count(2)->create(['trip_id' => $trip->id]);
    }

    $response = $this->actingAs($user)->getJson('/api/v1/reports/trip_analysis');

    $response->assertOk();
    expect($response['data']['aggregates']['total_trips'])->toBe(250);
    expect($response['data']['aggregates']['total_assignments'])->toBe(500);
});

test('passenger utilization excludes assignments of deleted trips', function (): void {
    $user = reportsTransportManager();
    $keptTrip = Trip::factory()->create();
    $deletedTrip = Trip::factory()->create();

    TripAssignment::factory()->create(['trip_id' => $keptTrip->id]);
    TripAssignment::factory()->create(['trip_id' => $deletedTrip->id]);

    $deletedTrip->delete();

    $response = $this->actingAs($user)->getJson('/api/v1/reports/passenger_utilization');

    $response->assertOk();
    expect($response['data']['aggregates']['total_assignments'])->toBe(2);
    expect($response['data']['aggregates']['passenger_trips'])->toBe(1);
});
