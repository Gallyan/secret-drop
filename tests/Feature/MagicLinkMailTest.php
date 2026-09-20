<?php

namespace Tests\Feature;

use App\Mail\MagicLinkMail;
use App\Mail\SuperAdminMagicLinkMail;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class MagicLinkMailTest extends TestCase
{
    private const URL_WITH_SPECIAL_CHARACTERS = 'https://example.com/fr/admin/verify/abc?x=1&y="2"<b>';

    private const ESCAPED_URL = 'https://example.com/fr/admin/verify/abc?x=1&amp;y=&quot;2&quot;&lt;b&gt;';

    /** Vérifie le sujet de l'email magic link, emoji de marque compris. */
    public function testMagicLinkMailHasTranslatedSubject(): void
    {
        $mail = new MagicLinkMail(self::URL_WITH_SPECIAL_CHARACTERS);

        $this->assertSame(
            MagicLinkMail::BRAND_EMOJI.' '.__('messages.email_magic_link_subject'),
            $mail->envelope()->subject,
        );
    }

    /** Vérifie que l'email magic link rend un lien cliquable échappé, le bouton et la durée de validité. */
    public function testMagicLinkMailRendersEscapedClickableUrlAndValidity(): void
    {
        Config::set('secrets.magic_link_ttl', 10);
        $mail = new MagicLinkMail(self::URL_WITH_SPECIAL_CHARACTERS);

        $rendered = $mail->render();

        $this->assertStringContainsString('href="'.self::ESCAPED_URL.'"', $rendered);
        $this->assertStringNotContainsString('<b>', $rendered);
        $this->assertStringContainsString(e(__('messages.email_magic_link_button')), $rendered);
        $this->assertStringContainsString(e(__('messages.email_magic_link_warning', ['minutes' => 10])), $rendered);
    }

    /** Vérifie le sujet de l'email magic link superadmin, emoji de marque compris. */
    public function testSuperAdminMagicLinkMailHasTranslatedSubject(): void
    {
        $mail = new SuperAdminMagicLinkMail(self::URL_WITH_SPECIAL_CHARACTERS);

        $this->assertSame(
            SuperAdminMagicLinkMail::BRAND_EMOJI.' '.__('messages.email_superadmin_subject'),
            $mail->envelope()->subject,
        );
    }

    /** Vérifie que l'email superadmin rend un lien cliquable échappé, le bouton et le badge Super Admin. */
    public function testSuperAdminMagicLinkMailRendersEscapedClickableUrlAndBadge(): void
    {
        $mail = new SuperAdminMagicLinkMail(self::URL_WITH_SPECIAL_CHARACTERS);

        $rendered = $mail->render();

        $this->assertStringContainsString('href="'.self::ESCAPED_URL.'"', $rendered);
        $this->assertStringNotContainsString('<b>', $rendered);
        $this->assertStringContainsString(e(__('messages.email_superadmin_button')), $rendered);
        $this->assertStringContainsString(e(__('messages.superadmin_title')), $rendered);
    }
}
