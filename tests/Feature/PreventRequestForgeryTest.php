<?php

namespace Tests\Feature;

use App\Http\Middleware\PreventRequestForgery;
use Illuminate\Contracts\Foundation\Application;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Laravel skips CSRF verification while running unit tests; the middleware is
 * rebound here to a subclass that does not, so the real checks run.
 */
class PreventRequestForgeryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->app->bind(PreventRequestForgery::class, fn (Application $app) => new class ($app, $app['encrypter']) extends PreventRequestForgery {
            protected function runningUnitTests(): bool
            {
                return false;
            }
        });
    }

    /**
     * @return array<string, array{0: array<string, string>}>
     */
    public static function forgedRequestHeaders(): array
    {
        return [
            'sans Sec-Fetch-Site ni jeton' => [[]],
            'Sec-Fetch-Site cross-site' => [['Sec-Fetch-Site' => 'cross-site']],
            'Sec-Fetch-Site same-site' => [['Sec-Fetch-Site' => 'same-site']],
        ];
    }

    /**
     * Vérifie qu'un POST sans jeton CSRF venant d'une autre origine est rejeté en 419.
     *
     * @param  array<string, string>  $headers
     */
    #[DataProvider('forgedRequestHeaders')]
    public function testRejectsPostWithoutTokenFromAnotherOrigin(array $headers): void
    {
        $response = $this->withSession(['admin_email_hash' => 'hash', 'admin_expires_at' => now()->addHour()->timestamp])
            ->withHeaders($headers)
            ->post('/fr/admin/logout');

        $response->assertStatus(419);
        $response->assertSessionHas('admin_email_hash', 'hash');
    }

    /** Vérifie qu'un POST same-origin est accepté sans jeton CSRF. */
    public function testAcceptsSameOriginPostWithoutToken(): void
    {
        $response = $this->withHeaders(['Sec-Fetch-Site' => 'same-origin'])->post('/fr/admin/logout');

        $response->assertRedirect('/fr/admin');
    }

    /** Vérifie qu'un POST portant le jeton CSRF de session dans l'en-tête X-CSRF-TOKEN est accepté. */
    public function testAcceptsPostWithSessionTokenInHeader(): void
    {
        $this->startSession();

        $response = $this->withHeaders(['X-CSRF-TOKEN' => (string) session()->token()])->post('/fr/admin/logout');

        $response->assertRedirect('/fr/admin');
    }

    /** Vérifie qu'aucun cookie XSRF-TOKEN n'est émis, le front lisant le jeton dans la balise meta. */
    public function testDoesNotSetXsrfTokenCookie(): void
    {
        $response = $this->get('/fr/admin');

        $response->assertCookieMissing('XSRF-TOKEN');
    }
}
