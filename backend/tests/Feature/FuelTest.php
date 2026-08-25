<?php

use App\Domain\Fuel\Models\FuelStock;
use App\Domain\Fuel\Models\FuelTransaction;
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

function actingAsFuelAttendant(): User
{
    $role = Role::where('slug', 'fuel_attendant')->first();
    $user = User::factory()->create();
    $user->roles()->attach($role->id, ['assigned_at' => now()]);

    return $user;
}

function createDefenceVehicle(): Vehicle
{
    return Vehicle::factory()->create([
        'category' => 'defence_plated',
        'status' => 'active',
        'seating_capacity' => 10,
    ]);
}

function seedStock(string $fuelType, float $quantity = 1000): FuelStock
{
    return FuelStock::factory()->create([
        'fuel_type' => $fuelType,
        'current_quantity' => $quantity,
    ]);
}

beforeEach(function (): void {
    $this->user = actingAsFuelAttendant();
    $this->vehicle = createDefenceVehicle();
    seedStock('diesel', 1000);
    seedStock('petrol', 500);
});

test('fuel attendant can list transactions', function (): void {
    FuelTransaction::factory()->count(3)->create();

    $response = $this->actingAs($this->user)->getJson('/api/v1/fuel/transactions');

    $response->assertOk()->assertJsonStructure(['success', 'data', 'meta']);
    expect($response['data'])->toHaveCount(3);
});

test('fuel attendant can issue fuel to defence-plated vehicle', function (): void {
    $response = $this->actingAs($this->user)->postJson('/api/v1/fuel/issue', [
        'vehicle_id' => $this->vehicle->id,
        'fuel_type' => 'diesel',
        'quantity' => 50,
        'notes' => 'Test issue',
    ]);

    $response->assertCreated()->assertJson(['success' => true, 'message' => 'Fuel issued successfully.']);
    expect($response['data']['transaction_type'])->toBe('issue');
    expect((float) $response['data']['quantity'])->toBe(-50.0);
});

test('issuing fuel to contracted vehicle fails', function (): void {
    $contracted = Vehicle::factory()->create([
        'category' => 'contracted_private',
        'status' => 'active',
    ]);

    $response = $this->actingAs($this->user)->postJson('/api/v1/fuel/issue', [
        'vehicle_id' => $contracted->id,
        'fuel_type' => 'diesel',
        'quantity' => 50,
    ]);

    $response->assertStatus(422);
});

test('issuing fuel without sufficient stock fails', function (): void {
    $response = $this->actingAs($this->user)->postJson('/api/v1/fuel/issue', [
        'vehicle_id' => $this->vehicle->id,
        'fuel_type' => 'diesel',
        'quantity' => 999999,
    ]);

    $response->assertStatus(422);
});

test('issuing fuel decrements stock', function (): void {
    $this->actingAs($this->user)->postJson('/api/v1/fuel/issue', [
        'vehicle_id' => $this->vehicle->id,
        'fuel_type' => 'diesel',
        'quantity' => 200,
    ]);

    $stock = FuelStock::where('fuel_type', 'diesel')->first();
    expect((float) $stock->current_quantity)->toBe(800.0);
});

test('fuel attendant can restock fuel', function (): void {
    $response = $this->actingAs($this->user)->postJson('/api/v1/fuel/restock', [
        'fuel_type' => 'diesel',
        'quantity' => 500,
        'unit_cost' => 50,
        'notes' => 'Monthly restock',
    ]);

    $response->assertCreated();
    $stock = FuelStock::where('fuel_type', 'diesel')->first();
    expect((float) $stock->current_quantity)->toBe(1500.0);
});

test('fuel attendant can adjust stock', function (): void {
    $response = $this->actingAs($this->user)->postJson('/api/v1/fuel/adjust', [
        'fuel_type' => 'petrol',
        'quantity' => -100,
        'notes' => 'Correcting inventory discrepancy due to measurement error',
    ]);

    $response->assertCreated();
    $stock = FuelStock::where('fuel_type', 'petrol')->first();
    expect((float) $stock->current_quantity)->toBe(400.0);
});

test('adjustment requiring reason fails without notes', function (): void {
    $response = $this->actingAs($this->user)->postJson('/api/v1/fuel/adjust', [
        'fuel_type' => 'petrol',
        'quantity' => 50,
        'notes' => 'Short',
    ]);

    $response->assertStatus(422);
});

test('fuel attendant can view stock levels', function (): void {
    $response = $this->actingAs($this->user)->getJson('/api/v1/fuel/stocks');

    $response->assertOk();
    expect($response['data'])->toHaveCount(2);
});

test('unauthorized user cannot issue fuel', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/v1/fuel/issue', [
        'vehicle_id' => $this->vehicle->id,
        'fuel_type' => 'diesel',
        'quantity' => 50,
    ]);

    $response->assertStatus(403);
});

test('unauthenticated request to fuel is rejected', function (): void {
    $response = $this->postJson('/api/v1/fuel/issue', [
        'vehicle_id' => 1,
        'fuel_type' => 'diesel',
        'quantity' => 50,
    ]);

    $response->assertStatus(401);
});

test('fuel issuance creates audit log', function (): void {
    $this->actingAs($this->user)->postJson('/api/v1/fuel/issue', [
        'vehicle_id' => $this->vehicle->id,
        'fuel_type' => 'diesel',
        'quantity' => 50,
    ]);

    $this->assertDatabaseHas('audit_logs', [
        'action' => 'fuel_issued',
        'actor_id' => $this->user->id,
    ]);
});

test('fuel restock creates audit log', function (): void {
    $this->actingAs($this->user)->postJson('/api/v1/fuel/restock', [
        'fuel_type' => 'diesel',
        'quantity' => 500,
    ]);

    $this->assertDatabaseHas('audit_logs', [
        'action' => 'fuel_restocked',
        'actor_id' => $this->user->id,
    ]);
});

test('fuel adjustment creates audit log', function (): void {
    $this->actingAs($this->user)->postJson('/api/v1/fuel/adjust', [
        'fuel_type' => 'petrol',
        'quantity' => 50,
        'notes' => 'Correcting inventory discrepancy due to measurement error',
    ]);

    $this->assertDatabaseHas('audit_logs', [
        'action' => 'fuel_stock_adjusted',
        'actor_id' => $this->user->id,
    ]);
});

test('issuing fuel to inactive vehicle fails', function (): void {
    $this->vehicle->update(['status' => 'in_maintenance']);

    $response = $this->actingAs($this->user)->postJson('/api/v1/fuel/issue', [
        'vehicle_id' => $this->vehicle->id,
        'fuel_type' => 'diesel',
        'quantity' => 50,
    ]);

    $response->assertStatus(422);
});

test('issuing fuel of matching type to a petrol vehicle succeeds', function (): void {
    $vehicle = Vehicle::factory()->create([
        'category' => 'defence_plated',
        'status' => 'active',
        'fuel_type' => 'petrol',
    ]);

    $response = $this->actingAs($this->user)->postJson('/api/v1/fuel/issue', [
        'vehicle_id' => $vehicle->id,
        'fuel_type' => 'petrol',
        'quantity' => 50,
    ]);

    $response->assertCreated();
});

test('issuing petrol to a diesel vehicle fails', function (): void {
    $response = $this->actingAs($this->user)->postJson('/api/v1/fuel/issue', [
        'vehicle_id' => $this->vehicle->id,
        'fuel_type' => 'petrol',
        'quantity' => 50,
    ]);

    $response->assertStatus(422);
    expect($response['rule'])->toBe('fuel_type_incompatible');
});

test('issuing diesel to an electric vehicle fails', function (): void {
    $vehicle = Vehicle::factory()->create([
        'category' => 'defence_plated',
        'status' => 'active',
        'fuel_type' => 'electric',
    ]);

    $response = $this->actingAs($this->user)->postJson('/api/v1/fuel/issue', [
        'vehicle_id' => $vehicle->id,
        'fuel_type' => 'diesel',
        'quantity' => 50,
    ]);

    $response->assertStatus(422);
    expect($response['rule'])->toBe('fuel_type_incompatible');
});

test('issuing petrol to a hybrid vehicle fails', function (): void {
    $vehicle = Vehicle::factory()->create([
        'category' => 'defence_plated',
        'status' => 'active',
        'fuel_type' => 'hybrid',
    ]);

    $response = $this->actingAs($this->user)->postJson('/api/v1/fuel/issue', [
        'vehicle_id' => $vehicle->id,
        'fuel_type' => 'petrol',
        'quantity' => 50,
    ]);

    $response->assertStatus(422);
    expect($response['rule'])->toBe('fuel_type_incompatible');
});

test('issuing fuel to vehicle with valid registration and insurance succeeds', function (): void {
    $this->vehicle->update([
        'registration_expiry' => now()->addYear(),
        'insurance_expiry' => now()->addYear(),
    ]);

    $response = $this->actingAs($this->user)->postJson('/api/v1/fuel/issue', [
        'vehicle_id' => $this->vehicle->id,
        'fuel_type' => 'diesel',
        'quantity' => 50,
    ]);

    $response->assertCreated();
});

test('stock levels serialize quantities as real numbers', function (): void {
    FuelStock::where('fuel_type', 'diesel')->update([
        'current_quantity' => 150.75,
        'minimum_quantity' => 100.5,
    ]);

    $response = $this->actingAs($this->user)->getJson('/api/v1/fuel/stocks');

    $response->assertOk();
    $diesel = collect($response['data'])->firstWhere('fuel_type', 'diesel');
    expect($diesel['current_quantity'])->toBeFloat();
    expect($diesel['current_quantity'])->toBe(150.75);
    expect($diesel['minimum_quantity'])->toBeFloat();
    expect($diesel['minimum_quantity'])->toBe(100.5);
});

test('fuel transaction quantity is serialized as a real number', function (): void {
    $response = $this->actingAs($this->user)->postJson('/api/v1/fuel/issue', [
        'vehicle_id' => $this->vehicle->id,
        'fuel_type' => 'diesel',
        'quantity' => 50.25,
    ]);

    $response->assertCreated();
    expect($response['data']['quantity'])->toBeFloat();
    expect($response['data']['quantity'])->toBe(-50.25);
});
