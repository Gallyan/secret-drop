<?php

namespace Tests\Feature;

use Tests\TestCase;

class IndexNowKeyRouteTest extends TestCase
{
    private const KEY = 'test-indexnow-key-0123456789abcdef';

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.indexnow.key' => self::KEY]);
    }

    /** Vérifie que le fichier de vérification retourne la clé en texte brut. */
    public function testKeyFileReturnsTheConfiguredKey(): void
    {
        $response = $this->get('/'.self::KEY.'.txt');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
        $this->assertSame(self::KEY, $response->getContent());
    }

    /** Vérifie qu'une clé différente de celle configurée retourne 404. */
    public function testWrongKeyReturnsNotFound(): void
    {
        $response = $this->get('/wrong-indexnow-key-000000000.txt');

        $response->assertStatus(404);
    }

    /** Vérifie que la comparaison de clé est sensible à la casse. */
    public function testKeyComparisonIsCaseSensitive(): void
    {
        $response = $this->get('/'.strtoupper(self::KEY).'.txt');

        $response->assertStatus(404);
    }

    /** Vérifie qu'aucune clé configurée donne un 404 sans erreur PHP (hash_equals sur null). */
    public function testMissingConfiguredKeyReturnsNotFound(): void
    {
        config(['services.indexnow.key' => null]);

        $response = $this->get('/'.self::KEY.'.txt');

        $response->assertStatus(404);
    }

    /** Vérifie qu'une clé configurée vide donne un 404. */
    public function testEmptyConfiguredKeyReturnsNotFound(): void
    {
        config(['services.indexnow.key' => '']);

        $response = $this->get('/'.self::KEY.'.txt');

        $response->assertStatus(404);
    }

    /** Vérifie qu'une clé plus courte que 8 caractères n'atteint pas la route. */
    public function testTooShortKeyReturnsNotFound(): void
    {
        config(['services.indexnow.key' => 'abc']);

        $response = $this->get('/abc.txt');

        $response->assertStatus(404);
    }

    /** Vérifie qu'une clé plus longue que 128 caractères n'atteint pas la route. */
    public function testTooLongKeyReturnsNotFound(): void
    {
        $tooLong = str_repeat('a', 129);
        config(['services.indexnow.key' => $tooLong]);

        $response = $this->get('/'.$tooLong.'.txt');

        $response->assertStatus(404);
    }

    /** Vérifie qu'une clé de 128 caractères reste servie. */
    public function testMaximumLengthKeyIsServed(): void
    {
        $maxLength = str_repeat('a', 128);
        config(['services.indexnow.key' => $maxLength]);

        $response = $this->get('/'.$maxLength.'.txt');

        $response->assertOk();
        $this->assertSame($maxLength, $response->getContent());
    }

    /** Vérifie que robots.txt n'est pas capturé par la route de clé. */
    public function testRobotsTxtIsNotCapturedByTheKeyRoute(): void
    {
        config(['services.indexnow.key' => 'robots']);

        $response = $this->get('/robots.txt');

        $response->assertOk();
        $content = $response->getContent();
        $this->assertStringContainsString('User-agent: *', $content);
        $this->assertStringContainsString('Disallow: /s/', $content);
        $this->assertStringContainsString('Sitemap:', $content);
    }

    /** Vérifie que llms.txt n'est pas capturé par la route de clé. */
    public function testLlmsTxtIsNotCapturedByTheKeyRoute(): void
    {
        config(['services.indexnow.key' => 'llms']);

        $response = $this->get('/llms.txt');

        $response->assertOk();
        $this->assertStringContainsString('# Secret Drop', $response->getContent());
    }

    /** Vérifie que llms-full.txt n'est pas capturé par la route de clé (il matche pourtant la regex). */
    public function testLlmsFullTxtIsNotCapturedByTheKeyRoute(): void
    {
        config(['services.indexnow.key' => 'llms-full']);

        $response = $this->get('/llms-full.txt');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/plain; charset=utf-8');
        $content = $response->getContent();
        $this->assertNotSame('llms-full', $content);
        $this->assertStringContainsString('Secret Drop', $content);
    }

    /** Vérifie que le sitemap n'est pas capturé par la route de clé. */
    public function testSitemapIsNotCapturedByTheKeyRoute(): void
    {
        $response = $this->get('/sitemap.xml');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/xml');
        $this->assertStringContainsString('<urlset', $response->getContent());
    }

    /** Vérifie que security.txt n'est pas capturé par la route de clé. */
    public function testSecurityTxtIsNotCapturedByTheKeyRoute(): void
    {
        $response = $this->get('/.well-known/security.txt');

        $response->assertOk();
        $this->assertStringContainsString('Contact: mailto:', $response->getContent());
    }
}
