<?php

namespace App\Domain\Shared\Filesystem;

use Illuminate\Support\Facades\Crypt;
use League\Flysystem\Config;
use League\Flysystem\Local\LocalFilesystemAdapter;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToWriteFile;

class EncryptedLocalFilesystem extends LocalFilesystemAdapter
{
    public function __construct(string $root, ?Config $config = null)
    {
        parent::__construct($root, null, LOCK_EX, LocalFilesystemAdapter::DISALLOW_LINKS, $config);
    }

    public function read(string $path): string
    {
        try {
            $encrypted = parent::read($path);

            return Crypt::decryptString($encrypted);
        } catch (\Exception $e) {
            throw UnableToReadFile::fromPath($path, previous: $e);
        }
    }

    public function write(string $path, string $contents, Config $config): void
    {
        try {
            $encrypted = Crypt::encryptString($contents);
            parent::write($path, $encrypted, $config);
        } catch (\Exception $e) {
            throw UnableToWriteFile::atLocation($path, previous: $e);
        }
    }
}
