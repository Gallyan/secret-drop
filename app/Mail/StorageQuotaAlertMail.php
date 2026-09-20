<?php

namespace App\Mail;

use App\Console\Commands\CheckStorageQuotaCommand;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Number;

/** Operator alert telling how full the global storage quota is and what happens once it is full. */
class StorageQuotaAlertMail extends Mailable
{
    use PrefixesSubjectWithEmoji;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public string $level,
        public int $usedBytes,
        public int $quotaBytes,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->emojiSubject($this->levelEmoji(), __('messages.email_storage_quota_subject', [
                'percent' => $this->percentage(),
                'app' => config_string('app.name', 'Secret Drop'),
            ])),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.storage-quota-alert',
            text: 'emails.storage-quota-alert-text',
            with: [
                'level' => $this->level,
                'levelLabel' => $this->levelLabel(),
                'percent' => $this->percentage(),
                'used' => Number::fileSize($this->usedBytes, 2),
                'total' => Number::fileSize($this->quotaBytes, 2),
                ...$this->palette(),
            ],
        );
    }

    /**
     * Amber for the warning level, deep red for the critical one, in the same shape as the magic link layout.
     *
     * The critical gradient stays in the darkest half of the Tailwind red scale (red-700 to red-900)
     * so it cannot be mistaken for the amber warning identity at a glance. Its gauge percentage
     * switches to red-400 in dark mode, where red-900 would be unreadable on the slate container.
     *
     * @return array{
     *     gradientStart: string,
     *     gradientEnd: string,
     *     gaugeDarkColor: string,
     *     headerBgStart: string,
     *     headerBgEnd: string,
     *     headerBgDarkStart: string,
     *     headerBgDarkEnd: string
     * }
     */
    private function palette(): array
    {
        if ($this->level === CheckStorageQuotaCommand::LEVEL_CRITICAL) {
            return [
                'gradientStart' => '#b91c1c',
                'gradientEnd' => '#7f1d1d',
                'gaugeDarkColor' => '#f87171',
                'headerBgStart' => 'rgba(185, 28, 28, 0.10)',
                'headerBgEnd' => 'rgba(127, 29, 29, 0.04)',
                'headerBgDarkStart' => 'rgba(185, 28, 28, 0.18)',
                'headerBgDarkEnd' => 'rgba(127, 29, 29, 0.06)',
            ];
        }

        return [
            'gradientStart' => '#d97706',
            'gradientEnd' => '#ea580c',
            'gaugeDarkColor' => '#ea580c',
            'headerBgStart' => 'rgba(217, 119, 6, 0.06)',
            'headerBgEnd' => 'rgba(234, 88, 12, 0.02)',
            'headerBgDarkStart' => 'rgba(217, 119, 6, 0.12)',
            'headerBgDarkEnd' => 'rgba(234, 88, 12, 0.04)',
        ];
    }

    /** The subject carries the severity emoji instead of the brand one, the level label stays in the body. */
    private function levelEmoji(): string
    {
        return $this->level === CheckStorageQuotaCommand::LEVEL_CRITICAL
            ? self::CRITICAL_EMOJI
            : self::WARNING_EMOJI;
    }

    private function levelLabel(): string
    {
        return __('messages.email_storage_quota_level_'.$this->level);
    }

    private function percentage(): string
    {
        if ($this->quotaBytes <= 0) {
            return '0';
        }

        return (string) round($this->usedBytes / $this->quotaBytes * 100, 1);
    }
}
