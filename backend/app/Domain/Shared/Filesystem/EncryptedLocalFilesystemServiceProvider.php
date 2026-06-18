<?php

namespace App\Domain\Shared\Filesystem;

use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Support\ServiceProvider;

class EncryptedLocalFilesystemServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->make(FilesystemManager::class)->extend('encrypted-local', function ($app, $config) {
            return new FilesystemAdapter(
                new EncryptedLocalFilesystem(
                    root: $config['root'],
                ),
            );
        });
    }
}
