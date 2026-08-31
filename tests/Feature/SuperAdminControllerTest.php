<?php

namespace Tests\Feature;

use App\Models\MagicLink;
use App\Services\StatsService;
use App\Services\TokenService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SuperAdminControllerTest extends TestCase
{
    private TokenService $tokenService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tokenService = app(TokenService::class);
        Mail::fake();
    }

    /** Vérifie que la page d'index affiche le formulaire de login superadmin. */
    public function testIndexPageDisplaysLoginForm(): void
    {
        $response = $this->get('/fr/superadmin');

        $response->assertStatus(200);
        $response->assertViewIs('superadmin.index');
    }

    /** Vérifie qu'un superadmin déjà authentifié est redirigé vers le dashboard. */
    public function testIndexRedirectsToDashboardWhenAuthenticated(): void
    {
        $response = $this->withSession(['super_admin_verified' => true])
            ->get('/fr/superadmin');

        $response->assertRedirect(route('superadmin.dashboard', ['locale' => 'fr']));
    }

    /** Vérifie que la demande d'accès affiche la page de confirmation quel que soit l'email. */
    public function testRequestAccessShowsConfirmationPage(): void
    {
        $response = $this->post('/fr/superadmin/request-access', [
            'email' => 'random@example.com',
        ]);

        $response->assertRedirect(route('superadmin.accessSent'));
    }

    /** Vérifie qu'un email avec magic link est envoyé quand l'email correspond au super admin. */
    public function testRequestAccessSendsEmailWhenSuperAdmin(): void
    {
        Config::set('app.super_admin_email', 'superadmin@example.com');

        $response = $this->post('/fr/superadmin/request-access', [
            'email' => 'superadmin@example.com',
        ]);

        $response->assertRedirect(route('superadmin.accessSent'));
        Mail::assertSent(\App\Mail\SuperAdminMagicLinkMail::class);

        $this->assertDatabaseHas('magic_links', [
            'email_hash' => MagicLink::SUPER_ADMIN_EMAIL_HASH,
        ]);
    }

    /** Vérifie qu'aucun email n'est envoyé pour un email non super admin. */
    public function testRequestAccessDoesNotSendEmailWhenNotSuperAdmin(): void
    {
        Config::set('app.super_admin_email', 'superadmin@example.com');

        $response = $this->post('/fr/superadmin/request-access', [
            'email' => 'random@example.com',
        ]);

        $response->assertRedirect(route('superadmin.accessSent'));
        Mail::assertNothingSent();
    }

    /** Vérifie que GET verify affiche la page de confirmation sans consommer le token. */
    public function testVerifyGetShowsConfirmationPage(): void
    {
        $tokenData = $this->tokenService->generateMagicLinkToken();
        MagicLink::create([
            'email_hash' => MagicLink::SUPER_ADMIN_EMAIL_HASH,
            'token_hash' => $tokenData['hash'],
            'expire_at' => now()->addMinutes(5),
        ]);

        $response = $this->get("/fr/superadmin/verify/{$tokenData['token']}");

        $response->assertStatus(200);
        $response->assertViewIs('superadmin.verify-confirm');
        $response->assertViewHas('token', $tokenData['token']);
    }

    /** Vérifie que GET verify ne marque pas le token comme utilisé (protection scanner email). */
    public function testVerifyGetDoesNotConsumeToken(): void
    {
        $tokenData = $this->tokenService->generateMagicLinkToken();
        MagicLink::create([
            'email_hash' => MagicLink::SUPER_ADMIN_EMAIL_HASH,
            'token_hash' => $tokenData['hash'],
            'expire_at' => now()->addMinutes(5),
        ]);

        $this->get("/fr/superadmin/verify/{$tokenData['token']}");

        $magicLink = MagicLink::findByToken($tokenData['token']);
        $this->assertNull($magicLink->used_at);
    }

    /** Vérifie que POST verify consomme le token et redirige vers le dashboard. */
    public function testVerifyPostRedirectsToDashboard(): void
    {
        $tokenData = $this->tokenService->generateMagicLinkToken();
        MagicLink::create([
            'email_hash' => MagicLink::SUPER_ADMIN_EMAIL_HASH,
            'token_hash' => $tokenData['hash'],
            'expire_at' => now()->addMinutes(5),
        ]);

        $response = $this->post("/fr/superadmin/verify/{$tokenData['token']}");

        $response->assertRedirect(route('superadmin.dashboard', ['locale' => 'fr']));
        $response->assertSessionHas('super_admin_verified', true);
        $response->assertSessionHas('super_admin_expires_at');
    }

    /** Vérifie le flux complet : POST verify puis accès au dashboard avec session persistante. */
    public function testVerifyPostThenDashboardFlowWorksEndToEnd(): void
    {
        $tokenData = $this->tokenService->generateMagicLinkToken();
        MagicLink::create([
            'email_hash' => MagicLink::SUPER_ADMIN_EMAIL_HASH,
            'token_hash' => $tokenData['hash'],
            'expire_at' => now()->addMinutes(5),
        ]);

        $this->post("/fr/superadmin/verify/{$tokenData['token']}");

        $dashboard = $this->get('/fr/superadmin/dashboard');
        $dashboard->assertStatus(200);
        $dashboard->assertViewIs('superadmin.dashboard');
    }

    /**
     * "14h: 2" reads like a clock time; the hourly charts must label the bucket
     * as a range and name what is being counted.
     */
    public function testHourlyChartTooltipsShowARangeAndAUnit(): void
    {
        $tokenData = $this->tokenService->generateMagicLinkToken();
        MagicLink::create([
            'email_hash' => MagicLink::SUPER_ADMIN_EMAIL_HASH,
            'token_hash' => $tokenData['hash'],
            'expire_at' => now()->addMinutes(5),
        ]);

        $this->post("/fr/superadmin/verify/{$tokenData['token']}");

        $dashboard = $this->get('/fr/superadmin/dashboard');

        $dashboard->assertStatus(200);
        $dashboard->assertSee('title="14:00–15:00 · 0 vue"', false);
        $dashboard->assertSee('title="23:00–00:00 · 0 vue"', false);
        $dashboard->assertDontSee('title="14h:', false);
    }

    /**
     * Over a single day the daily charts collapse to one point, so they switch
     * to an hourly breakdown taken from the heatmap counters.
     */
    public function testTodayPeriodExposesAnHourlyBreakdown(): void
    {
        DB::table('stats_heatmap')->insert([
            'date' => now()->toDateString(),
            'day_of_week' => (int) now()->dayOfWeek,
            'hour' => 14,
            'metric' => StatsService::HEATMAP_SECRETS_CREATED,
            'count' => 3,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->authenticateSuperAdmin();

        $today = $this->get('/fr/superadmin/dashboard?period=today');
        $today->assertStatus(200);
        $hourly = $today->viewData('hourly');

        $this->assertCount(24, $hourly['created']);
        $this->assertSame(3, $hourly['created'][14]);
        $this->assertSame(0, $hourly['created'][13]);

        foreach (['read', 'magic_links_requested', 'magic_links_used', 'secrets_extended', 'errors_4xx', 'errors_5xx'] as $series) {
            $this->assertCount(24, $hourly[$series], "La serie {$series} doit couvrir 24 heures");
        }
    }

    /** Vérifie que les erreurs HTTP sont comptées à l'heure comme au jour. */
    public function testHttpErrorsAreRecordedHourly(): void
    {
        $this->get('/fr/une-page-qui-nexiste-pas')->assertStatus(404);

        $hour = (int) now()->hour;

        $this->assertSame(1, (int) DB::table('stats_heatmap')
            ->where('metric', StatsService::HTTP_ERRORS_4XX)
            ->where('date', now()->toDateString())
            ->where('hour', $hour)
            ->value('count'));
    }

    /** Vérifie que les demandes de lien magique sont comptées à l'heure. */
    public function testMagicLinkRequestsAreRecordedHourly(): void
    {
        Config::set('app.super_admin_email', 'boss@example.com');

        $this->post('/fr/superadmin/request-access', ['email' => 'boss@example.com'])
            ->assertRedirect(route('superadmin.accessSent'));

        $this->assertSame(1, (int) DB::table('stats_heatmap')
            ->where('metric', StatsService::MAGIC_LINKS_REQUESTED)
            ->where('date', now()->toDateString())
            ->where('hour', (int) now()->hour)
            ->value('count'));
    }

    /** Vérifie que les autres périodes conservent l'affichage par jour. */
    public function testOtherPeriodsKeepTheDailyBreakdown(): void
    {
        $this->authenticateSuperAdmin();

        $response = $this->get('/fr/superadmin/dashboard?period=30d');

        $response->assertStatus(200);
        $this->assertNull($response->viewData('hourly'));
    }

    private function authenticateSuperAdmin(): void
    {
        $tokenData = $this->tokenService->generateMagicLinkToken();
        MagicLink::create([
            'email_hash' => MagicLink::SUPER_ADMIN_EMAIL_HASH,
            'token_hash' => $tokenData['hash'],
            'expire_at' => now()->addMinutes(5),
        ]);

        $this->post("/fr/superadmin/verify/{$tokenData['token']}");
    }

    /** Vérifie qu'un token invalide affiche la page d'erreur (GET). */
    public function testVerifyWithInvalidTokenShowsError(): void
    {
        $response = $this->get('/fr/superadmin/verify/invalid-token');

        $response->assertStatus(200);
        $response->assertViewIs('superadmin.invalid-link');
    }

    /** Vérifie qu'un token invalide affiche la page d'erreur (POST). */
    public function testVerifyPostWithInvalidTokenShowsError(): void
    {
        $response = $this->post('/fr/superadmin/verify/invalid-token');

        $response->assertStatus(200);
        $response->assertViewIs('superadmin.invalid-link');
    }

    /** Vérifie qu'un token expiré affiche la page d'erreur. */
    public function testVerifyWithExpiredTokenShowsError(): void
    {
        $tokenData = $this->tokenService->generateMagicLinkToken();
        MagicLink::create([
            'email_hash' => MagicLink::SUPER_ADMIN_EMAIL_HASH,
            'token_hash' => $tokenData['hash'],
            'expire_at' => now()->subMinutes(1),
        ]);

        $response = $this->get("/fr/superadmin/verify/{$tokenData['token']}");

        $response->assertStatus(200);
        $response->assertViewIs('superadmin.invalid-link');
    }

    /** Vérifie qu'un token admin normal ne fonctionne pas sur la route superadmin. */
    public function testVerifyWithNonSuperadminTokenShowsError(): void
    {
        $tokenData = $this->tokenService->generateMagicLinkToken();
        MagicLink::create([
            'email_hash' => 'regular-user-hash',
            'token_hash' => $tokenData['hash'],
            'expire_at' => now()->addMinutes(5),
        ]);

        $response = $this->get("/fr/superadmin/verify/{$tokenData['token']}");

        $response->assertStatus(200);
        $response->assertViewIs('superadmin.invalid-link');
    }

    /** Vérifie que le dashboard redirige vers le login sans authentification. */
    public function testDashboardRequiresAuthentication(): void
    {
        $response = $this->get('/fr/superadmin/dashboard');

        $response->assertRedirect(route('superadmin.index', ['locale' => 'fr']));
    }

    /** Vérifie que le dashboard affiche les statistiques quand authentifié. */
    public function testDashboardDisplaysStatsWhenAuthenticated(): void
    {
        $response = $this->withSession(['super_admin_verified' => true])
            ->get('/fr/superadmin/dashboard');

        $response->assertStatus(200);
        $response->assertViewIs('superadmin.dashboard');
        $response->assertViewHas('stats');
    }

    /** Vérifie que l'anneau de polling expose le template de son infobulle de décompte. */
    public function testDashboardPollRingCarriesRefreshTitleTemplate(): void
    {
        $response = $this->withSession(['super_admin_verified' => true])
            ->get('/fr/superadmin/dashboard');

        $response->assertStatus(200);
        $response->assertSee('data-title-template="'.__('messages.poll_refresh_in', [], 'fr').'"', false);
        $response->assertSee('pollRingTitle');
    }

    /** Vérifie que le logout détruit la session et redirige vers le login. */
    public function testLogoutClearsSession(): void
    {
        $response = $this->withSession(['super_admin_verified' => true])
            ->post('/fr/superadmin/logout');

        $response->assertRedirect(route('superadmin.index', ['locale' => 'fr']));
        $response->assertSessionMissing('super_admin_verified');
    }

    /** Vérifie que la session expirée redirige vers le login. */
    public function testSessionExpiresAfterTimeout(): void
    {
        $response = $this->withSession([
            'super_admin_verified' => true,
            'super_admin_expires_at' => now()->subHours(3)->timestamp,
        ])->get('/fr/superadmin/dashboard');

        $response->assertRedirect(route('superadmin.index', ['locale' => 'fr']));
    }

    /** Vérifie qu'une période invalide est remplacée par la valeur par défaut (30d). */
    public function testDashboardWithInvalidPeriodFallsBackToDefault(): void
    {
        $response = $this->withSession(['super_admin_verified' => true])
            ->get('/fr/superadmin/dashboard?period=2d');

        $response->assertStatus(200);
        $response->assertViewIs('superadmin.dashboard');
        $response->assertViewHas('period', '30d');
    }

    /** Vérifie qu'une période valide est bien utilisée pour le dashboard. */
    public function testDashboardWithValidPeriodUsesRequestedPeriod(): void
    {
        $response = $this->withSession(['super_admin_verified' => true])
            ->get('/fr/superadmin/dashboard?period=7d');

        $response->assertStatus(200);
        $response->assertViewHas('period', '7d');
    }
}
