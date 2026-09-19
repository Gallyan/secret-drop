<?php

namespace Tests\Feature\Middleware;

use App\Http\Middleware\NoCacheHeaders;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class NoCacheHeadersTest extends TestCase
{
    /** Vérifie que le middleware remplace les en-têtes de cache par des valeurs anti-cache exactes. */
    public function testOverridesCacheHeadersWithNoStoreValues(): void
    {
        $middleware = new NoCacheHeaders();
        $request = Request::create('/test', 'GET');

        $response = $middleware->handle($request, fn () => response('OK')
            ->header('Cache-Control', 'public, max-age=3600')
            ->header('Expires', 'Fri, 01 Jan 2100 00:00:00 GMT'));

        $this->assertSame('max-age=0, must-revalidate, no-cache, no-store, private', $response->headers->get('Cache-Control'));
        $this->assertSame('no-cache', $response->headers->get('Pragma'));
        $this->assertSame('Sat, 01 Jan 2000 00:00:00 GMT', $response->headers->get('Expires'));
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function sensitiveRoutes(): array
    {
        return [
            'page de consultation GET /s/{token}' => ['GET', '/s/0123456789abcdef0123456789abcdef'],
            'fetch API GET /api/secrets/{token}' => ['GET', '/api/secrets/0123456789abcdef0123456789abcdef'],
            'confirmation POST /api/secrets/{token}/read' => ['POST', '/api/secrets/0123456789abcdef0123456789abcdef/read'],
        ];
    }

    /** Vérifie que les routes sensibles passent par le middleware anti-cache. */
    #[DataProvider('sensitiveRoutes')]
    public function testSensitiveRouteResponsesCarryNoCacheHeaders(string $method, string $uri): void
    {
        $response = $this->json($method, $uri);

        $response->assertHeader('Cache-Control', 'max-age=0, must-revalidate, no-cache, no-store, private');
        $response->assertHeader('Pragma', 'no-cache');
        $response->assertHeader('Expires', 'Sat, 01 Jan 2000 00:00:00 GMT');
    }
}
