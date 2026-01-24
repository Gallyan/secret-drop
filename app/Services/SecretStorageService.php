<?php

namespace App\Services;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SecretStorageService
{
    private const DISK_NAME = 'secrets';

    public function disk(): Filesystem
    {
        return Storage::disk(self::DISK_NAME);
    }

    /**
     * Store an encrypted file.
     * The file content is already encrypted client-side, we only store the blob.
     */
    public function store(string $token, UploadedFile $file): string
    {
        $path = $this->buildPath($token);

        $this->disk()->put($path, $file->getContent());

        return $path;
    }

    /**
     * Check if an encrypted file exists.
     */
    public function exists(string $path): bool
    {
        return $this->disk()->exists($path);
    }

    /**
     * Get the size of an encrypted file in bytes.
     */
    public function size(string $path): int
    {
        return $this->disk()->size($path);
    }

    /**
     * Stream download the encrypted file.
     * Returns the raw encrypted bytes for client-side decryption.
     */
    public function download(string $path): StreamedResponse
    {
        return $this->disk()->download(
            $path,
            'encrypted',
            ['Content-Type' => 'application/octet-stream']
        );
    }

    /**
     * Get the encrypted file contents as a stream.
     *
     * @return resource|null
     */
    public function readStream(string $path)
    {
        return $this->disk()->readStream($path);
    }

    /**
     * Delete an encrypted file.
     */
    public function delete(string $path): bool
    {
        if (! $this->exists($path)) {
            return false;
        }

        return $this->disk()->delete($path);
    }

    /**
     * Build the storage path for a secret file.
     */
    private function buildPath(string $token): string
    {
        // Use a simple flat structure with the token as filename
        // The token is already a secure random string
        return $token;
    }
}
