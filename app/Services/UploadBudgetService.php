<?php

namespace App\Services;

use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

/**
 * Per-IP budget over the bytes an IP keeps stored: uploaded files and text ciphertexts alike.
 *
 * A daily counter would release bytes after a day while a secret can live 90 days, letting one IP
 * fill the global quota by sustaining its daily budget. Bytes are therefore charged to a bucket per
 * expiration option and per period as long as that expiration: the current and previous periods
 * both count, so a byte stays charged at least as long as it can live, and at most twice as long.
 *
 * The check and the charge run under one lock, so concurrent uploads cannot jointly exceed the budget.
 */
class UploadBudgetService
{
    private const LOCK_SECONDS = 10;

    private const LOCK_WAIT_SECONDS = 3;

    /**
     * Charges the bytes to the IP if they fit its budget.
     *
     * Returns the charged bucket, to hand back to release() if the secret is not stored after all,
     * an empty string when nothing needed charging, or null when the budget is exceeded.
     */
    public function reserve(string $ip, string $expiration, int $bytes): ?string
    {
        $budgetBytes = Config::integer('secrets.upload_mb_per_ip') * 1024 * 1024;

        if ($bytes === 0) {
            return '';
        }

        if ($budgetBytes <= 0) {
            return '';
        }

        $bucket = null;

        try {
            Cache::lock($this->lockKey($ip), self::LOCK_SECONDS)->block(
                self::LOCK_WAIT_SECONDS,
                function () use ($ip, $expiration, $bytes, $budgetBytes, &$bucket): void {
                    if ($this->storedBytes($ip) + $bytes > $budgetBytes) {
                        return;
                    }

                    $expiration = $this->knownExpiration($expiration);
                    $retentionSeconds = $this->retentionSeconds($expiration);
                    $bucket = $this->bucketKey($ip, $expiration, $this->period($retentionSeconds));

                    Cache::add($bucket, 0, 2 * $retentionSeconds);
                    Cache::increment($bucket, $bytes);
                }
            );
        } catch (LockTimeoutException) {
            // Fail closed: an unserialized charge could let concurrent uploads exceed the budget
            return null;
        }

        return $bucket;
    }

    /** Refunds a charge whose secret could not be stored. */
    public function release(string $ip, string $bucket, int $bytes): void
    {
        if ($bucket === '') {
            return;
        }

        try {
            Cache::lock($this->lockKey($ip), self::LOCK_SECONDS)->block(
                self::LOCK_WAIT_SECONDS,
                function () use ($bucket, $bytes): void {
                    $charged = Cache::get($bucket);

                    if (! is_numeric($charged)) {
                        return;
                    }

                    Cache::decrement($bucket, min($bytes, (int) $charged));
                }
            );
        } catch (LockTimeoutException) {
            // Keeping the charge only overcounts the IP; it must not mask the storage failure being handled
        }
    }

    /** Bytes currently charged to the IP across every expiration option. */
    public function storedBytes(string $ip): int
    {
        $total = 0;

        foreach (array_keys(Config::array('secrets.expirations')) as $expiration) {
            $expiration = (string) $expiration;
            $period = $this->period($this->retentionSeconds($expiration));

            foreach ([$period, $period - 1] as $bucketPeriod) {
                // The database cache store returns counters as numeric strings
                $charged = Cache::get($this->bucketKey($ip, $expiration, $bucketPeriod));
                $total += is_numeric($charged) ? (int) $charged : 0;
            }
        }

        return $total;
    }

    /** Same fallback as the expiry computation, so the charge lands in a bucket storedBytes() reads. */
    private function knownExpiration(string $expiration): string
    {
        if (array_key_exists($expiration, Config::array('secrets.expirations'))) {
            return $expiration;
        }

        return Config::string('secrets.default_expiration');
    }

    private function retentionSeconds(string $expiration): int
    {
        return max(1, Config::integer("secrets.expirations.{$expiration}")) * 3600;
    }

    private function period(int $retentionSeconds): int
    {
        return intdiv(now()->getTimestamp(), $retentionSeconds);
    }

    private function bucketKey(string $ip, string $expiration, int $period): string
    {
        return "upload-bytes:{$ip}:{$expiration}:{$period}";
    }

    private function lockKey(string $ip): string
    {
        return "upload-bytes:{$ip}:lock";
    }
}
