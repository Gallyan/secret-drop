<?php

namespace Tests\Feature;

use App\Console\Commands\CheckStorageQuotaCommand;
use App\Mail\MagicLinkMail;
use App\Mail\StorageQuotaAlertMail;
use App\Mail\SuperAdminMagicLinkMail;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Transport\ArrayTransport;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\SentMessage;
use Tests\TestCase;

/**
 * Every outgoing subject starts with an emoji built in the mailable, never stored in a translation.
 */
class EmailSubjectTest extends TestCase
{
    private const RECIPIENT = 'operator@example.com';

    private const QUOTA = 1000000;

    /** Vérifie que le sujet du magic link porte l'emoji de marque devant la traduction. */
    public function testMagicLinkSubjectCarriesTheBrandEmoji(): void
    {
        $mail = new MagicLinkMail('https://example.com/fr/admin/verify/abc');

        $this->assertSame(
            '🔐 '.__('messages.email_magic_link_subject'),
            $mail->envelope()->subject,
        );
        $this->assertSame(MagicLinkMail::BRAND_EMOJI, '🔐');
    }

    /** Vérifie que le sujet du magic link superadmin porte l'emoji de marque. */
    public function testSuperAdminSubjectCarriesTheBrandEmoji(): void
    {
        $mail = new SuperAdminMagicLinkMail('https://example.com/fr/superadmin/verify/abc');

        $this->assertSame(
            '🔐 '.__('messages.email_superadmin_subject'),
            $mail->envelope()->subject,
        );
    }

    /** Vérifie que l'alerte d'avertissement porte l'emoji de sévérité, sans emoji de marque ni préfixe entre crochets. */
    public function testWarningAlertSubjectCarriesTheWarningEmoji(): void
    {
        $subject = (string) $this->alertMail(CheckStorageQuotaCommand::LEVEL_WARNING, 850000)->envelope()->subject;

        $this->assertSame(
            '⚠️ '.__('messages.email_storage_quota_subject', ['percent' => '85', 'app' => config('app.name')]),
            $subject,
        );
        $this->assertStringStartsWith('⚠️ ', $subject);
        $this->assertStringNotContainsString('🔐', $subject);
        $this->assertStringNotContainsString('[', $subject);
        $this->assertStringNotContainsString(__('messages.email_storage_quota_level_warning'), $subject);
    }

    /** Vérifie que l'alerte critique porte l'emoji de sévérité critique. */
    public function testCriticalAlertSubjectCarriesTheCriticalEmoji(): void
    {
        $subject = (string) $this->alertMail(CheckStorageQuotaCommand::LEVEL_CRITICAL, 970000)->envelope()->subject;

        $this->assertSame(
            '🚨 '.__('messages.email_storage_quota_subject', ['percent' => '97', 'app' => config('app.name')]),
            $subject,
        );
        $this->assertStringNotContainsString('[', $subject);
    }

    /** Vérifie que le niveau reste visible dans le corps de l'alerte alors qu'il a quitté le sujet. */
    public function testAlertBodyStillShowsTheLevel(): void
    {
        $rendered = $this->alertMail(CheckStorageQuotaCommand::LEVEL_CRITICAL, 970000)->render();

        $this->assertStringContainsString(e(__('messages.email_storage_quota_level_critical')), $rendered);
    }

    /**
     * Vérifie que l'emoji survit à l'encodage MIME des en-têtes : le sujet transmis est encodé
     * en =?utf-8?...?= et se redécode exactement, emoji compris.
     */
    public function testEmojisSurviveTheMimeHeaderEncoding(): void
    {
        $mailables = [
            '🔐' => new MagicLinkMail('https://example.com/fr/admin/verify/abc'),
            '⚠️' => $this->alertMail(CheckStorageQuotaCommand::LEVEL_WARNING, 850000),
            '🚨' => $this->alertMail(CheckStorageQuotaCommand::LEVEL_CRITICAL, 970000),
        ];

        foreach ($mailables as $emoji => $mailable) {
            $expected = (string) $mailable->envelope()->subject;
            $raw = $this->rawMessage($mailable);

            $this->assertStringNotContainsString($emoji, $raw, 'The subject header must be MIME encoded, not raw UTF-8.');
            $this->assertMatchesRegularExpression('/^Subject: =\?utf-8\?/mi', $raw);

            /** @var array<string, string|array<int, string>> $headers */
            $headers = (array) iconv_mime_decode_headers($raw, ICONV_MIME_DECODE_CONTINUE_ON_ERROR, 'UTF-8');
            $decoded = $headers['Subject'] ?? '';

            $this->assertSame($expected, is_array($decoded) ? reset($decoded) : $decoded);
            $this->assertStringStartsWith($emoji, $expected);
        }
    }

    /** Vérifie que les 11 locales exposent les mêmes clés et gardent des sujets sans emoji ni placeholder :level. */
    public function testSubjectTranslationsStayCompleteAndEmojiFree(): void
    {
        $files = glob(lang_path('*/messages.php'));

        $this->assertIsArray($files);
        $this->assertCount(11, $files);

        $reference = null;

        foreach ($files as $file) {
            /** @var array<string, string> $messages */
            $messages = require $file;
            $keys = array_keys($messages);
            sort($keys);

            $reference ??= $keys;
            $this->assertSame($reference, $keys, "Locale file {$file} does not hold the same keys as the others.");

            foreach (['email_magic_link_subject', 'email_superadmin_subject', 'email_storage_quota_subject'] as $key) {
                $this->assertArrayHasKey($key, $messages, "Missing {$key} in {$file}.");

                foreach (['🔐', '⚠️', '🚨'] as $emoji) {
                    $this->assertStringNotContainsString($emoji, $messages[$key], "Emoji hardcoded in {$key} of {$file}.");
                }
            }

            $this->assertStringNotContainsString(':level', $messages['email_storage_quota_subject']);
            $this->assertStringContainsString(':percent', $messages['email_storage_quota_subject']);
            $this->assertArrayHasKey('email_storage_quota_level_warning', $messages);
            $this->assertArrayHasKey('email_storage_quota_level_critical', $messages);
        }
    }

    private function alertMail(string $level, int $used): StorageQuotaAlertMail
    {
        return new StorageQuotaAlertMail($level, $used, self::QUOTA);
    }

    /** Sends the mailable through the array transport and returns the message as it leaves the mailer. */
    private function rawMessage(Mailable $mailable): string
    {
        Mail::mailer('array')->to(self::RECIPIENT)->send($mailable);

        $transport = Mail::mailer('array')->getSymfonyTransport();

        $this->assertInstanceOf(ArrayTransport::class, $transport);

        $sent = $transport->messages()->last();

        $this->assertInstanceOf(SentMessage::class, $sent);

        return $sent->toString();
    }
}
