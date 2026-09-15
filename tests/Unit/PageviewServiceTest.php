<?php

namespace Tests\Unit;

use App\Services\PageviewService;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PageviewServiceTest extends TestCase
{
    private const HUMAN_UA = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120';

    private PageviewService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PageviewService();
    }

    /** Vérifie la détection des user-agents de bots. */
    public function testDetectsBotUserAgents(): void
    {
        $this->assertTrue($this->service->isBot('Googlebot/2.1'));
        $this->assertTrue($this->service->isBot('Mozilla/5.0 (compatible; bingbot/2.0)'));
        $this->assertTrue($this->service->isBot('python-requests/2.28'));
        $this->assertTrue($this->service->isBot('curl/7.88'));
        $this->assertTrue($this->service->isBot(''));
    }

    /** Vérifie la détection des bots qui ne contiennent pas "bot"/"crawl"/"spider". */
    public function testDetectsStealthBotUserAgents(): void
    {
        $this->assertTrue($this->service->isBot(
            'Mozilla/5.0 (compatible; GoogleDocs; apps-spreadsheets; +http://docs.google.com)'
        ));
        $this->assertTrue($this->service->isBot('Chrome Privacy Preserving Prefetch Proxy'));
        $this->assertTrue($this->service->isBot(
            'Mozilla/5.0 (l9scan/2.0; +https://leakix.net)'
        ));
        $this->assertTrue($this->service->isBot(
            'Hello from Palo Alto Networks, find out more about our scans in https://docs-cortex.paloaltonetworks.com/'
        ));
        $this->assertTrue($this->service->isBot(
            'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko; Google Web Preview) Chrome/141.0'
        ));
    }

    /** Vérifie l'identification des bots spécifiques. */
    public function testIdentifiesSpecificBots(): void
    {
        $this->assertSame('Google Docs', $this->service->identifyBot(
            'Mozilla/5.0 (compatible; GoogleDocs; apps-spreadsheets; +http://docs.google.com)'
        ));
        $this->assertSame('Google Images', $this->service->identifyBot('Googlebot-Image/1.0'));
        $this->assertSame('Google Other', $this->service->identifyBot(
            'Mozilla/5.0 AppleWebKit/537.36 (compatible; GoogleOther) Chrome/141'
        ));
        $this->assertSame('UptimeRobot', $this->service->identifyBot(
            'Mozilla/5.0+(compatible; UptimeRobot/2.0; http://www.uptimerobot.com/)'
        ));
        $this->assertSame('Linkup', $this->service->identifyBot(
            'LinkupBot/1.0 (LinkupBot for web indexing; https://linkup.so/bot; bot@linkup.so)'
        ));
        $this->assertSame('LeakIX', $this->service->identifyBot(
            'Mozilla/5.0 (l9scan/2.0; +https://leakix.net)'
        ));
        $this->assertSame('Chrome Prefetch', $this->service->identifyBot(
            'Chrome Privacy Preserving Prefetch Proxy'
        ));
    }

    /** Vérifie qu'un bot absent du catalogue est regroupé sous « Other ». */
    public function testIdentifiesUnknownBotAsOther(): void
    {
        $this->assertSame('Other', $this->service->identifyBot('AcmeCrawler/3.1 (+https://acme.test/crawler)'));
    }

    /** Vérifie la détection des user-agents humains. */
    public function testDetectsHumanUserAgents(): void
    {
        $this->assertFalse($this->service->isBot(self::HUMAN_UA));
        $this->assertFalse($this->service->isBot('Mozilla/5.0 (iPhone; CPU iPhone OS 17_0) Safari/605'));
        $this->assertFalse($this->service->isBot('Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) Firefox/120'));
    }

    /** @return array<string, array{string, string}> */
    public static function deviceUserAgents(): array
    {
        return [
            'iPad' => ['Mozilla/5.0 (iPad; CPU OS 17_0 like Mac OS X) Safari/604', 'tablet'],
            'tablette Android' => ['Mozilla/5.0 (Linux; Android 14; SM-X710 Tablet) Chrome/120', 'tablet'],
            'iPhone' => ['Mozilla/5.0 (iPhone; CPU iPhone OS 17_0) Safari/605', 'mobile'],
            'téléphone Android' => ['Mozilla/5.0 (Linux; Android 14; Pixel 8) Chrome/120 Mobile', 'mobile'],
            'poste Windows' => [self::HUMAN_UA, 'desktop'],
            'poste macOS' => ['Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) Firefox/120', 'desktop'],
        ];
    }

    /** Vérifie la classification tablette, mobile ou ordinateur selon le user-agent. */
    #[DataProvider('deviceUserAgents')]
    public function testClassifiesDeviceFromUserAgent(string $userAgent, string $expectedDevice): void
    {
        $this->assertSame($expectedDevice, $this->service->detectDevice($userAgent));
    }

    /** Vérifie que deux vues identiques incrémentent la même ligne de pageview. */
    public function testTrackIncrementsExistingPageview(): void
    {
        $this->travelTo('2026-09-15 10:45:00');

        $this->service->track('home', self::HUMAN_UA, 'fr-FR', -60, 'fr');
        $this->service->track('home', self::HUMAN_UA, 'fr-FR', -60, 'fr');

        $this->assertDatabaseCount('stats_pageviews', 1);
        $this->assertDatabaseHas('stats_pageviews', [
            'date' => '2026-09-15',
            'page' => 'home',
            'is_bot' => false,
            'hour' => 10,
            'country' => 'FR',
            'locale' => 'fr',
            'count' => 2,
        ]);
        $this->assertDatabaseHas('stats_devices', ['device_type' => 'desktop', 'count' => 2]);
    }

    /** Vérifie qu'un bot incrémente stats_bots par nom et ne crée ni device ni heure locale. */
    public function testTrackBotUpsertsBotStatsWithoutDeviceOrLocalHour(): void
    {
        $this->travelTo('2026-09-15 10:45:00');

        $this->service->track('home', 'Googlebot/2.1', 'en-US', 0);
        $this->service->track('home', 'Googlebot/2.1', 'en-US', 0);

        $this->assertDatabaseCount('stats_bots', 1);
        $this->assertDatabaseHas('stats_bots', ['date' => '2026-09-15', 'bot_name' => 'Googlebot', 'count' => 2]);
        $this->assertDatabaseHas('stats_pageviews', ['page' => 'home', 'is_bot' => true, 'count' => 2]);
        $this->assertDatabaseCount('stats_devices', 0);
        $this->assertDatabaseCount('stats_local_hours', 0);
    }

    /** @return array<string, array{string, int, int}> */
    public static function timezoneOffsets(): array
    {
        return [
            'UTC' => ['2026-09-15 10:45:00', 0, 10],
            'UTC+2 (Paris, heure d\'été)' => ['2026-09-15 10:45:00', -120, 12],
            'UTC-5 avec passage au jour précédent' => ['2026-09-15 03:00:00', 300, 22],
            'UTC+5:30 (Inde)' => ['2026-09-15 10:45:00', -330, 16],
            'UTC-9:30 (Marquises)' => ['2026-09-15 10:15:00', 570, 0],
            'UTC+14, borne haute réaliste' => ['2026-09-15 10:45:00', -840, 0],
            'UTC-12, borne basse réaliste' => ['2026-09-15 10:45:00', 720, 22],
            'décalage positif hors bornes ignoré' => ['2026-09-15 10:45:00', 100000, 10],
            'décalage négatif hors bornes ignoré' => ['2026-09-15 10:45:00', -100000, 10],
        ];
    }

    /** Vérifie que l'heure locale tient compte des minutes du décalage et ignore les décalages irréalistes. */
    #[DataProvider('timezoneOffsets')]
    public function testTrackRecordsLocalHourFromTimezoneOffset(string $utcNow, int $tzOffset, int $expectedLocalHour): void
    {
        $this->travelTo($utcNow);

        $this->service->track('home', self::HUMAN_UA, 'en-US', $tzOffset);

        $this->assertDatabaseCount('stats_local_hours', 1);
        $this->assertDatabaseHas('stats_local_hours', ['local_hour' => $expectedLocalHour, 'count' => 1]);
    }

    /** Vérifie la détection des apps IA. */
    public function testDetectsAiApps(): void
    {
        $this->assertSame('(chatgpt-app)', $this->service->detectAiApp(
            'ChatGPT/1.2025.287 (iOS 18.6.2; iPhone17,1; build 18608390057)'
        ));
        $this->assertSame('(chatgpt-app)', $this->service->detectAiApp(
            'ChatGPT/1.2025.258 (Windows_NT 10.0.26200; x86_64) Electron/37.4.0'
        ));
        $this->assertSame('(perplexity-app)', $this->service->detectAiApp('Perplexity/1.0 (iOS 18)'));
        $this->assertSame('(claude-app)', $this->service->detectAiApp('Claude/1.0 (iOS)'));
        $this->assertNull($this->service->detectAiApp(self::HUMAN_UA));
    }

    /** Vérifie que les apps IA sont taguées comme pseudo-referrer, même avec un referer réel. */
    public function testTrackTagsAiAppAsReferrer(): void
    {
        $this->service->track(
            'home',
            'ChatGPT/1.2025.287 (iOS 18.6.2; iPhone17,1; build 18608390057)',
            'en-US',
            0,
            '',
            'https://news.example.org/article'
        );

        $this->assertDatabaseCount('stats_referrers', 1);
        $this->assertDatabaseHas('stats_referrers', [
            'referrer_domain' => '(chatgpt-app)',
            'is_bot' => false,
        ]);
    }

    /** Vérifie que les apps IA restent comptées comme humains côté device. */
    public function testTrackAiAppIsNotBot(): void
    {
        $ua = 'ChatGPT/1.2025.287 (iOS 18.6.2; iPhone17,1; build 18608390057)';

        $this->service->track('home', $ua, 'en-US', 0);

        $this->assertDatabaseHas('stats_pageviews', [
            'page' => 'home',
            'is_bot' => false,
        ]);
        $this->assertDatabaseHas('stats_devices', [
            'device_type' => 'mobile',
        ]);
    }

    /** @return array<string, array{string, string}> */
    public static function referrers(): array
    {
        return [
            'accès direct' => ['', '(direct)'],
            'domaine externe' => ['https://news.ycombinator.com/item?id=1', 'news.ycombinator.com'],
            'préfixe www retiré et casse normalisée' => ['https://WWW.Google.com/search?q=secret', 'google.com'],
            'URL sans hôte' => ['/relative/path', '(direct)'],
            'URL mal formée rejetée par parse_url' => ['http:///broken', '(direct)'],
            'hôte tronqué à 100 caractères' => ['https://'.str_repeat('a', 120).'.example/', str_repeat('a', 100)],
        ];
    }

    /** Vérifie l'extraction du domaine référent enregistré. */
    #[DataProvider('referrers')]
    public function testTrackRecordsReferrerDomain(string $referrer, string $expectedDomain): void
    {
        $this->service->track('home', self::HUMAN_UA, 'en-US', 0, 'en', $referrer);

        $this->assertDatabaseCount('stats_referrers', 1);
        $this->assertDatabaseHas('stats_referrers', ['referrer_domain' => $expectedDomain, 'is_bot' => false]);
    }

    /** Vérifie qu'un referer issu du domaine de l'application compte comme accès direct. */
    public function testTrackTreatsOwnDomainReferrerAsDirect(): void
    {
        config(['app.url' => 'https://secret.example']);

        $this->service->track('home', self::HUMAN_UA, 'en-US', 0, 'en', 'https://www.secret.example/fr/faq');

        $this->assertDatabaseHas('stats_referrers', ['referrer_domain' => '(direct)']);
    }

    /** Vérifie qu'en local un referer 127.0.0.1 compte comme accès direct quand l'app tourne sur localhost. */
    public function testTrackTreatsLoopbackReferrerAsDirectForLocalApp(): void
    {
        config(['app.url' => 'http://localhost']);

        $this->service->track('home', self::HUMAN_UA, 'en-US', 0, 'en', 'http://127.0.0.1:8000/fr');

        $this->assertDatabaseHas('stats_referrers', ['referrer_domain' => '(direct)']);
    }

    /** @return array<string, array{string, string}> */
    public static function acceptLanguages(): array
    {
        return [
            'région en majuscules' => ['fr-FR', 'FR'],
            'région en minuscules' => ['fr-fr', 'FR'],
            'langue seule' => ['ja', 'JP'],
            'langue seule en majuscules' => ['JA', 'JP'],
            'région numérique ONU repliée sur la langue' => ['es-419', 'ES'],
            'script puis région' => ['zh-Hant-TW', 'TW'],
            'script latin puis région' => ['sr-Latn-RS', 'RS'],
            'script sans région, langue connue' => ['zh-Hans', 'CN'],
            'script sans région, langue inconnue' => ['sr-Latn', 'XX'],
            'langue de trois lettres inconnue' => ['fil', 'XX'],
            'liste avec qualité' => ['de-DE,de;q=0.9', 'DE'],
            'qualité sur la première langue' => ['pt-BR;q=0.8, en;q=0.5', 'BR'],
            'espaces autour des valeurs' => ['  it-IT , en', 'IT'],
            'joker' => ['*', 'XX'],
            'en-tête vide' => ['', 'XX'],
        ];
    }

    /** Vérifie que le pays enregistré est toujours un code de deux lettres déduit d'Accept-Language. */
    #[DataProvider('acceptLanguages')]
    public function testTrackRecordsTwoLetterCountryFromAcceptLanguage(string $acceptLanguage, string $expectedCountry): void
    {
        $this->service->track('home', self::HUMAN_UA, $acceptLanguage, 0);

        $this->assertDatabaseCount('stats_pageviews', 1);
        $this->assertDatabaseHas('stats_pageviews', ['page' => 'home', 'country' => $expectedCountry]);
    }
}
