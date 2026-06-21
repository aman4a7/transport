<?php

use App\Domain\Compliance\Models\ComplianceDocument;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed([
        RoleSeeder::class,
        PermissionSeeder::class,
        RolePermissionSeeder::class,
    ]);
});

test('approved document past expiry gets marked expired', function (): void {
    $document = ComplianceDocument::factory()->approved()->create([
        'expires_at' => now()->subDay(),
    ]);

    Artisan::call('compliance:check-expirations');

    $document->refresh();
    expect($document->status->value)->toBe('expired');
});

test('approved document with future expiry is not touched', function (): void {
    $document = ComplianceDocument::factory()->approved()->create([
        'expires_at' => now()->addYear(),
    ]);

    Artisan::call('compliance:check-expirations');

    $document->refresh();
    expect($document->status->value)->toBe('approved');
});

test('pending document with past expiry is not touched', function (): void {
    $document = ComplianceDocument::factory()->create([
        'expires_at' => now()->subDay(),
    ]);

    Artisan::call('compliance:check-expirations');

    $document->refresh();
    expect($document->status->value)->toBe('pending');
});

test('rejected document with past expiry is not touched', function (): void {
    $document = ComplianceDocument::factory()->rejected()->create([
        'expires_at' => now()->subDay(),
    ]);

    Artisan::call('compliance:check-expirations');

    $document->refresh();
    expect($document->status->value)->toBe('rejected');
});

test('audit log created for each expired document', function (): void {
    $document1 = ComplianceDocument::factory()->approved()->create(['expires_at' => now()->subDay()]);
    $document2 = ComplianceDocument::factory()->approved()->create(['expires_at' => now()->subDay()]);

    Artisan::call('compliance:check-expirations');

    $this->assertDatabaseHas('audit_logs', [
        'action' => 'compliance_document.expired',
        'subject_type' => ComplianceDocument::class,
        'subject_id' => $document1->id,
    ]);
    $this->assertDatabaseHas('audit_logs', [
        'action' => 'compliance_document.expired',
        'subject_type' => ComplianceDocument::class,
        'subject_id' => $document2->id,
    ]);
});

test('running command twice is idempotent', function (): void {
    ComplianceDocument::factory()->approved()->count(3)->create([
        'expires_at' => now()->subDay(),
    ]);

    Artisan::call('compliance:check-expirations');
    $output1 = Artisan::output();

    Artisan::call('compliance:check-expirations');
    $output2 = Artisan::output();

    expect($output1)->toContain('Marked 3');
    expect($output2)->toContain('Marked 0');
});
