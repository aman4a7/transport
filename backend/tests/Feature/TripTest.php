<?php

use App\Domain\Driver\Models\Driver;
use App\Domain\Passenger\Models\Passenger;
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

function actingAsTransportManager(): User
{
    $role = Role::where('slug', 'transport_manager')->first();
    $user = User::factory()->create();
    $user->roles()->attach($role->id, ['assigned_at' => now()]);

    return $user;
}

function makeTripData(): array
{
    $route = Route::factory()->create(['status' => 'active']);
    $vehicle = Vehicle::factory()->create(['category' => 'defence_plated', 'status' => 'active', 'registration_expiry' => now()->addYear(), 'insurance_expiry' => now()->addYear()]);
    $driver = Driver::factory()->create(['status' => 'active', 'license_expiry' => now()->addYear()]);

    return [
        'route_id' => $route->id,
        'vehicle_id' => $vehicle->id,
        'driver_id' => $driver->id,
        'scheduled_date' => now()->addDay()->format('Y-m-d'),
        'departure_time' => '08:00',
        'estimated_arrival_time' => '12:00',
    ];
}

test('transport manager can list trips', function (): void {
    $user = actingAsTransportManager();
    $data = makeTripData();
    Trip::factory()->create(['route_id' => $data['route_id'], 'vehicle_id' => $data['vehicle_id'], 'driver_id' => $data['driver_id']]);

    $response = $this->actingAs($user)->getJson('/api/v1/trips');

    $response->assertOk()->assertJsonStructure(['success', 'data', 'meta']);
    expect($response['data'])->toHaveCount(1);
});

test('transport manager can create a trip', function (): void {
    $user = actingAsTransportManager();
    $data = makeTripData();

    $response = $this->actingAs($user)->postJson('/api/v1/trips', $data);

    $response->assertCreated()->assertJson(['success' => true, 'message' => 'Trip created successfully.']);
    expect($response['data']['status'])->toBe('scheduled');
});

test('creating trip with inactive vehicle fails', function (): void {
    $user = actingAsTransportManager();
    $data = makeTripData();
    $vehicle = Vehicle::find($data['vehicle_id']);
    $vehicle->update(['status' => 'in_maintenance']);

    $response = $this->actingAs($user)->postJson('/api/v1/trips', $data);

    $response->assertStatus(422);
});

test('creating trip with expired vehicle registration fails', function (): void {
    $user = actingAsTransportManager();
    $data = makeTripData();
    $vehicle = Vehicle::find($data['vehicle_id']);
    $vehicle->update(['registration_expiry' => now()->subDay()]);

    $response = $this->actingAs($user)->postJson('/api/v1/trips', $data);

    $response->assertStatus(422);
});

test('creating trip with expired vehicle insurance fails', function (): void {
    $user = actingAsTransportManager();
    $data = makeTripData();
    $vehicle = Vehicle::find($data['vehicle_id']);
    $vehicle->update(['insurance_expiry' => now()->subDay()]);

    $response = $this->actingAs($user)->postJson('/api/v1/trips', $data);

    $response->assertStatus(422);
});

test('creating trip with inactive driver fails', function (): void {
    $user = actingAsTransportManager();
    $data = makeTripData();
    $driver = Driver::find($data['driver_id']);
    $driver->update(['status' => 'suspended']);

    $response = $this->actingAs($user)->postJson('/api/v1/trips', $data);

    $response->assertStatus(422);
});

test('creating trip with expired driver license fails', function (): void {
    $user = actingAsTransportManager();
    $data = makeTripData();
    $driver = Driver::find($data['driver_id']);
    $driver->update(['license_expiry' => now()->subDay()]);

    $response = $this->actingAs($user)->postJson('/api/v1/trips', $data);

    $response->assertStatus(422);
});

test('creating trip with inactive route fails', function (): void {
    $user = actingAsTransportManager();
    $data = makeTripData();
    $route = Route::find($data['route_id']);
    $route->update(['status' => 'inactive']);

    $response = $this->actingAs($user)->postJson('/api/v1/trips', $data);

    $response->assertStatus(422);
});

test('creating trip with passenger capacity exceeded fails', function (): void {
    $user = actingAsTransportManager();
    $data = makeTripData();
    $vehicle = Vehicle::find($data['vehicle_id']);
    $vehicle->update(['seating_capacity' => 1]);
    $passenger1 = Passenger::factory()->create();
    $passenger2 = Passenger::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/v1/trips', array_merge($data, [
        'passenger_ids' => [$passenger1->id, $passenger2->id],
    ]));

    $response->assertStatus(422);
});

test('creating trip with contracted vehicle missing valid compliance fails', function (): void {
    $user = actingAsTransportManager();
    $route = Route::factory()->create(['status' => 'active']);
    $vehicle = Vehicle::factory()->create(['category' => 'contracted_private', 'status' => 'active', 'seating_capacity' => 10]);
    $driver = Driver::factory()->create(['status' => 'active', 'license_expiry' => now()->addYear()]);

    $response = $this->actingAs($user)->postJson('/api/v1/trips', [
        'route_id' => $route->id,
        'vehicle_id' => $vehicle->id,
        'driver_id' => $driver->id,
        'scheduled_date' => now()->addDay()->format('Y-m-d'),
        'departure_time' => '08:00',
    ]);

    $response->assertStatus(422);
});

test('transport manager can show a trip', function (): void {
    $user = actingAsTransportManager();
    $data = makeTripData();
    $trip = Trip::factory()->create(['route_id' => $data['route_id'], 'vehicle_id' => $data['vehicle_id'], 'driver_id' => $data['driver_id']]);

    $response = $this->actingAs($user)->getJson("/api/v1/trips/{$trip->id}");

    $response->assertOk()->assertJsonStructure(['success', 'data']);
});

test('transport manager can start a trip', function (): void {
    $user = actingAsTransportManager();
    $data = makeTripData();
    $trip = Trip::factory()->create(['route_id' => $data['route_id'], 'vehicle_id' => $data['vehicle_id'], 'driver_id' => $data['driver_id'], 'status' => 'scheduled']);

    $response = $this->actingAs($user)->postJson("/api/v1/trips/{$trip->id}/start");

    $response->assertOk()->assertJson(['success' => true, 'message' => 'Trip started.']);
    expect($response['data']['status'])->toBe('in_progress');
});

test('transport manager can complete a trip', function (): void {
    $user = actingAsTransportManager();
    $data = makeTripData();
    $trip = Trip::factory()->inProgress()->create(['route_id' => $data['route_id'], 'vehicle_id' => $data['vehicle_id'], 'driver_id' => $data['driver_id']]);

    $response = $this->actingAs($user)->postJson("/api/v1/trips/{$trip->id}/complete");

    $response->assertOk()->assertJson(['success' => true, 'message' => 'Trip completed.']);
    expect($response['data']['status'])->toBe('completed');
});

test('transport manager can cancel a trip', function (): void {
    $user = actingAsTransportManager();
    $data = makeTripData();
    $trip = Trip::factory()->create(['route_id' => $data['route_id'], 'vehicle_id' => $data['vehicle_id'], 'driver_id' => $data['driver_id'], 'status' => 'scheduled']);

    $response = $this->actingAs($user)->postJson("/api/v1/trips/{$trip->id}/cancel");

    $response->assertOk()->assertJson(['success' => true, 'message' => 'Trip cancelled.']);
    expect($response['data']['status'])->toBe('cancelled');
});

test('starting already started trip fails', function (): void {
    $user = actingAsTransportManager();
    $data = makeTripData();
    $trip = Trip::factory()->inProgress()->create(['route_id' => $data['route_id'], 'vehicle_id' => $data['vehicle_id'], 'driver_id' => $data['driver_id']]);

    $response = $this->actingAs($user)->postJson("/api/v1/trips/{$trip->id}/start");

    $response->assertStatus(422);
});

test('completing scheduled trip fails', function (): void {
    $user = actingAsTransportManager();
    $data = makeTripData();
    $trip = Trip::factory()->create(['route_id' => $data['route_id'], 'vehicle_id' => $data['vehicle_id'], 'driver_id' => $data['driver_id'], 'status' => 'scheduled']);

    $response = $this->actingAs($user)->postJson("/api/v1/trips/{$trip->id}/complete");

    $response->assertStatus(422);
});

test('transport manager can update a trip', function (): void {
    $user = actingAsTransportManager();
    $data = makeTripData();
    $trip = Trip::factory()->create(['route_id' => $data['route_id'], 'vehicle_id' => $data['vehicle_id'], 'driver_id' => $data['driver_id']]);

    $response = $this->actingAs($user)->putJson("/api/v1/trips/{$trip->id}", ['notes' => 'Updated notes']);

    $response->assertOk()->assertJson(['success' => true]);
    expect($response['data']['notes'])->toBe('Updated notes');
});

test('transport manager can delete a scheduled trip', function (): void {
    $user = actingAsTransportManager();
    $data = makeTripData();
    $trip = Trip::factory()->create(['route_id' => $data['route_id'], 'vehicle_id' => $data['vehicle_id'], 'driver_id' => $data['driver_id']]);

    $response = $this->actingAs($user)->deleteJson("/api/v1/trips/{$trip->id}");

    $response->assertOk()->assertJson(['success' => true, 'message' => 'Trip deleted successfully.']);
    expect(Trip::find($trip->id))->toBeNull();
});

test('deleting in-progress trip fails', function (): void {
    $user = actingAsTransportManager();
    $data = makeTripData();
    $trip = Trip::factory()->inProgress()->create(['route_id' => $data['route_id'], 'vehicle_id' => $data['vehicle_id'], 'driver_id' => $data['driver_id']]);

    $response = $this->actingAs($user)->deleteJson("/api/v1/trips/{$trip->id}");

    $response->assertStatus(422);
});

test('unauthorized user cannot list trips', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->getJson('/api/v1/trips');

    $response->assertStatus(403);
});

test('unauthenticated request to trips is rejected', function (): void {
    $response = $this->getJson('/api/v1/trips');

    $response->assertStatus(401);
});

test('creating trip assigns passengers', function (): void {
    $user = actingAsTransportManager();
    $data = makeTripData();
    $vehicle = Vehicle::find($data['vehicle_id']);
    $vehicle->update(['seating_capacity' => 10]);
    $passenger1 = Passenger::factory()->create();
    $passenger2 = Passenger::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/v1/trips', array_merge($data, [
        'passenger_ids' => [$passenger1->id, $passenger2->id],
    ]));

    $response->assertCreated();
    expect($response['data']['assignments'])->toHaveCount(2);
});

test('creating trip without required fields fails', function (): void {
    $user = actingAsTransportManager();

    $response = $this->actingAs($user)->postJson('/api/v1/trips', []);

    $response->assertStatus(422);
});

test('transport manager can cancel an in-progress trip', function (): void {
    $user = actingAsTransportManager();
    $data = makeTripData();
    $trip = Trip::factory()->inProgress()->create(['route_id' => $data['route_id'], 'vehicle_id' => $data['vehicle_id'], 'driver_id' => $data['driver_id']]);

    $response = $this->actingAs($user)->postJson("/api/v1/trips/{$trip->id}/cancel");

    $response->assertOk()->assertJson(['success' => true, 'message' => 'Trip cancelled.']);
    expect($response['data']['status'])->toBe('cancelled');
});

test('creating trip with exact passenger capacity succeeds', function (): void {
    $user = actingAsTransportManager();
    $data = makeTripData();
    $vehicle = Vehicle::find($data['vehicle_id']);
    $vehicle->update(['seating_capacity' => 2]);
    $passenger1 = Passenger::factory()->create();
    $passenger2 = Passenger::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/v1/trips', array_merge($data, [
        'passenger_ids' => [$passenger1->id, $passenger2->id],
    ]));

    $response->assertCreated();
    expect($response['data']['assignments'])->toHaveCount(2);
});

test('updating trip reassigns passengers', function (): void {
    $user = actingAsTransportManager();
    $data = makeTripData();
    $vehicle = Vehicle::find($data['vehicle_id']);
    $vehicle->update(['seating_capacity' => 10]);
    $passenger1 = Passenger::factory()->create();
    $passenger2 = Passenger::factory()->create();
    $trip = Trip::factory()->create(['route_id' => $data['route_id'], 'vehicle_id' => $data['vehicle_id'], 'driver_id' => $data['driver_id']]);

    $response = $this->actingAs($user)->putJson("/api/v1/trips/{$trip->id}", [
        'passenger_ids' => [$passenger1->id, $passenger2->id],
    ]);

    $response->assertOk();
    expect($response['data']['assignments'])->toHaveCount(2);
});

test('trip status changes create audit logs', function (): void {
    $user = actingAsTransportManager();
    $data = makeTripData();
    $trip = Trip::factory()->create(['route_id' => $data['route_id'], 'vehicle_id' => $data['vehicle_id'], 'driver_id' => $data['driver_id']]);

    $this->actingAs($user)->postJson("/api/v1/trips/{$trip->id}/start");
    $this->assertDatabaseHas('audit_logs', [
        'action' => 'trip_started',
        'subject_type' => Trip::class,
        'subject_id' => $trip->id,
        'actor_id' => $user->id,
    ]);

    $this->actingAs($user)->postJson("/api/v1/trips/{$trip->id}/complete");
    $this->assertDatabaseHas('audit_logs', [
        'action' => 'trip_completed',
        'subject_type' => Trip::class,
        'subject_id' => $trip->id,
        'actor_id' => $user->id,
    ]);
});

test('passenger can list only trips they are assigned to', function (): void {
    $role = Role::where('slug', 'passenger')->first();
    $user = User::factory()->create();
    $user->roles()->attach($role->id, ['assigned_at' => now()]);
    $passenger = Passenger::factory()->create(['user_id' => $user->id]);

    $assignedTrip = Trip::factory()->create();
    TripAssignment::factory()->create(['trip_id' => $assignedTrip->id, 'passenger_id' => $passenger->id]);
    $otherTrip = Trip::factory()->create();

    $response = $this->actingAs($user)->getJson('/api/v1/trips');

    $response->assertOk();
    expect($response['data'])->toHaveCount(1);
    expect($response['data'][0]['id'])->toBe($assignedTrip->id);
});

test('passenger can view a trip they are assigned to', function (): void {
    $role = Role::where('slug', 'passenger')->first();
    $user = User::factory()->create();
    $user->roles()->attach($role->id, ['assigned_at' => now()]);
    $passenger = Passenger::factory()->create(['user_id' => $user->id]);
    $trip = Trip::factory()->create();
    TripAssignment::factory()->create(['trip_id' => $trip->id, 'passenger_id' => $passenger->id]);

    $response = $this->actingAs($user)->getJson("/api/v1/trips/{$trip->id}");

    $response->assertOk();
});

test('passenger cannot view a trip they are not assigned to', function (): void {
    $role = Role::where('slug', 'passenger')->first();
    $user = User::factory()->create();
    $user->roles()->attach($role->id, ['assigned_at' => now()]);
    Passenger::factory()->create(['user_id' => $user->id]);
    $trip = Trip::factory()->create();

    $response = $this->actingAs($user)->getJson("/api/v1/trips/{$trip->id}");

    $response->assertStatus(403);
});

test('updating trip with vehicle that became ineligible fails', function (): void {
    $user = actingAsTransportManager();
    $data = makeTripData();
    $trip = Trip::factory()->create(['route_id' => $data['route_id'], 'vehicle_id' => $data['vehicle_id'], 'driver_id' => $data['driver_id']]);
    Vehicle::find($data['vehicle_id'])->update(['status' => 'in_maintenance']);

    $response = $this->actingAs($user)->putJson("/api/v1/trips/{$trip->id}", ['notes' => 'Update notes']);

    $response->assertStatus(422);
});

test('updating trip with driver that became ineligible fails', function (): void {
    $user = actingAsTransportManager();
    $data = makeTripData();
    $trip = Trip::factory()->create(['route_id' => $data['route_id'], 'vehicle_id' => $data['vehicle_id'], 'driver_id' => $data['driver_id']]);
    Driver::find($data['driver_id'])->update(['status' => 'suspended']);

    $response = $this->actingAs($user)->putJson("/api/v1/trips/{$trip->id}", ['notes' => 'Update notes']);

    $response->assertStatus(422);
});

test('updating trip with route that became inactive fails', function (): void {
    $user = actingAsTransportManager();
    $data = makeTripData();
    $trip = Trip::factory()->create(['route_id' => $data['route_id'], 'vehicle_id' => $data['vehicle_id'], 'driver_id' => $data['driver_id']]);
    Route::find($data['route_id'])->update(['status' => 'inactive']);

    $response = $this->actingAs($user)->putJson("/api/v1/trips/{$trip->id}", ['notes' => 'Update notes']);

    $response->assertStatus(422);
});

test('starting trip with vehicle that became ineligible fails', function (): void {
    $user = actingAsTransportManager();
    $data = makeTripData();
    $trip = Trip::factory()->create(['route_id' => $data['route_id'], 'vehicle_id' => $data['vehicle_id'], 'driver_id' => $data['driver_id'], 'status' => 'scheduled']);
    Vehicle::find($data['vehicle_id'])->update(['status' => 'in_maintenance']);

    $response = $this->actingAs($user)->postJson("/api/v1/trips/{$trip->id}/start");

    $response->assertStatus(422);
});

test('starting trip with driver that became ineligible fails', function (): void {
    $user = actingAsTransportManager();
    $data = makeTripData();
    $trip = Trip::factory()->create(['route_id' => $data['route_id'], 'vehicle_id' => $data['vehicle_id'], 'driver_id' => $data['driver_id'], 'status' => 'scheduled']);
    Driver::find($data['driver_id'])->update(['license_expiry' => now()->subDay()]);

    $response = $this->actingAs($user)->postJson("/api/v1/trips/{$trip->id}/start");

    $response->assertStatus(422);
});

test('starting trip with route that became inactive fails', function (): void {
    $user = actingAsTransportManager();
    $data = makeTripData();
    $trip = Trip::factory()->create(['route_id' => $data['route_id'], 'vehicle_id' => $data['vehicle_id'], 'driver_id' => $data['driver_id'], 'status' => 'scheduled']);
    Route::find($data['route_id'])->update(['status' => 'inactive']);

    $response = $this->actingAs($user)->postJson("/api/v1/trips/{$trip->id}/start");

    $response->assertStatus(422);
});

test('creating trip exceeding route capacity fails', function (): void {
    $user = actingAsTransportManager();
    $data = makeTripData();
    $vehicle = Vehicle::find($data['vehicle_id']);
    $vehicle->update(['seating_capacity' => 10]);
    Route::find($data['route_id'])->update(['capacity' => 1]);
    $passenger1 = Passenger::factory()->create();
    $passenger2 = Passenger::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/v1/trips', array_merge($data, [
        'passenger_ids' => [$passenger1->id, $passenger2->id],
    ]));

    $response->assertStatus(422);
});

test('creating trip within route capacity succeeds', function (): void {
    $user = actingAsTransportManager();
    $data = makeTripData();
    $vehicle = Vehicle::find($data['vehicle_id']);
    $vehicle->update(['seating_capacity' => 10]);
    Route::find($data['route_id'])->update(['capacity' => 2]);
    $passenger1 = Passenger::factory()->create();
    $passenger2 = Passenger::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/v1/trips', array_merge($data, [
        'passenger_ids' => [$passenger1->id, $passenger2->id],
    ]));

    $response->assertCreated();
    expect($response['data']['assignments'])->toHaveCount(2);
});

test('updating trip exceeding route capacity fails', function (): void {
    $user = actingAsTransportManager();
    $data = makeTripData();
    $vehicle = Vehicle::find($data['vehicle_id']);
    $vehicle->update(['seating_capacity' => 10]);
    Route::find($data['route_id'])->update(['capacity' => 1]);
    $passenger1 = Passenger::factory()->create();
    $passenger2 = Passenger::factory()->create();
    $trip = Trip::factory()->create(['route_id' => $data['route_id'], 'vehicle_id' => $data['vehicle_id'], 'driver_id' => $data['driver_id']]);

    $response = $this->actingAs($user)->putJson("/api/v1/trips/{$trip->id}", [
        'passenger_ids' => [$passenger1->id, $passenger2->id],
    ]);

    $response->assertStatus(422);
});
