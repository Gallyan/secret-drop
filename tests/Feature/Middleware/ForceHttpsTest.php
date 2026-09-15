<?php

namespace Tests\Feature\Middleware;

use App\Http\Middleware\ForceHttps;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class ForceHttpsTest extends TestCase
{
    private ForceHttps $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new ForceHttps();
    }

    /** Vérifie que le middleware ne redirige pas en environnement local. */
    public function testDoesNotRedirectInLocalEnvironment(): void
    {
        $this->app->detectEnvironment(fn () => 'local');
        $request = Request::create('http://localhost/test', 'GET');

        $response = $this->middleware->handle($request, fn () => response('OK'));

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame('OK', $response->getContent());
    }

    /** @return array<string, array{string}> */
    public static function readMethods(): array
    {
        return [
            'GET' => ['GET'],
            'HEAD' => ['HEAD'],
        ];
    }

    /** Vérifie qu'en production une lecture HTTP est redirigée en 301 vers HTTPS en gardant l'URI et la query. */
    #[DataProvider('readMethods')]
    public function testRedirectsHttpReadPermanentlyToHttpsInProduction(string $method): void
    {
        $this->app->detectEnvironment(fn () => 'production');
        $request = Request::create('http://localhost/s/abc123?foo=bar', $method);

        $response = $this->middleware->handle($request, fn () => response('OK'));

        $this->assertSame(Response::HTTP_MOVED_PERMANENTLY, $response->getStatusCode());
        $this->assertSame('https://localhost/s/abc123?foo=bar', $response->headers->get('Location'));
    }

    /** @return array<string, array{string}> */
    public static function writeMethods(): array
    {
        return [
            'POST' => ['POST'],
            'PUT' => ['PUT'],
            'PATCH' => ['PATCH'],
            'DELETE' => ['DELETE'],
        ];
    }

    /** Vérifie qu'en production une écriture HTTP est redirigée en 308 pour que le navigateur renvoie la méthode et le corps. */
    #[DataProvider('writeMethods')]
    public function testRedirectsHttpWriteWith308InProduction(string $method): void
    {
        $this->app->detectEnvironment(fn () => 'production');
        $request = Request::create('http://localhost/api/secrets', $method, ['type' => 'text']);

        $response = $this->middleware->handle($request, fn () => response('OK'));

        $this->assertSame(Response::HTTP_PERMANENTLY_REDIRECT, $response->getStatusCode());
        $this->assertSame('https://localhost/api/secrets', $response->headers->get('Location'));
    }

    /** Vérifie qu'en production une requête HTTPS passe sans redirection et que les URL générées sont en HTTPS. */
    public function testPassesHttpsThroughAndForcesHttpsSchemeInProduction(): void
    {
        $this->app->detectEnvironment(fn () => 'production');
        $request = Request::create('https://localhost/test', 'GET');

        $response = $this->middleware->handle($request, fn () => response('OK'));

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame('OK', $response->getContent());
        $this->assertSame('https', parse_url(URL::to('/'), PHP_URL_SCHEME));
    }
}
