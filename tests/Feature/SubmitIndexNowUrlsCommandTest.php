<?php

namespace Tests\Feature;

use App\Support\PublicUrls;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SubmitIndexNowUrlsCommandTest extends TestCase
{
    private const KEY = 'test-indexnow-key-0123456789abcdef';

    private const ENDPOINT = 'https://indexnow.test/indexnow';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.indexnow.key' => self::KEY,
            'services.indexnow.endpoint' => self::ENDPOINT,
        ]);

        Http::preventStrayRequests();
    }

    /** Vérifie qu'une clé absente fait échouer la commande sans émettre de requête. */
    public function testCommandFailsWithoutConfiguredKey(): void
    {
        Http::fake();
        config(['services.indexnow.key' => null]);

        $this->artisan('indexnow:submit')
            ->expectsOutputToContain('IndexNow key is not configured')
            ->assertExitCode(1);

        Http::assertNothingSent();
    }

    /** Vérifie que la commande poste toutes les URLs publiques sur l'endpoint configuré. */
    public function testCommandSubmitsEveryPublicUrl(): void
    {
        Http::fake([self::ENDPOINT => Http::response('', 200)]);

        $this->artisan('indexnow:submit')
            ->expectsOutputToContain('HTTP 200')
            ->assertExitCode(0);

        Http::assertSentCount(1);

        Http::assertSent(function (Request $request): bool {
            $payload = $request->data();

            $this->assertSame('POST', $request->method());
            $this->assertSame(self::ENDPOINT, $request->url());

            $this->assertSame('localhost', $payload['host']);
            $this->assertSame(self::KEY, $payload['key']);
            $this->assertSame(url('/'.self::KEY.'.txt'), $payload['keyLocation']);
            $this->assertSame(PublicUrls::all(), $payload['urlList']);

            return true;
        });
    }

    /**
     * Vérifie que la requête déclare un seul Content-Type JSON, sans quoi l'API répond 415.
     *
     * Garde-fou contre un retour à withHeaders(['Content-Type' => ...]) après asJson() :
     * withHeaders fait un array_merge_recursive, donc l'en-tête partirait en double valeur
     * « application/json, application/json; charset=utf-8 ». contentType() écrase, lui.
     */
    public function testRequestDeclaresASingleJsonContentType(): void
    {
        Http::fake([self::ENDPOINT => Http::response('', 200)]);

        $this->artisan('indexnow:submit')->assertExitCode(0);

        Http::assertSent(function (Request $request): bool {
            $this->assertSame(['application/json; charset=utf-8'], $request->header('Content-Type'));

            return true;
        });
    }

    /** Vérifie que la liste soumise couvre les 11 locales et les 4 pages traduisibles. */
    public function testSubmittedUrlListCoversEveryLocaleAndPage(): void
    {
        Http::fake([self::ENDPOINT => Http::response('', 200)]);

        $this->artisan('indexnow:submit')->assertExitCode(0);

        Http::assertSent(function (Request $request): bool {
            /** @var array<int, string> $urlList */
            $urlList = $request->data()['urlList'];

            $this->assertCount(55, $urlList);
            $this->assertSame($urlList, array_unique($urlList));

            foreach (['en', 'fr', 'de', 'es', 'it', 'pt', 'nl', 'pl', 'ja', 'ko', 'ar'] as $locale) {
                $this->assertContains(url("/{$locale}"), $urlList);
            }

            $this->assertContains(url('/en/how-it-works'), $urlList);
            $this->assertContains(url('/fr/comment-ca-marche'), $urlList);
            $this->assertContains(url('/fr/mentions-legales'), $urlList);
            $this->assertContains(url('/en/faq'), $urlList);

            return true;
        });
    }

    /** Vérifie qu'aucune URL privée n'est soumise aux moteurs. */
    public function testSubmittedUrlListContainsNoPrivateUrl(): void
    {
        Http::fake([self::ENDPOINT => Http::response('', 200)]);

        $this->artisan('indexnow:submit')->assertExitCode(0);

        Http::assertSent(function (Request $request): bool {
            /** @var array<int, string> $urlList */
            $urlList = $request->data()['urlList'];

            foreach ($urlList as $url) {
                $this->assertStringNotContainsString('/s/', $url);
                $this->assertStringNotContainsString('/admin', $url);
                $this->assertStringNotContainsString('/superadmin', $url);
                $this->assertStringNotContainsString('/api', $url);
                $this->assertStringNotContainsString('/contact', $url);
            }

            return true;
        });
    }

    /** Vérifie que l'option --url restreint la soumission aux URLs fournies. */
    public function testUrlOptionSubmitsOnlyTheGivenUrls(): void
    {
        Http::fake([self::ENDPOINT => Http::response('', 200)]);

        $this->artisan('indexnow:submit', ['--url' => [url('/fr'), url('/fr/faq')]])
            ->assertExitCode(0);

        Http::assertSent(function (Request $request): bool {
            $this->assertSame([url('/fr'), url('/fr/faq')], $request->data()['urlList']);

            return true;
        });
    }

    /** Vérifie que --dry-run affiche les URLs sans rien envoyer. */
    public function testDryRunSendsNothing(): void
    {
        Http::fake();

        $this->artisan('indexnow:submit', ['--dry-run' => true])
            ->expectsOutputToContain('[DRY RUN]')
            ->assertExitCode(0);

        Http::assertNothingSent();
    }

    /** Vérifie qu'un HTTP 202 est traité comme un succès. */
    public function testAcceptedResponseIsASuccess(): void
    {
        Http::fake([self::ENDPOINT => Http::response('', 202)]);

        $this->artisan('indexnow:submit')
            ->expectsOutputToContain('HTTP 202')
            ->assertExitCode(0);
    }

    /** Vérifie qu'un HTTP 400 fait échouer la commande avec un message dédié. */
    public function testMalformedRequestResponseFailsTheCommand(): void
    {
        Http::fake([self::ENDPOINT => Http::response('', 400)]);

        $this->artisan('indexnow:submit')
            ->expectsOutputToContain('malformed')
            ->assertExitCode(1);
    }

    /** Vérifie qu'un HTTP 403 fait échouer la commande avec un message sur la clé. */
    public function testForbiddenResponseFailsTheCommand(): void
    {
        Http::fake([self::ENDPOINT => Http::response('', 403)]);

        $this->artisan('indexnow:submit')
            ->expectsOutputToContain('could not validate the key')
            ->assertExitCode(1);
    }

    /** Vérifie qu'un HTTP 422 fait échouer la commande avec un message sur les URLs. */
    public function testUnprocessableResponseFailsTheCommand(): void
    {
        Http::fake([self::ENDPOINT => Http::response('', 422)]);

        $this->artisan('indexnow:submit')
            ->expectsOutputToContain('rejected the URLs')
            ->assertExitCode(1);
    }

    /** Vérifie qu'un HTTP 429 fait échouer la commande en relayant l'en-tête Retry-After. */
    public function testRateLimitedResponseReportsRetryAfter(): void
    {
        Http::fake([self::ENDPOINT => Http::response('', 429, ['Retry-After' => '120'])]);

        $this->artisan('indexnow:submit')
            ->expectsOutputToContain('Retry after 120 seconds')
            ->assertExitCode(1);
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
    }

    /** Vérifie qu'un statut inattendu fait échouer la commande. */
    public function testUnexpectedStatusFailsTheCommand(): void
    {
        Http::fake([self::ENDPOINT => Http::response('', 500)]);

        $this->artisan('indexnow:submit')
            ->expectsOutputToContain('unexpected status')
            ->assertExitCode(1);
    }
}
