<?php

namespace App\Services;

use App\Enums\SecretType;
use App\Models\Secret;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\UnableToRetrieveMetadata;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Manages encrypted file blobs on the dedicated secrets disk with streamed I/O and directory partitioning,
 * and accounts for everything the application stores — blobs and text ciphertexts — against the global quota.
 */
class SecretStorageService
{
    public const TOTAL_SIZE_CACHE_KEY = 'secrets:total_file_size';

    public const TOTAL_TEXT_SIZE_CACHE_KEY = 'secrets:total_text_size';

    /** Cache key of StatsService::getCurrentDiskUsage(), shown on the superadmin dashboard. */
    public const DISK_USAGE_CACHE_KEY = 'disk_usage_secrets';

    /**
     * Text ciphertexts are summed in the database, and their rows are also removed by readers,
     * the purge command and expiration, so the total is refreshed often rather than invalidated.
     */
    private const TEXT_SIZE_CACHE_SECONDS = 60;

    /** Minimum age of an unreferenced blob before it is treated as orphan, so in-flight uploads are never deleted. */
    public const ORPHAN_GRACE_SECONDS = 3600;

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
     * Delete an encrypted file.
     *
     * Partition directories are never pruned: removing one could race with a
     * concurrent upload into the same partition.
     */
    public function delete(#[\SensitiveParameter] string $path): bool
    {
        if (! $this->exists($path)) {
            return false;
        }

        $this->disk()->delete($path);
        $this->invalidateSizeCache();

        return true;
    }

    /**
     * Blobs not referenced by any file secret and older than the grace period.
     *
     * @param  array<int, string>  $files
     * @return list<string>
     */
    public function orphans(array $files): array
    {
        $referencedPaths = Secret::query()
            ->where('type', SecretType::File)
            ->whereNotNull('file_path')
            ->pluck('file_path')
            ->filter(fn (mixed $path): bool => is_string($path))
            ->flip()
            ->all();

        $cutoff = now()->subSeconds(self::ORPHAN_GRACE_SECONDS)->getTimestamp();

        return array_values(array_filter(
            $files,
            fn (string $file): bool => ! isset($referencedPaths[$file]) && $this->isOlderThan($file, $cutoff),
        ));
    }

    /**
     * Delete a blob only if no secret references it at this moment.
     */
    public function deleteOrphan(#[\SensitiveParameter] string $path): bool
    {
        if (Secret::query()->where('file_path', $path)->exists()) {
            return false;
        }

        return $this->delete($path);
    }

    /**
     * Total size of all stored files in bytes (cached 5 minutes).
     */
    public function totalSize(): int
    {
        return (int) Cache::remember(self::TOTAL_SIZE_CACHE_KEY, 300, function () {
            return collect($this->disk()->allFiles())
                ->sum(fn (string $file) => $this->disk()->size($file));
        });
    }

    /**
     * Total size of all stored text ciphertexts in bytes (cached 60 seconds).
     *
     * The column holds base64url, so one character is one byte and LENGTH() reports
     * the same count on SQLite, MySQL and PostgreSQL.
     */
    public function totalTextSize(): int
    {
        return (int) Cache::remember(self::TOTAL_TEXT_SIZE_CACHE_KEY, self::TEXT_SIZE_CACHE_SECONDS, function (): int {
            return (int) DB::table('secrets')
                ->whereNotNull('ciphertext')
                ->sum(DB::raw('LENGTH(ciphertext)'));
        });
    }

    /** Everything the application stores: blobs on the secrets disk and text ciphertexts in the database. */
    public function totalStoredBytes(): int
    {
        return $this->totalSize() + $this->totalTextSize();
    }

    /** Configured global quota in bytes, 0 when unlimited. */
    public function quotaBytes(): int
    {
        $quotaMb = Config::integer('secrets.storage_quota_mb');

        if ($quotaMb <= 0) {
            return 0;
        }

        return $quotaMb * 1024 * 1024;
    }

    /** Share of the global quota already used, 0.0 when unlimited. */
    public function usageRatio(): float
    {
        $quotaBytes = $this->quotaBytes();

        if ($quotaBytes === 0) {
            return 0.0;
        }

        return $this->totalStoredBytes() / $quotaBytes;
    }

    public function isQuotaExceeded(): bool
    {
        $quotaBytes = $this->quotaBytes();

        if ($quotaBytes === 0) {
            return false;
        }

        return $this->totalStoredBytes() >= $quotaBytes;
    }

    private function isOlderThan(string $path, int $timestamp): bool
    {
        try {
            return $this->disk()->lastModified($path) <= $timestamp;
        } catch (UnableToRetrieveMetadata) {
            return false;
        }
    }

    private function invalidateSizeCache(): void
    {
        Cache::forget(self::TOTAL_SIZE_CACHE_KEY);
        Cache::forget(self::DISK_USAGE_CACHE_KEY);
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
