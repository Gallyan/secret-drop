<?php

namespace Tests\Feature\Middleware;

use App\Http\Middleware\SecurityHeaders;
use Illuminate\Http\Request;
use Illuminate\Support\Once;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    private SecurityHeaders $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new SecurityHeaders();
    }

    /** @return array<string, array{string, string}> */
    public static function hardeningHeaders(): array
    {
        return [
            'X-Content-Type-Options' => ['X-Content-Type-Options', 'nosniff'],
            'X-Frame-Options' => ['X-Frame-Options', 'DENY'],
            'Referrer-Policy' => ['Referrer-Policy', 'strict-origin-when-cross-origin'],
            'Permissions-Policy' => ['Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=(), usb=()'],
            'Cross-Origin-Opener-Policy' => ['Cross-Origin-Opener-Policy', 'same-origin'],
            'Cross-Origin-Resource-Policy' => ['Cross-Origin-Resource-Policy', 'same-origin'],
            'X-Permitted-Cross-Domain-Policies' => ['X-Permitted-Cross-Domain-Policies', 'none'],
        ];
    }

    /** Vérifie la valeur de chaque en-tête de durcissement posé dans tous les environnements. */
    #[DataProvider('hardeningHeaders')]
    public function testSetsHardeningHeader(string $header, string $expectedValue): void
    {
        $response = $this->handle();

        $this->assertSame($expectedValue, $response->headers->get($header));
    }

    /** Vérifie l'absence du header obsolète X-XSS-Protection. */
    public function testDoesNotSetObsoleteXXssProtection(): void
    {
        $this->assertNull($this->handle()->headers->get('X-XSS-Protection'));
    }

    /** Vérifie que le CSP de production est strict : nonce seul pour scripts et styles, aucune source externe. */
    public function testCspIsStrictInProduction(): void
    {
        $this->app->detectEnvironment(fn () => 'production');
        $nonce = csp_nonce();

        $directives = $this->cspDirectives($this->handle());

        $this->assertSame([
            'default-src' => "'self'",
            'script-src' => "'self' 'nonce-{$nonce}'",
            'style-src' => "'self' 'nonce-{$nonce}'",
            'style-src-attr' => "'unsafe-inline'",
            'img-src' => "'self' data:",
            'font-src' => "'self'",
            'connect-src' => "'self'",
            'frame-ancestors' => "'none'",
            'form-action' => "'self'",
            'base-uri' => "'self'",
            'object-src' => "'none'",
        ], $directives);
    }

    /** Vérifie qu'en local le CSP ouvre unsafe-eval aux scripts, unsafe-inline aux styles et le websocket Vite. */
    public function testCspRelaxesScriptStyleAndConnectInLocal(): void
    {
        $this->app->detectEnvironment(fn () => 'local');
        $nonce = csp_nonce();

        $directives = $this->cspDirectives($this->handle());

        $this->assertSame("'self' 'nonce-{$nonce}' 'unsafe-eval'", $directives['script-src']);
        $this->assertSame("'self' 'nonce-{$nonce}' 'unsafe-inline'", $directives['style-src']);
        $this->assertSame("'self' ws://localhost:* http://localhost:*", $directives['connect-src']);
    }

    /** Vérifie que le HSTS de production couvre les sous-domaines et demande le preload. */
    public function testSetsHstsWithPreloadInProduction(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        $response = $this->handle();

        $this->assertSame('max-age=31536000; includeSubDomains; preload', $response->headers->get('Strict-Transport-Security'));
    }

    /** Vérifie l'absence du HSTS en local. */
    public function testDoesNotSetHstsInLocal(): void
    {
        $this->app->detectEnvironment(fn () => 'local');

        $this->assertNull($this->handle()->headers->get('Strict-Transport-Security'));
    }

    /** Vérifie la présence du header Cross-Origin-Embedder-Policy en production. */
    public function testSetsCoepHeaderInProduction(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        $this->assertSame('require-corp', $this->handle()->headers->get('Cross-Origin-Embedder-Policy'));
    }

    /** Vérifie l'absence du COEP en local (scripts Vite cross-origin). */
    public function testDoesNotSetCoepHeaderInLocal(): void
    {
        $this->app->detectEnvironment(fn () => 'local');

        $this->assertNull($this->handle()->headers->get('Cross-Origin-Embedder-Policy'));
    }

    /** @return array<string, array{string}> */
    public static function sitemapUris(): array
    {
        return [
            'sitemap.xml' => ['/sitemap.xml'],
            'sitemap.xsl' => ['/sitemap.xsl'],
        ];
    }

    /** Vérifie que le sitemap et sa feuille XSL sont servis sans CSP mais avec les autres en-têtes. */
    #[DataProvider('sitemapUris')]
    public function testOmitsCspOnSitemapFiles(string $uri): void
    {
        $response = $this->get($uri);

        $response->assertOk();
        $response->assertHeaderMissing('Content-Security-Policy');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    /** Vérifie que chaque balise nonce du HTML rendu porte le nonce annoncé dans l'en-tête CSP. */
    public function testRenderedPageNoncesMatchTheCspHeader(): void
    {
        $response = $this->get('/fr');

        $response->assertOk();
        $headerNonce = $this->headerNonce($response);
        preg_match_all('/<(script|style|link)\b[^>]*\bnonce="([^"]*)"/', (string) $response->getContent(), $tags);
        $this->assertContains('script', $tags[1]);
        $this->assertContains('style', $tags[1]);
        $this->assertSame(array_fill(0, count($tags[2]), $headerNonce), $tags[2]);
    }

    /**
     * Vérifie que le nonce change d'une requête à l'autre.
     *
     * csp_nonce() est mémorisé via once() pour la durée du processus PHP, qui ne sert qu'une
     * requête sous PHP-FPM : Once::flush() reproduit cette frontière entre les deux appels.
     */
    public function testNonceDiffersBetweenRequests(): void
    {
        $firstNonce = $this->headerNonce($this->get('/fr'));
        Once::flush();

        $secondNonce = $this->headerNonce($this->get('/fr'));

        $this->assertNotSame($firstNonce, $secondNonce);
    }

    private function handle(): Response
    {
        return $this->middleware->handle(Request::create('/test'), fn () => response('OK'));
    }

    /** @return array<string, string> */
    private function cspDirectives(Response $response): array
    {
        $csp = $response->headers->get('Content-Security-Policy');
        $this->assertIsString($csp);

        $directives = [];

        foreach (explode('; ', $csp) as $directive) {
            [$name, $sources] = explode(' ', $directive, 2);
            $directives[$name] = $sources;
        }

        return $directives;
    }

    /** @param TestResponse<Response> $response */
    private function headerNonce(TestResponse $response): string
    {
        $csp = $response->headers->get('Content-Security-Policy');
        $this->assertIsString($csp);
        $this->assertSame(1, preg_match("/script-src 'self' 'nonce-([A-Za-z0-9]+)'/", $csp, $matches));

        return $matches[1];
    }
}
