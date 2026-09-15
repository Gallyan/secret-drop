<?php

namespace Tests\Feature\Middleware;

use App\Models\Secret;
use Illuminate\Foundation\Http\Events\RequestHandled;
use Illuminate\Http\Request;
use Illuminate\Log\Logger;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Monolog\Handler\TestHandler;
use Monolog\Logger as MonologLogger;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

/**
 * Le middleware ne réécrit que le server bag : getRequestUri(), path(), les paramètres
 * de route et l'en-tête Referer gardent les vraies valeurs, le routage fonctionne donc
 * et les logs sont protégés par SanitizeProcessor, pas par ce middleware.
 */
class SanitizeRequestLoggingTest extends TestCase
{
    private const SECRET_TOKEN = '0123456789abcdef0123456789abcdef';

    private const MAGIC_LINK_TOKEN = 'fedcba9876543210fedcba9876543210fedcba9876543210fedcba9876543210';

    /** @return array<string, array{string, string}> */
    public static function sensitiveUris(): array
    {
        return [
            'page de lecture du secret' => ['/s/'.self::SECRET_TOKEN, '/s/[TOKEN]'],
            'téléchargement du secret' => ['/s/'.self::SECRET_TOKEN.'/download', '/s/[TOKEN]/download'],
            'API de lecture' => ['/api/secrets/'.self::SECRET_TOKEN, '/api/secrets/[TOKEN]'],
            'API de confirmation' => ['/api/secrets/'.self::SECRET_TOKEN.'/read', '/api/secrets/[TOKEN]/read'],
            'magic link admin localisé' => ['/fr/admin/verify/'.self::MAGIC_LINK_TOKEN, '/fr/admin/verify/[TOKEN]'],
            'magic link superadmin localisé' => ['/de/superadmin/verify/'.self::MAGIC_LINK_TOKEN, '/de/superadmin/verify/[TOKEN]'],
            'magic link admin sans locale' => ['/admin/verify/'.self::MAGIC_LINK_TOKEN, '/admin/verify/[TOKEN]'],
        ];
    }

    /** Vérifie que le REQUEST_URI du server bag est masqué pour chaque route à token, via le pipeline HTTP réel. */
    #[DataProvider('sensitiveUris')]
    public function testRedactsTokenInServerRequestUri(string $uri, string $expectedServerUri): void
    {
        $request = $this->handledRequest(fn () => $this->get($uri));

        $this->assertSame($expectedServerUri, $request->server->get('REQUEST_URI'));
        $this->assertSame('[REDACTED]', $request->server->get('ORIGINAL_REQUEST_URI'));
    }

    /** Vérifie que le masquage ne casse pas le routage : le contrôleur reçoit le vrai token. */
    public function testRoutingStillReceivesTheRealToken(): void
    {
        $secret = Secret::factory()->create();

        $request = $this->handledRequest(
            fn () => $this->getJson("/api/secrets/{$secret->token}")->assertOk()->assertJsonPath('ciphertext', $secret->ciphertext)
        );

        $this->assertSame("/api/secrets/{$secret->token}", $request->getRequestUri());
        $this->assertSame('/api/secrets/[TOKEN]', $request->server->get('REQUEST_URI'));
    }

    /** Vérifie qu'un segment trop court pour être un token et une URI publique restent intacts. */
    public function testLeavesShortSegmentsAndPublicUrisUntouched(): void
    {
        $shortSegment = $this->handledRequest(fn () => $this->get('/s/abc123'));
        $publicPage = $this->handledRequest(fn () => $this->get('/fr/faq'));

        $this->assertSame('/s/abc123', $shortSegment->server->get('REQUEST_URI'));
        $this->assertFalse($shortSegment->server->has('ORIGINAL_REQUEST_URI'));
        $this->assertSame('/fr/faq', $publicPage->server->get('REQUEST_URI'));
        $this->assertFalse($publicPage->server->has('ORIGINAL_REQUEST_URI'));
    }

    /** Vérifie qu'une query string sensible est masquée dans le server bag et disparaît de fullUrl(), sans toucher aux paramètres lus. */
    public function testRedactsSensitiveQueryString(): void
    {
        $request = $this->handledRequest(fn () => $this->get('/fr/faq?lang=fr&token=leaky-value'));

        $this->assertSame('[REDACTED]', $request->server->get('QUERY_STRING'));
        $this->assertSame('/fr/faq?[REDACTED]', $request->server->get('REQUEST_URI'));
        $this->assertStringNotContainsString('leaky-value', $request->fullUrl());
        $this->assertSame('leaky-value', $request->query('token'));
    }

    /** Vérifie qu'une query string sans paramètre sensible est conservée. */
    public function testKeepsHarmlessQueryString(): void
    {
        $request = $this->handledRequest(fn () => $this->get('/fr/faq?lang=fr'));

        $this->assertSame('lang=fr', $request->server->get('QUERY_STRING'));
        $this->assertSame('/fr/faq?lang=fr', $request->server->get('REQUEST_URI'));
    }

    /** @return array<string, array{string, string}> */
    public static function sensitiveReferers(): array
    {
        return [
            'fragment de clé' => [
                'https://secret.test/s/abc123#secret-key-material',
                'https://secret.test/s/abc123#[REDACTED]',
            ],
            'token de secret sans fragment' => [
                'https://secret.test/s/'.self::SECRET_TOKEN,
                'https://secret.test/s/[TOKEN]',
            ],
            'token de secret et fragment' => [
                'https://secret.test/s/'.self::SECRET_TOKEN.'#secret-key-material',
                'https://secret.test/s/[TOKEN]#[REDACTED]',
            ],
            'magic link admin' => [
                'https://secret.test/fr/admin/verify/'.self::MAGIC_LINK_TOKEN,
                'https://secret.test/fr/admin/verify/[TOKEN]',
            ],
            'page publique' => [
                'https://secret.test/fr/faq',
                'https://secret.test/fr/faq',
            ],
        ];
    }

    /** Vérifie le masquage du referer dans le server bag ; l'en-tête Referer lu par l'application reste intact. */
    #[DataProvider('sensitiveReferers')]
    public function testRedactsTokensInServerReferer(string $referer, string $expectedServerReferer): void
    {
        $request = $this->handledRequest(fn () => $this->withHeader('Referer', $referer)->get('/fr/faq'));

        $this->assertSame($expectedServerReferer, $request->server->get('HTTP_REFERER'));
        $this->assertSame($referer, $request->headers->get('Referer'));
    }

    /** Vérifie qu'une exception signalée pendant une requête à token n'écrit pas l'URL complète dans le log. */
    public function testReportedExceptionWithFullUrlDoesNotLeakTheTokenToLogs(): void
    {
        config([
            'logging.default' => 'capture',
            'logging.channels.capture' => array_merge((array) config('logging.channels.stderr'), [
                'handler' => TestHandler::class,
                'handler_with' => [],
            ]),
        ]);
        Route::get('/api/secrets/{token}/explode', function (Request $request): void {
            throw new RuntimeException("Échec pour {$request->fullUrl()}");
        });

        $this->get('/api/secrets/'.self::SECRET_TOKEN.'/explode')->assertInternalServerError();

        $channel = Log::channel('capture');
        $this->assertInstanceOf(Logger::class, $channel);
        $monolog = $channel->getLogger();
        $this->assertInstanceOf(MonologLogger::class, $monolog);
        $handler = $monolog->getHandlers()[0];
        $this->assertInstanceOf(TestHandler::class, $handler);
        $this->assertCount(1, $handler->getRecords());
        $formatted = $handler->getRecords()[0]->formatted;
        $this->assertIsString($formatted);
        $this->assertStringContainsString('http://localhost/api/secrets/[TOKEN]/explode', $formatted);
        $this->assertStringNotContainsString(self::SECRET_TOKEN, $formatted);
    }

    /**
     * @param  callable(): mixed  $sendRequest
     */
    private function handledRequest(callable $sendRequest): Request
    {
        $handled = null;
        Event::listen(RequestHandled::class, function (RequestHandled $event) use (&$handled): void {
            $handled = $event->request;
        });

        $sendRequest();

        $this->assertInstanceOf(Request::class, $handled);

        return $handled;
    }
}
