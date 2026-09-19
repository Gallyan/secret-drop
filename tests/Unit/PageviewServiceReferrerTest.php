<?php

namespace Tests\Unit;

use App\Services\PageviewService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Tests\TestCase;

class PageviewServiceReferrerTest extends TestCase
{
    private const HUMAN_UA = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120';

    private PageviewService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PageviewService();
    }

    /** @return array<string, array{string}> */
    public static function invalidHosts(): array
    {
        return [
            'caractères interdits encodés' => ['https://evil%3Cscript%3E.example/'],
            'soulignés' => ['https://bad_host.example/'],
            'IPv6 externe' => ['https://[2001:db8::1]/'],
            'hôte de plus de 253 caractères' => ['https://'.str_repeat('abcdefghi.', 26).'example/'],
            'label vide' => ['https://foo..example/'],
        ];
    }

    /** Vérifie qu'un referer dont l'hôte n'est pas un nom d'hôte valide n'est pas comptabilisé. */
    #[DataProvider('invalidHosts')]
    public function testInvalidReferrerHostIsNotTracked(string $referrer): void
    {
        $this->service->track('home', self::HUMAN_UA, 'en-US', 0, 'en', $referrer);

        $this->assertDatabaseCount('stats_referrers', 0);
        $this->assertDatabaseCount('stats_pageviews', 1);
    }

    /** Vérifie qu'un nom de domaine internationalisé est enregistré sous sa forme punycode. */
    #[RequiresPhpExtension('intl')]
    public function testInternationalizedReferrerIsStoredAsPunycode(): void
    {
        $this->service->track('home', self::HUMAN_UA, 'en-US', 0, 'en', 'https://www.Bücher.example/page');

        $this->assertDatabaseHas('stats_referrers', ['referrer_domain' => 'xn--bcher-kva.example']);
    }

    /** Vérifie qu'un point final est ignoré pour ne pas dupliquer le domaine. */
    public function testTrailingDotIsIgnored(): void
    {
        $this->service->track('home', self::HUMAN_UA, 'en-US', 0, 'en', 'https://google.com./search');

        $this->assertDatabaseHas('stats_referrers', ['referrer_domain' => 'google.com']);
    }

    /** Vérifie qu'une application IA reste comptabilisée même sans referer valide. */
    public function testAiAppIsTrackedWhateverTheReferrer(): void
    {
        $this->service->track('home', 'ChatGPT/1.2025 (iOS)', 'en-US', 0, 'en', 'https://bad_host.example/');

        $this->assertDatabaseHas('stats_referrers', ['referrer_domain' => '(chatgpt-app)']);
    }
}
