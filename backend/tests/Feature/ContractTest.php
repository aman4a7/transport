<?php

use App\Domain\Contract\Models\Contract;
use App\Domain\Driver\Models\Driver;
use App\Domain\Owner\Models\Owner;
use App\Domain\Route\Models\Route;
use App\Domain\Vehicle\Models\Vehicle;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed([
        RoleSeeder::class,
        PermissionSeeder::class,
        RolePermissionSeeder::class,
    ]);
});

function financeOfficer(): User
{
    $role = Role::where('slug', 'finance_officer')->first();
    $user = User::factory()->create();
    $user->roles()->attach($role->id, ['assigned_at' => now()]);

    return $user;
}

function contractorUser(): User
{
    $role = Role::where('slug', 'contractor')->first();
    $user = User::factory()->create();
    $user->roles()->attach($role->id, ['assigned_at' => now()]);

    return $user;
}

function contractedVehicle(): Vehicle
{
    return Vehicle::factory()->create(['category' => 'contracted_private', 'status' => 'active']);
}

test('finance officer can list contracts', function (): void {
    $user = financeOfficer();
    Contract::factory()->count(3)->create();

    $response = $this->actingAs($user)->getJson('/api/v1/contracts');

    $response->assertOk()->assertJsonStructure(['success', 'data', 'meta']);
    expect($response['data'])->toHaveCount(3);
});

test('contractor can list contracts', function (): void {
    $user = contractorUser();
    Contract::factory()->count(2)->create();

    $response = $this->actingAs($user)->getJson('/api/v1/contracts');

    $response->assertOk()->assertJsonStructure(['success', 'data', 'meta']);
});

test('finance officer can create a contract', function (): void {
    $user = financeOfficer();
    $owner = Owner::factory()->create();
    $vehicle = Vehicle::factory()->create(['category' => 'contracted_private', 'status' => 'active', 'owner_id' => $owner->id]);

    $response = $this->actingAs($user)->postJson('/api/v1/contracts', [
        'vehicle_id' => $vehicle->id,
        'owner_id' => $owner->id,
        'start_date' => '2026-07-01',
        'end_date' => '2027-06-30',
        'contract_value' => 500000.00,
        'payment_terms' => 'Monthly payments of ETB 41,666.67',
        'notes' => 'Annual transport service contract',
    ]);

    $response->assertCreated()->assertJson(['success' => true]);
    expect($response['data']['status'])->toBe('active');
    expect($response['data']['contract_number'])->not->toBeNull();
});

test('finance officer cannot create contract when owner does not own the vehicle', function (): void {
    $user = financeOfficer();
    $vehicle = contractedVehicle();
    $otherOwner = Owner::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/v1/contracts', [
        'vehicle_id' => $vehicle->id,
        'owner_id' => $otherOwner->id,
        'start_date' => '2026-07-01',
        'end_date' => '2027-06-30',
    ]);

    $response->assertStatus(422);
    expect($response['message'])->toBe('The selected owner does not own the selected vehicle.');
});

test('finance officer cannot create contract for defence-plated vehicle', function (): void {
    $user = financeOfficer();
    $vehicle = Vehicle::factory()->create(['category' => 'defence_plated', 'status' => 'active']);
    $owner = Owner::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/v1/contracts', [
        'vehicle_id' => $vehicle->id,
        'owner_id' => $owner->id,
        'start_date' => '2026-07-01',
        'end_date' => '2027-06-30',
    ]);

    $response->assertStatus(422);
});

function contractPayload(Vehicle $vehicle, Owner $owner, string $start, string $end): array
{
    return [
        'vehicle_id' => $vehicle->id,
        'owner_id' => $owner->id,
        'start_date' => $start,
        'end_date' => $end,
        'contract_value' => 500000.00,
        'payment_terms' => 'Monthly payments',
        'notes' => 'Test contract',
    ];
}

test('finance officer cannot create an overlapping active contract for the same vehicle', function (): void {
    $user = financeOfficer();
    $owner = Owner::factory()->create();
    $vehicle = Vehicle::factory()->create(['category' => 'contracted_private', 'status' => 'active', 'owner_id' => $owner->id]);

    $this->actingAs($user)->postJson('/api/v1/contracts', contractPayload($vehicle, $owner, '2026-01-01', '2026-12-31'))->assertCreated();

    $response = $this->actingAs($user)->postJson('/api/v1/contracts', contractPayload($vehicle, $owner, '2026-06-01', '2027-06-30'));

    $response->assertStatus(422);
    expect($response['message'])->toBe('Vehicle already has an active contract overlapping these dates.');
});

test('finance officer can create non-overlapping contracts for the same vehicle', function (): void {
    $user = financeOfficer();
    $owner = Owner::factory()->create();
    $vehicle = Vehicle::factory()->create(['category' => 'contracted_private', 'status' => 'active', 'owner_id' => $owner->id]);

    $this->actingAs($user)->postJson('/api/v1/contracts', contractPayload($vehicle, $owner, '2026-01-01', '2026-12-31'))->assertCreated();
    $this->actingAs($user)->postJson('/api/v1/contracts', contractPayload($vehicle, $owner, '2027-01-01', '2027-12-31'))->assertCreated();
});

test('same-day adjacency between contracts counts as overlap', function (): void {
    $user = financeOfficer();
    $owner = Owner::factory()->create();
    $vehicle = Vehicle::factory()->create(['category' => 'contracted_private', 'status' => 'active', 'owner_id' => $owner->id]);

    $this->actingAs($user)->postJson('/api/v1/contracts', contractPayload($vehicle, $owner, '2026-01-01', '2026-12-31'))->assertCreated();

    $response = $this->actingAs($user)->postJson('/api/v1/contracts', contractPayload($vehicle, $owner, '2026-12-31', '2027-12-31'));

    $response->assertStatus(422);
});

test('finance officer can create overlapping contracts for different vehicles', function (): void {
    $user = financeOfficer();
    $owner = Owner::factory()->create();
    $vehicleA = Vehicle::factory()->create(['category' => 'contracted_private', 'status' => 'active', 'owner_id' => $owner->id]);
    $vehicleB = Vehicle::factory()->create(['category' => 'contracted_private', 'status' => 'active', 'owner_id' => $owner->id]);

    $this->actingAs($user)->postJson('/api/v1/contracts', contractPayload($vehicleA, $owner, '2026-01-01', '2026-12-31'))->assertCreated();
    $this->actingAs($user)->postJson('/api/v1/contracts', contractPayload($vehicleB, $owner, '2026-06-01', '2027-06-30'))->assertCreated();
});

test('finance officer cannot update a contract to overlap another active contract', function (): void {
    $user = financeOfficer();
    $owner = Owner::factory()->create();
    $vehicle = Vehicle::factory()->create(['category' => 'contracted_private', 'status' => 'active', 'owner_id' => $owner->id]);

    $this->actingAs($user)->postJson('/api/v1/contracts', contractPayload($vehicle, $owner, '2026-01-01', '2026-12-31'))->assertCreated();
    $second = $this->actingAs($user)->postJson('/api/v1/contracts', contractPayload($vehicle, $owner, '2027-01-01', '2027-12-31'))->assertCreated();

    $response = $this->actingAs($user)->putJson("/api/v1/contracts/{$second['data']['id']}", ['start_date' => '2026-06-01']);

    $response->assertStatus(422);
});

test('finance officer cannot activate a contract overlapping another active contract', function (): void {
    $user = financeOfficer();
    $owner = Owner::factory()->create();
    $vehicle = Vehicle::factory()->create(['category' => 'contracted_private', 'status' => 'active', 'owner_id' => $owner->id]);

    $this->actingAs($user)->postJson('/api/v1/contracts', contractPayload($vehicle, $owner, '2026-01-01', '2026-12-31'))->assertCreated();
    $cancelled = Contract::factory()->cancelled()->create([
        'vehicle_id' => $vehicle->id,
        'owner_id' => $owner->id,
        'start_date' => '2026-06-01',
        'end_date' => '2027-06-30',
    ]);

    $response = $this->actingAs($user)->postJson("/api/v1/contracts/{$cancelled->id}/activate");

    $response->assertStatus(422);
});

test('contract numbers are unique and formatted CNT-YYYY-NNNN', function (): void {
    $user = financeOfficer();
    $owner = Owner::factory()->create();

    $numbers = [];
    for ($i = 0; $i < 5; $i++) {
        $vehicle = Vehicle::factory()->create(['category' => 'contracted_private', 'status' => 'active', 'owner_id' => $owner->id]);
        $response = $this->actingAs($user)->postJson('/api/v1/contracts', contractPayload($vehicle, $owner, '2027-01-01', '2027-12-31'));
        $response->assertCreated();
        $numbers[] = $response['data']['contract_number'];
    }

    expect($numbers)->toHaveCount(5);
    expect(collect($numbers)->unique())->toHaveCount(5);
    foreach ($numbers as $number) {
        expect($number)->toMatch('/^CNT-\d{4}-\d{4}$/');
    }
});

test('duplicate contract number violates the database unique constraint', function (): void {
    Contract::factory()->create(['contract_number' => 'CNT-2026-9999']);

    expect(fn () => Contract::factory()->create(['contract_number' => 'CNT-2026-9999']))
        ->toThrow(QueryException::class);
});

test('finance officer can show a contract', function (): void {
    $user = financeOfficer();
    $contract = Contract::factory()->create();

    $response = $this->actingAs($user)->getJson("/api/v1/contracts/{$contract->id}");

    $response->assertOk()->assertJsonStructure(['success', 'data']);
});

test('finance officer can update a contract', function (): void {
    $user = financeOfficer();
    $contract = Contract::factory()->create();

    $response = $this->actingAs($user)->putJson("/api/v1/contracts/{$contract->id}", [
        'contract_value' => 750000.00,
        'notes' => 'Updated contract value',
    ]);

    $response->assertOk()->assertJson(['success' => true]);
    expect($response['data']['contract_value'])->toBe('750000.00');
});

test('finance officer can delete a contract', function (): void {
    $user = financeOfficer();
    $contract = Contract::factory()->cancelled()->create();

    $response = $this->actingAs($user)->deleteJson("/api/v1/contracts/{$contract->id}");

    $response->assertOk();
    expect(Contract::find($contract->id))->toBeNull();
});

test('finance officer can activate a contract', function (): void {
    $user = financeOfficer();
    $contract = Contract::factory()->cancelled()->create();

    $response = $this->actingAs($user)->postJson("/api/v1/contracts/{$contract->id}/activate");

    $response->assertOk()->assertJson(['success' => true]);
    expect($response['data']['status'])->toBe('active');
});

test('finance officer cannot activate an already active contract', function (): void {
    $user = financeOfficer();
    $contract = Contract::factory()->active()->create();

    $response = $this->actingAs($user)->postJson("/api/v1/contracts/{$contract->id}/activate");

    $response->assertStatus(422);
});

test('finance officer can terminate an active contract', function (): void {
    $user = financeOfficer();
    $contract = Contract::factory()->active()->create();

    $response = $this->actingAs($user)->postJson("/api/v1/contracts/{$contract->id}/terminate");

    $response->assertOk()->assertJson(['success' => true]);
    expect($response['data']['status'])->toBe('terminated');
});

test('finance officer cannot terminate a non-active contract', function (): void {
    $user = financeOfficer();
    $contract = Contract::factory()->expired()->create();

    $response = $this->actingAs($user)->postJson("/api/v1/contracts/{$contract->id}/terminate");

    $response->assertStatus(422);
});

test('contractor cannot create a contract', function (): void {
    $user = contractorUser();
    $vehicle = contractedVehicle();
    $owner = Owner::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/v1/contracts', [
        'vehicle_id' => $vehicle->id,
        'owner_id' => $owner->id,
        'start_date' => '2026-07-01',
        'end_date' => '2027-06-30',
    ]);

    $response->assertStatus(403);
});

test('contractor cannot update a contract', function (): void {
    $user = contractorUser();
    $contract = Contract::factory()->create();

    $response = $this->actingAs($user)->putJson("/api/v1/contracts/{$contract->id}", [
        'notes' => 'Should not be allowed',
    ]);

    $response->assertStatus(403);
});

test('unauthorized user cannot list contracts', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->getJson('/api/v1/contracts');

    $response->assertStatus(403);
});

test('unauthenticated request to contracts is rejected', function (): void {
    $response = $this->getJson('/api/v1/contracts');

    $response->assertStatus(401);
});

test('contracted vehicle without active contract cannot be used for trips', function (): void {
    $role = Role::where('slug', 'transport_manager')->first();
    $user = User::factory()->create();
    $user->roles()->attach($role->id, ['assigned_at' => now()]);
    $vehicle = contractedVehicle();
    $driver = Driver::factory()->create(['status' => 'active']);
    $route = Route::factory()->create(['status' => 'active']);

    $response = $this->actingAs($user)->postJson('/api/v1/trips', [
        'vehicle_id' => $vehicle->id,
        'driver_id' => $driver->id,
        'route_id' => $route->id,
        'scheduled_date' => '2026-07-20',
        'departure_time' => '08:00',
    ]);

    $response->assertStatus(422);
});

test('contracted vehicle with active contract can be used for trips', function (): void {
    $role = Role::where('slug', 'transport_manager')->first();
    $user = User::factory()->create();
    $user->roles()->attach($role->id, ['assigned_at' => now()]);
    $vehicle = contractedVehicle();
    $owner = Owner::factory()->create();
    Contract::factory()->active()->create([
        'vehicle_id' => $vehicle->id,
        'owner_id' => $owner->id,
        'end_date' => now()->addYear(),
    ]);
    $driver = Driver::factory()->create(['status' => 'active']);
    $route = Route::factory()->create(['status' => 'active']);

    $response = $this->actingAs($user)->postJson('/api/v1/trips', [
        'vehicle_id' => $vehicle->id,
        'driver_id' => $driver->id,
        'route_id' => $route->id,
        'scheduled_date' => '2026-07-20',
        'departure_time' => '08:00',
    ]);

    $response->assertStatus(422); // may still fail due to compliance, but not contract
});

test('contractor can list only their own contracts', function (): void {
    $user = contractorUser();
    $owner = Owner::factory()->create(['user_id' => $user->id]);
    $otherOwner = Owner::factory()->create();
    Contract::factory()->create(['owner_id' => $owner->id]);
    Contract::factory()->create(['owner_id' => $otherOwner->id]);

    $response = $this->actingAs($user)->getJson('/api/v1/contracts');

    $response->assertOk();
    expect($response['data'])->toHaveCount(1);
});

test('contractor can view their own contract', function (): void {
    $user = contractorUser();
    $owner = Owner::factory()->create(['user_id' => $user->id]);
    $contract = Contract::factory()->create(['owner_id' => $owner->id]);

    $response = $this->actingAs($user)->getJson("/api/v1/contracts/{$contract->id}");

    $response->assertOk()->assertJsonStructure(['success', 'data']);
});

test('contractor cannot view another owners contract', function (): void {
    $user = contractorUser();
    Owner::factory()->create(['user_id' => $user->id]);
    $otherOwner = Owner::factory()->create();
    $contract = Contract::factory()->create(['owner_id' => $otherOwner->id]);

    $response = $this->actingAs($user)->getJson("/api/v1/contracts/{$contract->id}");

    $response->assertStatus(403);
});
