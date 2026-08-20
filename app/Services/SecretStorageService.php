<?php

namespace App\Services;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Manages encrypted file blobs on the dedicated secrets disk with streamed I/O and directory partitioning. */
class SecretStorageService
{
    private const DISK_NAME = 'secrets';

    public function disk(): Filesystem
    {
        return Storage::disk(self::DISK_NAME);
    }

    /**
     * Store an encrypted file using streaming to avoid loading into memory.
     * The file content is already encrypted client-side, we only store the blob.
     */
    public function store(#[\SensitiveParameter] string $token, UploadedFile $file): string
    {
        $path = $this->buildPath($token);

        $stream = fopen((string) $file->getRealPath(), 'rb');

        if ($stream === false) {
            throw new RuntimeException('Unable to read the uploaded file.');
        }

        try {
            $this->disk()->writeStream($path, $stream);
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }

        $this->invalidateSizeCache();

        return $path;
    }

    /**
     * Check if an encrypted file exists.
     */
    public function exists(#[\SensitiveParameter] string $path): bool
    {
        return $this->disk()->exists($path);
    }

    /**
     * Get the size of an encrypted file in bytes.
     */
    public function size(#[\SensitiveParameter] string $path): int
    {
        return $this->disk()->size($path);
    }

    /**
     * Stream download the encrypted file.
     * Returns the raw encrypted bytes for client-side decryption.
     *
     * Security headers:
     * - X-Content-Type-Options: nosniff - Prevents MIME type sniffing
     * - Content-Disposition: attachment - Forces download, never inline
     * - Cache-Control: no-store - Prevents caching of sensitive data
     * - X-Download-Options: noopen - IE: prevents direct open
     */
    public function download(#[\SensitiveParameter] string $path): StreamedResponse
    {
        return $this->disk()->download(
            $path,
            'encrypted',
            [
                'Content-Type' => 'application/octet-stream',
                'X-Content-Type-Options' => 'nosniff',
                'Content-Disposition' => 'attachment; filename="encrypted"',
                'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
                'Pragma' => 'no-cache',
                'X-Download-Options' => 'noopen',
            ]
        );
    }

    /**
     * Get the encrypted file contents as a stream.
     *
     * @return resource|null
     */
    public function readStream(#[\SensitiveParameter] string $path)
    {
        return $this->disk()->readStream($path);
    }

    /**
     * Delete an encrypted file and clean up empty parent directory.
     */
    public function delete(#[\SensitiveParameter] string $path): bool
    {
        if (! $this->exists($path)) {
            return false;
        }

        $this->disk()->delete($path);
        $this->invalidateSizeCache();

        $dir = dirname($path);

        if ($dir !== '.' && $this->disk()->exists($dir) && empty($this->disk()->files($dir))) {
            $this->disk()->deleteDirectory($dir);
        }

        return true;
    }

    /**
     * Total size of all stored files in bytes (cached 5 minutes).
     */
    public function totalSize(): int
    {
        return (int) Cache::remember('secrets:total_file_size', 300, function () {
            return collect($this->disk()->allFiles())
                ->sum(fn (string $file) => $this->disk()->size($file));
        });
    }

    public function isQuotaExceeded(): bool
    {
        $quotaMb = config('secrets.file_storage_quota_mb');

        if ($quotaMb <= 0) {
            return false;
        }

        return $this->totalSize() >= $quotaMb * 1024 * 1024;
    }

    private function invalidateSizeCache(): void
    {
        Cache::forget('secrets:total_file_size');
    }

    /**
     * Build the storage path for a secret file.
     * Partitions into subdirectories using first 2 chars of token.
     */
    private function buildPath(#[\SensitiveParameter] string $token): string
    {
        return substr($token, 0, 2).'/'.$token;
    }
}
