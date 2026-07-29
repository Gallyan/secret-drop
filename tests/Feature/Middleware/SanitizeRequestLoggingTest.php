<?php

namespace Tests\Feature\Middleware;

use App\Http\Middleware\SanitizeRequestLogging;
use Illuminate\Http\Request;
use Tests\TestCase;

class SanitizeRequestLoggingTest extends TestCase
{
    private SanitizeRequestLogging $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new SanitizeRequestLogging();
    }

    private function hexToken(): string
    {
        return bin2hex(random_bytes(16));
    }

    /** Vérifie la redaction du token d'un magic link admin localisé. */
    public function testRedactsTokenInLocalizedAdminVerifyUri(): void
    {
        $token = $this->hexToken();
        $request = Request::create("/fr/admin/verify/{$token}", 'GET');

        $this->middleware->handle($request, fn ($req) => response('OK'));

        $this->assertEquals('/fr/admin/verify/[TOKEN]', $request->server->get('REQUEST_URI'));
        $this->assertEquals('[REDACTED]', $request->server->get('ORIGINAL_REQUEST_URI'));
    }

    /** Vérifie la redaction du token d'un magic link superadmin localisé. */
    public function testRedactsTokenInLocalizedSuperAdminVerifyUri(): void
    {
        $token = $this->hexToken();
        $request = Request::create("/de/superadmin/verify/{$token}", 'GET');

        $this->middleware->handle($request, fn ($req) => response('OK'));

        $this->assertEquals('/de/superadmin/verify/[TOKEN]', $request->server->get('REQUEST_URI'));
    }

    /** Vérifie la redaction du token d'un magic link admin sans préfixe de locale. */
    public function testRedactsTokenInNonLocalizedAdminVerifyUri(): void
    {
        $token = $this->hexToken();
        $request = Request::create("/admin/verify/{$token}", 'GET');

        $this->middleware->handle($request, fn ($req) => response('OK'));

        $this->assertEquals('/admin/verify/[TOKEN]', $request->server->get('REQUEST_URI'));
    }

    /** Vérifie qu'une URI non sensible reste inchangée. */
    public function testLeavesNonSensitiveUriUntouched(): void
    {
        $request = Request::create('/fr/about', 'GET');

        $this->middleware->handle($request, fn ($req) => response('OK'));

        $this->assertEquals('/fr/about', $request->server->get('REQUEST_URI'));
        $this->assertFalse($request->server->has('ORIGINAL_REQUEST_URI'));
    }

    /** Vérifie la redaction du fragment dans le referer. */
    public function testRedactsFragmentInReferer(): void
    {
        $request = Request::create('/fr/about', 'GET', server: [
            'HTTP_REFERER' => 'https://example.com/s/abc123#secret-key-material',
        ]);

        $this->middleware->handle($request, fn ($req) => response('OK'));

        $this->assertEquals(
            'https://example.com/s/abc123#[REDACTED]',
            $request->server->get('HTTP_REFERER')
        );
    }
}
