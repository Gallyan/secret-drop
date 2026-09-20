<?php

namespace App\Console\Commands;

use App\Mail\StorageQuotaAlertMail;
use App\Services\SecretStorageService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Number;

/**
 * Warns the operator by email before the global storage quota is reached.
 *
 * Two levels are watched: WARNING from 80% of the quota, CRITICAL from 90%.
 * Each level is alerted at most once per ALERT_TTL_SECONDS (24 hours) thanks to
 * a cache key per level, and a level is re-armed as soon as usage falls back
 * below its threshold, so a usage hovering around a threshold cannot spam the
 * operator. Reaching CRITICAL still sends its own mail even when the WARNING
 * one was already sent, because the two levels hold separate keys.
 *
 * The --preview=warning|critical option mails a sample alert built on simulated figures,
 * whatever the real usage is, and leaves the de-duplication cache untouched so a preview
 * can neither suppress nor trigger a real alert.
 */
class CheckStorageQuotaCommand extends Command
{
    public const string LEVEL_WARNING = 'warning';

    public const string LEVEL_CRITICAL = 'critical';

    public const float WARNING_THRESHOLD = 0.80;

    public const float CRITICAL_THRESHOLD = 0.90;

    /** One alert per level per 24 hours: the cache entry expires exactly when the level is allowed to alert again. */
    public const int ALERT_TTL_SECONDS = 86400;

    public const CACHE_KEY_PREFIX = 'storage-quota-alert:';

    /** Simulated usage ratios of a preview mail, chosen inside each level's own range. */
    private const PREVIEW_WARNING_RATIO = 0.85;

    private const PREVIEW_CRITICAL_RATIO = 0.92;

    /** Fallback quota of a preview when the real quota is unlimited, so the sample figures stay plausible. */
    private const PREVIEW_FALLBACK_QUOTA_BYTES = 10 * 1024 * 1024 * 1024;

    protected $signature = 'storage:check
        {--preview= : Send a sample "warning" or "critical" alert mail to the operator, with simulated figures, without reading, writing or clearing the 24h de-duplication cache}';

    protected $description = 'Check the global storage quota and email the operator before it is reached (--preview=warning|critical sends a sample alert instead)';

    public function handle(SecretStorageService $storage): int
    {
        $preview = $this->option('preview');

        if (is_string($preview) || $this->input->hasParameterOption('--preview')) {
            return $this->sendPreview($storage, is_string($preview) ? $preview : '');
        }

        $quota = $storage->quotaBytes();
        $used = $storage->totalStoredBytes();

        if ($quota <= 0) {
            $this->info('Storage quota is unlimited: nothing to check ('.Number::fileSize($used, 2).' stored).');

            return Command::SUCCESS;
        }

        $ratio = $storage->usageRatio();

        $this->info(sprintf(
            'Storage usage: %s / %s (%s%%).',
            Number::fileSize($used, 2),
            Number::fileSize($quota, 2),
            $this->percentage($ratio),
        ));

        $this->rearmLevelsBelowThreshold($ratio);

        $level = $this->levelFor($ratio);

        if ($level === null) {
            $this->comment('Below the warning threshold ('.$this->percentage(self::WARNING_THRESHOLD).'%): no alert sent.');

            return Command::SUCCESS;
        }

        $recipient = trim(config_string('app.super_admin_email'));

        if ($recipient === '') {
            $this->warn('No operator email configured (app.super_admin_email): '.$level.' alert not sent.');

            return Command::SUCCESS;
        }

        if (! Cache::add($this->cacheKey($level), now()->getTimestamp(), self::ALERT_TTL_SECONDS)) {
            $this->comment("A {$level} alert was already sent in the last 24 hours: not sending another one.");

            return Command::SUCCESS;
        }

        Mail::to($recipient)
            ->locale(config_string('app.fallback_locale', 'en'))
            ->send(new StorageQuotaAlertMail($level, $used, $quota));

        $this->warn(sprintf('Sent the %s storage alert (%s%% used) to the operator.', $level, $this->percentage($ratio)));

        return Command::SUCCESS;
    }

    /**
     * Sends a sample alert mail so the operator can review it, whatever the real usage is.
     *
     * The de-duplication cache is deliberately left untouched: a preview must neither
     * suppress a real alert nor be suppressed by one.
     */
    private function sendPreview(SecretStorageService $storage, string $level): int
    {
        $ratios = [
            self::LEVEL_WARNING => self::PREVIEW_WARNING_RATIO,
            self::LEVEL_CRITICAL => self::PREVIEW_CRITICAL_RATIO,
        ];

        if (! isset($ratios[$level])) {
            $this->error($level === ''
                ? sprintf('The --preview option needs a value: "%s" or "%s".', self::LEVEL_WARNING, self::LEVEL_CRITICAL)
                : sprintf(
                    'Unknown --preview value "%s": expected "%s" or "%s".',
                    $level,
                    self::LEVEL_WARNING,
                    self::LEVEL_CRITICAL,
                ));

            return Command::FAILURE;
        }

        $recipient = trim(config_string('app.super_admin_email'));

        if ($recipient === '') {
            $this->error('No operator email configured (app.super_admin_email): '.$level.' preview not sent.');

            return Command::FAILURE;
        }

        $quota = $storage->quotaBytes();

        if ($quota <= 0) {
            $quota = self::PREVIEW_FALLBACK_QUOTA_BYTES;
        }

        $ratio = $ratios[$level];
        $used = (int) round($quota * $ratio);

        Mail::to($recipient)
            ->locale(config_string('app.fallback_locale', 'en'))
            ->send(new StorageQuotaAlertMail($level, $used, $quota));

        $this->warn(sprintf('PREVIEW: sent the %s storage alert to %s.', $level, $recipient));
        $this->info(sprintf(
            'Simulated figures, not the real usage: %s / %s (%s%%).',
            Number::fileSize($used, 2),
            Number::fileSize($quota, 2),
            $this->percentage($ratio),
        ));
        $this->comment('The 24h de-duplication cache was not read, written or cleared: real alerts are unaffected.');

        return Command::SUCCESS;
    }

    /** Lets a level alert again once usage has fallen back below its own threshold. */
    private function rearmLevelsBelowThreshold(float $ratio): void
    {
        foreach ($this->thresholds() as $level => $threshold) {
            if ($ratio < $threshold) {
                Cache::forget($this->cacheKey($level));
            }
        }
    }

    private function levelFor(float $ratio): ?string
    {
        if ($ratio >= self::CRITICAL_THRESHOLD) {
            return self::LEVEL_CRITICAL;
        }

        if ($ratio >= self::WARNING_THRESHOLD) {
            return self::LEVEL_WARNING;
        }

        return null;
    }

    /** @return array<string, float> */
    private function thresholds(): array
    {
        return [
            self::LEVEL_WARNING => self::WARNING_THRESHOLD,
            self::LEVEL_CRITICAL => self::CRITICAL_THRESHOLD,
        ];
    }

    private function cacheKey(string $level): string
    {
        return self::CACHE_KEY_PREFIX.$level;
    }

    private function percentage(float $ratio): string
    {
        return (string) round($ratio * 100, 1);
    }
}
