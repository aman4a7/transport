<?php

use App\Domain\Shared\Filesystem\EncryptedLocalFilesystemServiceProvider;
use App\Providers\AppServiceProvider;
use App\Providers\HorizonServiceProvider;

return [
    AppServiceProvider::class,
    HorizonServiceProvider::class,
    EncryptedLocalFilesystemServiceProvider::class,
];
