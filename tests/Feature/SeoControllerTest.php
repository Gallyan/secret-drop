<?php

namespace Tests\Feature;

use DOMDocument;
use DOMElement;
use DOMXPath;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SeoControllerTest extends TestCase
{
    private const LOCALES = ['en', 'fr', 'de', 'es', 'it', 'pt', 'nl', 'pl', 'ja', 'ko', 'ar'];

    /** Vérifie que le groupe User-agent: * de robots.txt interdit les secrets, l'admin de chaque locale (avec et sans slash final), l'API et le contact. */
    public function testRobotsTxtHidesPrivateAreasFromGenericCrawlers(): void
    {
        $response = $this->get('/robots.txt');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
        $this->assertSame([
            'Disallow: /s/',
            'Disallow: /en/admin',
            'Disallow: /fr/admin',
            'Disallow: /de/admin',
            'Disallow: /es/admin',
            'Disallow: /it/admin',
            'Disallow: /pt/admin',
            'Disallow: /nl/admin',
            'Disallow: /pl/admin',
            'Disallow: /ja/admin',
            'Disallow: /ko/admin',
            'Disallow: /ar/admin',
            'Disallow: /api/',
            'Disallow: /contact',
        ], $this->robotsGroups($response->getContent())['*']);
    }

    /** Vérifie que GPTBot est autorisé hors secrets et API. */
    public function testRobotsTxtAllowsGptBotOutsideSecretsAndApi(): void
    {
        $groups = $this->robotsGroups($this->get('/robots.txt')->getContent());

        $this->assertSame(['Allow: /', 'Disallow: /s/', 'Disallow: /api/'], $groups['GPTBot']);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function allowedCrawlers(): array
    {
        return collect([
            'ChatGPT-User', 'Google-Extended', 'anthropic-ai', 'PerplexityBot', 'ClaudeBot', 'OAI-SearchBot',
            'bingbot', 'GoogleOther', 'Applebot-Extended', 'Amazonbot', 'FacebookBot',
        ])->mapWithKeys(fn (string $agent): array => [$agent => [$agent]])->all();
    }

    /** Vérifie que les robots de recherche et d'IA listés sont explicitement autorisés sur tout le site. */
    #[DataProvider('allowedCrawlers')]
    public function testRobotsTxtAllowsSearchAndAiCrawler(string $agent): void
    {
        $groups = $this->robotsGroups($this->get('/robots.txt')->getContent());

        $this->assertSame(['Allow: /'], $groups[$agent]);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function blockedCrawlers(): array
    {
        return [
            'Bytespider' => ['Bytespider'],
            'CCBot' => ['CCBot'],
        ];
    }

    /** Vérifie que Bytespider et CCBot sont bloqués sur tout le site. */
    #[DataProvider('blockedCrawlers')]
    public function testRobotsTxtBlocksCrawler(string $agent): void
    {
        $groups = $this->robotsGroups($this->get('/robots.txt')->getContent());

        $this->assertSame(['Disallow: /'], $groups[$agent]);
    }

    /** Vérifie que robots.txt déclare l'URL absolue du sitemap. */
    public function testRobotsTxtDeclaresTheSitemap(): void
    {
        $content = $this->get('/robots.txt')->getContent();

        $this->assertStringEndsWith("\nSitemap: http://localhost/sitemap.xml", $content);
    }

    /** Vérifie que robots.txt, indépendant de la langue, n'annonce pas Vary: Accept-Language même quand l'en-tête est envoyé. */
    public function testRobotsTxtDoesNotVaryOnAcceptLanguage(): void
    {
        $response = $this->withHeader('Accept-Language', 'en')->get('/robots.txt');

        $response->assertOk();
        $response->assertHeaderMissing('Vary');
    }

    /** Vérifie que robots.txt n'expose pas l'existence de la route superadmin. */
    public function testRobotsDoesNotExposeSuperadmin(): void
    {
        $response = $this->get('/robots.txt');

        $this->assertStringNotContainsString('superadmin', $response->getContent());
    }

    /** Vérifie que le sitemap XML liste l'accueil puis chaque page traduisible dans chaque locale, avec un lastmod à l'instant de la requête en UTC. */
    public function testSitemapListsEveryPublicUrlWithUtcLastmod(): void
    {
        $this->travelTo('2026-09-15T10:20:30+02:00');

        $response = $this->get('/sitemap.xml');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/xml');
        $urls = $this->sitemapUrls($response->getContent());
        $this->assertCount(55, $urls);
        $this->assertSame('http://localhost/en', $urls[0]['loc']);
        $this->assertSame('http://localhost/ar/faq', $urls[54]['loc']);

        foreach ($urls as $url) {
            $this->assertSame('2026-09-15T08:20:30Z', $url['lastmod'], $url['loc']);
        }
    }

    /** Vérifie que chaque URL du sitemap déclare 11 alternates de locale puis x-default, dans cet ordre. */
    public function testSitemapDeclaresElevenLocaleAlternatesAndXDefaultPerUrl(): void
    {
        $urls = $this->sitemapUrls($this->get('/sitemap.xml')->getContent());

        foreach ($urls as $url) {
            $this->assertSame([...self::LOCALES, 'x-default'], array_keys($url['alternates']), $url['loc']);
        }
    }

    /** Vérifie les alternates et la priorité 1.0 d'une page d'accueil, x-default pointant vers le français. */
    public function testSitemapHomeEntryHasTopPriorityAndFrenchXDefault(): void
    {
        $urls = $this->sitemapUrls($this->get('/sitemap.xml')->getContent());

        $home = $this->findByLoc($urls, 'http://localhost/de');

        $this->assertSame('1.0', $home['priority']);
        $this->assertSame('http://localhost/ja', $home['alternates']['ja']);
        $this->assertSame('http://localhost/fr', $home['alternates']['x-default']);
    }

    /** Vérifie les alternates traduits et la priorité 0.8 d'une page interne, x-default pointant vers le slug français. */
    public function testSitemapInnerPageEntryHasTranslatedAlternatesAndFrenchXDefault(): void
    {
        $urls = $this->sitemapUrls($this->get('/sitemap.xml')->getContent());

        $page = $this->findByLoc($urls, 'http://localhost/de/so-funktioniert-es');

        $this->assertSame('0.8', $page['priority']);
        $this->assertSame([
            'en' => 'http://localhost/en/how-it-works',
            'fr' => 'http://localhost/fr/comment-ca-marche',
            'de' => 'http://localhost/de/so-funktioniert-es',
            'es' => 'http://localhost/es/como-funciona',
            'it' => 'http://localhost/it/come-funziona',
            'pt' => 'http://localhost/pt/como-funciona',
            'nl' => 'http://localhost/nl/hoe-het-werkt',
            'pl' => 'http://localhost/pl/jak-to-dziala',
            'ja' => 'http://localhost/ja/how-it-works',
            'ko' => 'http://localhost/ko/how-it-works',
            'ar' => 'http://localhost/ar/how-it-works',
            'x-default' => 'http://localhost/fr/comment-ca-marche',
        ], $page['alternates']);
    }

    /** Vérifie que la feuille de style du sitemap est servie en XSL. */
    public function testSitemapStylesheetReturnsXsl(): void
    {
        $response = $this->get('/sitemap.xsl');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/xsl; charset=UTF-8');
    }

    /**
     * Rules of each robots.txt group, keyed by user agent.
     *
     * @return array<string, array<int, string>>
     */
    private function robotsGroups(string $content): array
    {
        $groups = [];

        foreach (preg_split('/\n\s*\n/', trim($content)) ?: [] as $block) {
            $lines = array_map('trim', explode("\n", trim($block)));
            $first = array_shift($lines);

            if (str_starts_with($first, 'User-agent: ')) {
                $groups[substr($first, strlen('User-agent: '))] = $lines;
            }
        }

        return $groups;
    }

    /**
     * @return array<int, array{loc: string, lastmod: string, priority: string, alternates: array<string, string>}>
     */
    private function sitemapUrls(string $xml): array
    {
        $document = new DOMDocument();
        $document->loadXML($xml);
        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('s', 'http://www.sitemaps.org/schemas/sitemap/0.9');
        $xpath->registerNamespace('xhtml', 'http://www.w3.org/1999/xhtml');
        $urls = [];

        foreach ($xpath->query('/s:urlset/s:url') ?: [] as $url) {
            $alternates = [];

            foreach ($xpath->query('xhtml:link[@rel="alternate"]', $url) ?: [] as $link) {
                $this->assertInstanceOf(DOMElement::class, $link);
                $alternates[$link->getAttribute('hreflang')] = $link->getAttribute('href');
            }

            $urls[] = [
                'loc' => $xpath->evaluate('string(s:loc)', $url),
                'lastmod' => $xpath->evaluate('string(s:lastmod)', $url),
                'priority' => $xpath->evaluate('string(s:priority)', $url),
                'alternates' => $alternates,
            ];
        }

        return $urls;
    }

    /**
     * @param  array<int, array{loc: string, lastmod: string, priority: string, alternates: array<string, string>}>  $urls
     * @return array{loc: string, lastmod: string, priority: string, alternates: array<string, string>}
     */
    private function findByLoc(array $urls, string $loc): array
    {
        $found = collect($urls)->firstWhere('loc', $loc);

        $this->assertIsArray($found, "Aucune entrée {$loc} dans le sitemap");

        return $found;
    }
}
