<?php

namespace App\Domain\Shared\Filesystem;

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Support\ServiceProvider;
use League\Flysystem\Filesystem;

class EncryptedLocalFilesystemServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->make(FilesystemManager::class)->extend('encrypted-local', function ($app, $config) {
            $adapter = new EncryptedLocalFilesystem(
                root: $config['root'],
            );

            return new FilesystemAdapter(
                new Filesystem($adapter),
                $adapter,
                $config,
            );
        });
    }
}
