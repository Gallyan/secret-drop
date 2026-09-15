<?php

namespace Tests\Feature;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SubmitIndexNowUrlsCommandTest extends TestCase
{
    private const KEY = 'test-indexnow-key-0123456789abcdef';

    private const ENDPOINT = 'https://indexnow.test/indexnow';

    private const PUBLIC_URLS = [
        'http://localhost/en',
        'http://localhost/fr',
        'http://localhost/de',
        'http://localhost/es',
        'http://localhost/it',
        'http://localhost/pt',
        'http://localhost/nl',
        'http://localhost/pl',
        'http://localhost/ja',
        'http://localhost/ko',
        'http://localhost/ar',
        'http://localhost/en/how-it-works',
        'http://localhost/fr/comment-ca-marche',
        'http://localhost/de/so-funktioniert-es',
        'http://localhost/es/como-funciona',
        'http://localhost/it/come-funziona',
        'http://localhost/pt/como-funciona',
        'http://localhost/nl/hoe-het-werkt',
        'http://localhost/pl/jak-to-dziala',
        'http://localhost/ja/how-it-works',
        'http://localhost/ko/how-it-works',
        'http://localhost/ar/how-it-works',
        'http://localhost/en/use-cases',
        'http://localhost/fr/cas-d-usage',
        'http://localhost/de/anwendungsfaelle',
        'http://localhost/es/casos-de-uso',
        'http://localhost/it/casi-d-uso',
        'http://localhost/pt/casos-de-uso',
        'http://localhost/nl/gebruikssituaties',
        'http://localhost/pl/przypadki-uzycia',
        'http://localhost/ja/use-cases',
        'http://localhost/ko/use-cases',
        'http://localhost/ar/use-cases',
        'http://localhost/en/legal-notice',
        'http://localhost/fr/mentions-legales',
        'http://localhost/de/impressum',
        'http://localhost/es/aviso-legal',
        'http://localhost/it/avviso-legale',
        'http://localhost/pt/aviso-legal',
        'http://localhost/nl/juridische-kennisgeving',
        'http://localhost/pl/oswiadczenie-prawne',
        'http://localhost/ja/legal-notice',
        'http://localhost/ko/legal-notice',
        'http://localhost/ar/legal-notice',
        'http://localhost/en/faq',
        'http://localhost/fr/faq',
        'http://localhost/de/faq',
        'http://localhost/es/preguntas-frecuentes',
        'http://localhost/it/faq',
        'http://localhost/pt/perguntas-frequentes',
        'http://localhost/nl/faq',
        'http://localhost/pl/faq',
        'http://localhost/ja/faq',
        'http://localhost/ko/faq',
        'http://localhost/ar/faq',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
        config([
            'services.indexnow.key' => self::KEY,
            'services.indexnow.endpoint' => self::ENDPOINT,
        ]);
    }

    /** Vérifie qu'une clé absente fait échouer la commande sans émettre de requête. */
    public function testCommandFailsWithoutConfiguredKey(): void
    {
        Http::fake([self::ENDPOINT => Http::response('', 200)]);
        config(['services.indexnow.key' => null]);

        $this->artisan('indexnow:submit')
            ->expectsOutputToContain('IndexNow key is not configured')
            ->assertExitCode(1);

        Http::assertNothingSent();
    }

    /**
     * @return array<string, array{string}>
     */
    public static function malformedKeys(): array
    {
        return [
            'seven characters' => ['abc1234'],
            '129 characters' => [str_repeat('a', 129)],
            'underscore' => ['abcd_efgh'],
            'dot' => ['abcd.efgh'],
            'space' => ['abcd efgh'],
            'slash' => ['abcd/efgh'],
            'non ascii letter' => ['clé-indexnow'],
        ];
    }

    /** Vérifie qu'une clé hors format IndexNow fait échouer la commande sans requête, puisque son fichier de vérification répondrait 404. */
    #[DataProvider('malformedKeys')]
    public function testCommandFailsWithMalformedKey(string $key): void
    {
        Http::fake([self::ENDPOINT => Http::response('', 200)]);
        config(['services.indexnow.key' => $key]);

        $this->artisan('indexnow:submit')
            ->expectsOutputToContain('IndexNow key must be 8 to 128 letters, digits or dashes')
            ->assertExitCode(1);

        Http::assertNothingSent();
    }

    /** Vérifie qu'une clé de 8 caractères, borne basse du format, est acceptée et publiée à la racine. */
    public function testShortestValidKeyIsSubmittedWithItsKeyLocation(): void
    {
        Http::fake([self::ENDPOINT => Http::response('', 200)]);
        config(['services.indexnow.key' => 'abcd1234']);

        $this->artisan('indexnow:submit')->assertExitCode(0);

        Http::assertSent(fn (Request $request): bool => $request['key'] === 'abcd1234'
            && $request['keyLocation'] === 'http://localhost/abcd1234.txt');
    }

    /** Vérifie qu'un APP_URL sans hôte fait échouer la commande sans requête. */
    public function testCommandFailsWhenAppUrlHasNoHost(): void
    {
        Http::fake([self::ENDPOINT => Http::response('', 200)]);
        config(['app.url' => 'localhost-without-scheme']);

        $this->artisan('indexnow:submit')
            ->expectsOutputToContain('Cannot determine the site host')
            ->assertExitCode(1);

        Http::assertNothingSent();
    }

    /**
     * Vérifie que la commande poste en une seule requête JSON les 55 URLs publiques, sans URL privée.
     *
     * Le Content-Type doit être unique, sans quoi l'API répond 415 : withHeaders() après asJson()
     * ferait un array_merge_recursive et doublerait l'en-tête, contentType() l'écrase.
     */
    public function testCommandPostsEveryPublicUrlInOneJsonRequest(): void
    {
        Http::fake([self::ENDPOINT => Http::response('', 200)]);

        $this->artisan('indexnow:submit')
            ->expectsOutputToContain('Submitting 55 URLs to IndexNow for host localhost.')
            ->expectsOutputToContain('IndexNow accepted 55 URLs (HTTP 200).')
            ->assertExitCode(0);

        Http::assertSentCount(1);
        Http::assertSent(function (Request $request): bool {
            $this->assertSame('POST', $request->method());
            $this->assertSame(self::ENDPOINT, $request->url());
            $this->assertSame(['application/json; charset=utf-8'], $request->header('Content-Type'));
            $this->assertSame([
                'host' => 'localhost',
                'key' => self::KEY,
                'keyLocation' => 'http://localhost/'.self::KEY.'.txt',
                'urlList' => self::PUBLIC_URLS,
            ], $request->data());

            return true;
        });
    }

    /** Vérifie que l'endpoint public d'IndexNow est utilisé quand la configuration de l'endpoint est nulle. */
    public function testCommandFallsBackToTheDefaultEndpointWhenConfigIsNull(): void
    {
        Http::fake(['https://api.indexnow.org/indexnow' => Http::response('', 200)]);
        config(['services.indexnow.endpoint' => null]);

        $this->artisan('indexnow:submit')->assertExitCode(0);

        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://api.indexnow.org/indexnow');
    }

    /** Vérifie que l'option --url restreint la soumission aux URLs fournies, en ignorant les valeurs vides. */
    public function testUrlOptionSubmitsOnlyTheGivenNonEmptyUrls(): void
    {
        Http::fake([self::ENDPOINT => Http::response('', 200)]);

        $this->artisan('indexnow:submit', ['--url' => ['http://localhost/fr', '', '   ', ' http://localhost/fr/faq ']])
            ->assertExitCode(0);

        Http::assertSent(fn (Request $request): bool => $request['urlList'] === ['http://localhost/fr', 'http://localhost/fr/faq']);
    }

    /** Vérifie que --url= sans valeur retombe sur la liste complète des URLs publiques (comportement actuel figé). */
    public function testEmptyUrlOptionFallsBackToEveryPublicUrl(): void
    {
        Http::fake([self::ENDPOINT => Http::response('', 200)]);

        $this->artisan('indexnow:submit', ['--url' => ['']])->assertExitCode(0);

        Http::assertSent(fn (Request $request): bool => $request['urlList'] === self::PUBLIC_URLS);
    }

    /** Vérifie que les messages parlent d'« 1 URL » au singulier quand une seule URL est soumise. */
    public function testSingleUrlIsLabelledInTheSingular(): void
    {
        Http::fake([self::ENDPOINT => Http::response('', 200)]);

        $this->artisan('indexnow:submit', ['--url' => ['http://localhost/fr']])
            ->expectsOutputToContain('Submitting 1 URL to IndexNow for host localhost.')
            ->expectsOutputToContain('IndexNow accepted 1 URL (HTTP 200).')
            ->doesntExpectOutputToContain('1 URLs')
            ->assertExitCode(0);

        Http::assertSentCount(1);
    }

    /** Vérifie que --dry-run affiche les URLs sans rien envoyer. */
    public function testDryRunSendsNothing(): void
    {
        Http::fake([self::ENDPOINT => Http::response('', 200)]);

        $this->artisan('indexnow:submit', ['--dry-run' => true])
            ->expectsOutputToContain('[DRY RUN] Submitting 55 URLs to IndexNow for host localhost.')
            ->expectsOutputToContain('  http://localhost/ar/faq')
            ->expectsOutputToContain('[DRY RUN] Nothing was sent.')
            ->assertExitCode(0);

        Http::assertNothingSent();
    }

    /** Vérifie qu'un endpoint injoignable est retenté deux fois puis fait échouer la commande avec un message explicite. */
    public function testUnreachableEndpointIsRetriedThenFailsTheCommand(): void
    {
        Sleep::fake();
        Http::fake([self::ENDPOINT => Http::failedConnection('Connection refused')]);

        $this->artisan('indexnow:submit')
            ->expectsOutputToContain('Could not reach the IndexNow endpoint: Connection refused')
            ->assertExitCode(1);

        Http::assertSentCount(2);
        Sleep::assertSleptTimes(1);
    }

    /** Vérifie qu'un HTTP 202 est traité comme un succès. */
    public function testAcceptedResponseIsASuccess(): void
    {
        Http::fake([self::ENDPOINT => Http::response('', 202)]);

        $this->artisan('indexnow:submit')
            ->expectsOutputToContain('IndexNow received 55 URLs (HTTP 202): key validation is pending.')
            ->assertExitCode(0);

        Http::assertSentCount(1);
    }

    /** Vérifie qu'un HTTP 400 fait échouer la commande avec un message dédié. */
    public function testMalformedRequestResponseFailsTheCommand(): void
    {
        Http::fake([self::ENDPOINT => Http::response('', 400)]);

        $this->artisan('indexnow:submit')
            ->expectsOutputToContain('malformed')
            ->assertExitCode(1);

        Http::assertSentCount(1);
    }

    /** Vérifie qu'un HTTP 403 fait échouer la commande avec un message sur la clé. */
    public function testForbiddenResponseFailsTheCommand(): void
    {
        Http::fake([self::ENDPOINT => Http::response('', 403)]);

        $this->artisan('indexnow:submit')
            ->expectsOutputToContain('could not validate the key')
            ->assertExitCode(1);

        Http::assertSentCount(1);
    }

    /** Vérifie qu'un HTTP 422 fait échouer la commande avec un message sur les URLs. */
    public function testUnprocessableResponseFailsTheCommand(): void
    {
        Http::fake([self::ENDPOINT => Http::response('', 422)]);

        $this->artisan('indexnow:submit')
            ->expectsOutputToContain('rejected the URLs')
            ->assertExitCode(1);

        Http::assertSentCount(1);
    }

    /** Vérifie qu'un HTTP 429 fait échouer la commande en relayant l'en-tête Retry-After. */
    public function testRateLimitedResponseReportsRetryAfter(): void
    {
        Http::fake([self::ENDPOINT => Http::response('', 429, ['Retry-After' => '120'])]);

        $this->artisan('indexnow:submit')
            ->expectsOutputToContain('Retry after 120 seconds')
            ->assertExitCode(1);

        Http::assertSentCount(1);
    }

    /** Vérifie qu'un Retry-After au format date HTTP est relayé tel quel, sans unité « seconds ». */
    public function testRateLimitedResponseKeepsHttpDateRetryAfterVerbatim(): void
    {
        $httpDate = 'Wed, 21 Oct 2026 07:28:00 GMT';
        Http::fake([self::ENDPOINT => Http::response('', 429, ['Retry-After' => $httpDate])]);

        $this->artisan('indexnow:submit')
            ->expectsOutputToContain("Retry after {$httpDate}.")
            ->doesntExpectOutputToContain('seconds')
            ->assertExitCode(1);

        Http::assertSentCount(1);
    }

    /** Vérifie qu'un HTTP 429 sans en-tête Retry-After n'invente aucun délai. */
    public function testRateLimitedResponseWithoutRetryAfterAdvertisesNoDelay(): void
    {
        Http::fake([self::ENDPOINT => Http::response('', 429)]);

        $this->artisan('indexnow:submit')
            ->expectsOutputToContain('rate limiting the submissions (HTTP 429).')
            ->doesntExpectOutputToContain('Retry after')
            ->doesntExpectOutputToContain('Retry in')
            ->assertExitCode(1);

        Http::assertSentCount(1);
    }

    /** Vérifie qu'un statut inattendu fait échouer la commande. */
    public function testUnexpectedStatusFailsTheCommand(): void
    {
        Http::fake([self::ENDPOINT => Http::response('', 500)]);

        $this->artisan('indexnow:submit')
            ->expectsOutputToContain('unexpected status (HTTP 500)')
            ->assertExitCode(1);

        Http::assertSentCount(1);
    }
}
