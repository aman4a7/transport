<?php

use App\Domain\Driver\Models\Driver;
use App\Domain\Passenger\Models\Passenger;
use App\Domain\Route\Models\Route;
use App\Domain\Vehicle\Models\Vehicle;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

function actingAsSecurityTransportManager(): User
{
    $role = Role::where('slug', 'transport_manager')->first();
    $user = User::factory()->create();
    $user->roles()->attach($role->id, ['assigned_at' => now()]);

    return $user;
}

test('client cannot spoof status or audit fields on vehicle creation', function (): void {
    $user = actingAsSecurityTransportManager();

    $response = $this->actingAs($user)->postJson('/api/v1/vehicles', [
        'plate_number' => 'SPOOF-001',
        'make' => 'Toyota',
        'model' => 'Hilux',
        'year' => 2024,
        'category' => 'defence_plated',
        'fuel_type' => 'diesel',
        'status' => 'decommissioned',
        'created_by' => 99999,
        'updated_by' => 99999,
        'id' => 123456,
    ]);

    $response->assertCreated();

    $vehicle = Vehicle::where('plate_number', 'SPOOF-001')->firstOrFail();
    expect($vehicle->status->value)->toBe('active');
    expect($vehicle->created_by)->toBe($user->id);
    expect($vehicle->updated_by)->toBeNull();
    expect($vehicle->id)->not->toBe(123456);
});

test('invalid sort field falls back to default ordering without error', function (): void {
    $user = actingAsSecurityTransportManager();
    Vehicle::factory()->create(['category' => 'defence_plated', 'plate_number' => 'SORT-001']);
    Vehicle::factory()->create(['category' => 'defence_plated', 'plate_number' => 'SORT-002']);

    $response = $this->actingAs($user)->getJson('/api/v1/vehicles?sort_by=evil_column--x&sort_dir=desc');

    $response->assertOk();
    expect($response['data'])->toHaveCount(2);
});

test('invalid sort direction is normalized to the default direction', function (): void {
    $user = actingAsSecurityTransportManager();
    Vehicle::factory()->create(['category' => 'defence_plated', 'plate_number' => 'DIR-001']);

    $response = $this->actingAs($user)->getJson('/api/v1/vehicles?sort_by=plate_number&sort_dir=BOGUS');

    $response->assertOk();
    expect($response['data'])->toHaveCount(1);
});

test('valid sort parameters still order results', function (): void {
    $user = actingAsSecurityTransportManager();
    Vehicle::factory()->create(['category' => 'defence_plated', 'plate_number' => 'ORD-001', 'make' => 'Zebra Motors']);
    Vehicle::factory()->create(['category' => 'defence_plated', 'plate_number' => 'ORD-002', 'make' => 'Alpha Motors']);

    $response = $this->actingAs($user)->getJson('/api/v1/vehicles?sort_by=make&sort_dir=asc');

    $response->assertOk();
    expect($response['data'][0]['make'])->toBe('Alpha Motors');
    expect($response['data'][1]['make'])->toBe('Zebra Motors');
});

test('driver with expired medical_expiry remains assignable while license is valid', function (): void {
    $user = actingAsSecurityTransportManager();

    $route = Route::factory()->create(['status' => 'active']);
    $vehicle = Vehicle::factory()->create([
        'category' => 'defence_plated',
        'status' => 'active',
        'seating_capacity' => 10,
        'registration_expiry' => now()->addYear(),
        'insurance_expiry' => now()->addYear(),
    ]);
    $driver = Driver::factory()->create([
        'status' => 'active',
        'license_expiry' => now()->addYear(),
        'medical_expiry' => now()->subDay(),
    ]);
    $passenger = Passenger::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/v1/trips', [
        'route_id' => $route->id,
        'vehicle_id' => $vehicle->id,
        'driver_id' => $driver->id,
        'scheduled_date' => now()->addDay()->format('Y-m-d'),
        'departure_time' => '08:00',
        'estimated_arrival_time' => '12:00',
        'passenger_ids' => [$passenger->id],
    ]);

    $response->assertCreated();
    expect($response['data']['assignments'])->toHaveCount(1);
});

test('nginx locations that define add_header repeat core security headers', function (): void {
    $confPath = dirname(__DIR__, 3).'/docker/nginx/default.conf';

    if (! file_exists($confPath)) {
        $this->markTestSkipped('nginx configuration not present in this environment.');
    }

    $conf = file_get_contents($confPath);

    // Directive parsing must ignore comments: prose legitimately uses words
    // like "location"/"add_header", which would otherwise be misparsed as
    // configuration blocks by the naive split below.
    $conf = preg_replace('/^\s*#.*$/m', '', $conf) ?? $conf;

    $blocks = preg_split('/(?=location\s)/', $conf) ?: [];
    $violations = [];

    foreach ($blocks as $block) {
        if (str_contains($block, 'add_header ') && ! str_contains($block, 'X-Content-Type-Options')) {
            $violations[] = trim(strtok($block, '{') ?? 'unknown block');
        }
    }

    expect($violations)->toBe([]);
    expect(substr_count($conf, 'Referrer-Policy'))->toBeGreaterThanOrEqual(1);
});

test('session cookies keep hardened framework defaults', function (): void {
    expect(config('session.http_only'))->toBeTrue();
    expect(config('session.same_site'))->toBe('lax');
});
