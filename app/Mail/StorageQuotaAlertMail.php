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

    private const IDENTITY_GRADIENT_START = '#d97706';

    private const IDENTITY_GRADIENT_END = '#ea580c';

    private const IDENTITY_HEADER_BG_START = 'rgba(217, 119, 6, 0.06)';

    private const IDENTITY_HEADER_BG_END = 'rgba(234, 88, 12, 0.02)';

    private const IDENTITY_HEADER_BG_DARK_START = 'rgba(217, 119, 6, 0.12)';

    private const IDENTITY_HEADER_BG_DARK_END = 'rgba(234, 88, 12, 0.04)';

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
                'gradientStart' => self::IDENTITY_GRADIENT_START,
                'gradientEnd' => self::IDENTITY_GRADIENT_END,
                'headerBgStart' => self::IDENTITY_HEADER_BG_START,
                'headerBgEnd' => self::IDENTITY_HEADER_BG_END,
                'headerBgDarkStart' => self::IDENTITY_HEADER_BG_DARK_START,
                'headerBgDarkEnd' => self::IDENTITY_HEADER_BG_DARK_END,
                ...$this->severityPalette(),
            ],
        );
    }

    /**
     * The severity colour is confined to the level pill and to the gauge percentage.
     *
     * Everything else keeps the amber super-admin identity, so a critical alert still reads as an
     * operator email rather than a differently branded one. The critical percentage switches to
     * red-400 in dark mode, where red-700 would only reach a 1.5:1 ratio on the slate container.
     *
     * @return array{
     *     pillStart: string,
     *     pillEnd: string,
     *     gaugeColor: string,
     *     gaugeDarkColor: string
     * }
     */
    private function severityPalette(): array
    {
        if ($this->level === CheckStorageQuotaCommand::LEVEL_CRITICAL) {
            return [
                'pillStart' => '#b91c1c',
                'pillEnd' => '#7f1d1d',
                'gaugeColor' => '#b91c1c',
                'gaugeDarkColor' => '#f87171',
            ];
        }

        return [
            'pillStart' => self::IDENTITY_GRADIENT_START,
            'pillEnd' => self::IDENTITY_GRADIENT_END,
            'gaugeColor' => self::IDENTITY_GRADIENT_END,
            'gaugeDarkColor' => self::IDENTITY_GRADIENT_END,
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
