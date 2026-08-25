<?php

use App\Domain\Compliance\Services\ComplianceDocumentService;
use App\Domain\Shared\Filesystem\EncryptedLocalFilesystem;
use App\Domain\Vehicle\Models\Vehicle;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed([
        RoleSeeder::class,
        PermissionSeeder::class,
        RolePermissionSeeder::class,
    ]);
});

test('compliance document contents are encrypted at rest', function (): void {
    $role = Role::where('slug', 'compliance_officer')->first();
    $officer = User::factory()->create();
    $officer->roles()->attach($role->id, ['assigned_at' => now()]);
    $vehicle = Vehicle::factory()->create(['category' => 'defence_plated']);

    $testRoot = storage_path('framework/testing/compliance-encryption-proof');
    File::ensureDirectoryExists($testRoot);
    config(['filesystems.disks.compliance.root' => $testRoot]);
    app('filesystem')->forgetDisk('compliance');

    try {
        expect(Storage::disk('compliance')->getAdapter())->toBeInstanceOf(EncryptedLocalFilesystem::class);

        $plaintext = 'TOPSECRET-PLAINTEXT-MARKER-9f3a';
        $file = UploadedFile::fake()->createWithContent('secret.pdf', $plaintext);

        $document = app(ComplianceDocumentService::class)->upload([
            'documentable_type' => Vehicle::class,
            'documentable_id' => $vehicle->id,
            'type' => 'vehicle_registration',
        ], $file, $officer);

        Storage::disk('compliance')->assertExists($document->file_path);

        $raw = file_get_contents(Storage::disk('compliance')->path($document->file_path));

        expect($raw)->not->toContain($plaintext);
        expect(Crypt::decryptString($raw))->toBe($plaintext);
        expect(Storage::disk('compliance')->get($document->file_path))->toBe($plaintext);
    } finally {
        File::deleteDirectory($testRoot);
        app('filesystem')->forgetDisk('compliance');
    }
});
