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

    public function writeStream(string $path, $contents, Config $config): void
    {
        try {
            $plaintext = stream_get_contents($contents);

            if ($plaintext === false) {
                throw new \RuntimeException('Unable to read from source stream.');
            }

            $this->write($path, $plaintext, $config);
        } catch (\Exception $e) {
            throw UnableToWriteFile::atLocation($path, previous: $e);
        }
    }

    public function readStream(string $path)
    {
        try {
            $stream = fopen('php://temp', 'r+b');
            fwrite($stream, $this->read($path));
            rewind($stream);

            return $stream;
        } catch (\Exception $e) {
            throw UnableToReadFile::fromLocation($path, previous: $e);
        }
    }
}
