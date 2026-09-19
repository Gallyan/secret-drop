<?php

namespace Tests\Feature;

use App\Mail\MagicLinkMail;
use App\Mail\SuperAdminMagicLinkMail;
use App\Models\Secret;
use App\Providers\AppServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Sleep;
use Tests\TestCase;

class TrustedHostsTest extends TestCase
{
    private const APP_URL = 'https://secret.test';

    private const OWNER_EMAIL = 'owner@example.com';

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('app.url', self::APP_URL);
        $this->bootAppServiceProvider();
    }

    protected function tearDown(): void
    {
        Request::setTrustedHosts([]);

        parent::tearDown();
    }

    /** Vérifie que le lien magique admin pointe vers APP_URL même si la requête porte un Host forgé. */
    public function testAdminMagicLinkUsesAppUrlHostInsteadOfRequestHost(): void
    {
        Mail::fake();
        Sleep::fake();
        Secret::factory()->withCreatorEmail(self::OWNER_EMAIL)->create();

        $this->post('https://attacker.example/fr/admin/request-access', ['email' => self::OWNER_EMAIL])
            ->assertRedirect(self::APP_URL.'/fr/admin/access-sent');

        Mail::assertSent(MagicLinkMail::class, function (MagicLinkMail $mail): bool {
            return parse_url($mail->verifyUrl, PHP_URL_HOST) === 'secret.test'
                && str_starts_with($mail->verifyUrl, self::APP_URL.'/fr/admin/verify/');
        });
    }

    /** Vérifie que le lien magique superadmin pointe vers APP_URL même si la requête porte un Host forgé. */
    public function testSuperAdminMagicLinkUsesAppUrlHostInsteadOfRequestHost(): void
    {
        Mail::fake();
        Sleep::fake();
        Config::set('app.super_admin_email', self::OWNER_EMAIL);

        $this->post('https://attacker.example/fr/superadmin/request-access', ['email' => self::OWNER_EMAIL]);

        Mail::assertSent(SuperAdminMagicLinkMail::class, function (SuperAdminMagicLinkMail $mail): bool {
            return parse_url($mail->verifyUrl, PHP_URL_HOST) === 'secret.test';
        });
    }

    /** Vérifie qu'un APP_URL sans hôte ne force pas la racine des URLs générées. */
    public function testRootUrlIsNotForcedWhenAppUrlHasNoHost(): void
    {
        Config::set('app.url', 'localhost-without-scheme');
        URL::forceRootUrl(null);
        $this->bootAppServiceProvider();

        $this->get('https://other.test/fr/admin');

        $this->assertSame('https://other.test/fr/admin', route('admin.index', ['locale' => 'fr']));
    }

    /** Vérifie qu'hors tests et hors local une requête sur un Host inconnu est rejetée. */
    public function testUntrustedHostIsRejectedOutsideLocalAndTesting(): void
    {
        $this->app['env'] = 'staging';

        $this->get('https://attacker.example/fr/admin')->assertBadRequest();
    }

    /** Vérifie qu'un sous-domaine de l'hôte d'APP_URL n'est pas approuvé. */
    public function testSubdomainOfAppHostIsRejected(): void
    {
        $this->app['env'] = 'staging';

        $this->get('https://evil.secret.test/fr/admin')->assertBadRequest();
    }

    /** Vérifie qu'hors tests et hors local une requête sur l'hôte d'APP_URL est servie. */
    public function testAppHostIsAcceptedOutsideLocalAndTesting(): void
    {
        $this->app['env'] = 'staging';

        $this->get(self::APP_URL.'/fr/admin')->assertOk();
    }

    private function bootAppServiceProvider(): void
    {
        $provider = $this->app->getProvider(AppServiceProvider::class);
        $this->assertInstanceOf(AppServiceProvider::class, $provider);
        $provider->boot();
    }
}
