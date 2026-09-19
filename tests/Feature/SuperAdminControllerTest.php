<?php

namespace Tests\Feature;

use App\Mail\SuperAdminMagicLinkMail;
use App\Models\MagicLink;
use App\Services\StatsService;
use Closure;
use Illuminate\Support\Defer\DeferredCallbackCollection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Sleep;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SuperAdminControllerTest extends TestCase
{
    private const SUPER_ADMIN_EMAIL = 'boss@example.com';

    private const MAGIC_LINK_TOKEN = 'plain-superadmin-magic-link-token';

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('app.super_admin_email', self::SUPER_ADMIN_EMAIL);
        Config::set('secrets.magic_link_ttl', 10);
        Config::set('secrets.super_admin_session_ttl', 15);
    }

    /** Vérifie que l'index affiche le formulaire de connexion superadmin sans session. */
    public function testIndexRendersLoginFormWithoutSession(): void
    {
        $response = $this->get('/fr/superadmin');

        $response->assertViewIs('superadmin.index');
    }

    /** Vérifie qu'un superadmin à la session valide est redirigé vers le dashboard. */
    public function testIndexRedirectsToDashboardWhenSessionIsValid(): void
    {
        $response = $this->withSession($this->superAdminSession())->get('/fr/superadmin');

        $response->assertRedirect('/fr/superadmin/dashboard');
    }

    /**
     * @return array<string, array{0: Closure(): array<string, mixed>}>
     */
    public static function unusableSessions(): array
    {
        return [
            'sans date d\'expiration' => [fn (): array => ['super_admin_verified' => true]],
            'expirée' => [fn (): array => [
                'super_admin_verified' => true,
                'super_admin_expires_at' => now()->subSecond()->timestamp,
            ]],
        ];
    }

    /**
     * Vérifie que l'index affiche le formulaire quand la session est expirée ou sans expiration.
     *
     * @param  Closure(): array<string, mixed>  $session
     */
    #[DataProvider('unusableSessions')]
    public function testIndexRendersLoginFormWhenSessionIsNotUsable(Closure $session): void
    {
        $this->freezeTime();

        $response = $this->withSession($session())->get('/fr/superadmin');

        $response->assertViewIs('superadmin.index');
    }

    /** Vérifie que l'email superadmin, comparé sans casse ni espaces, reçoit le lien, persisté et compté. */
    public function testRequestAccessSendsMagicLinkToSuperAdminIgnoringCaseAndSpaces(): void
    {
        $this->travelTo('2026-09-15 14:30:00');
        Config::set('app.super_admin_email', '  Boss@Example.COM ');
        Mail::fake();

        $response = $this->post('/fr/superadmin/request-access', ['email' => 'BOSS@example.com']);

        $response->assertRedirect('/fr/superadmin/access-sent');

        Mail::assertSent(
            SuperAdminMagicLinkMail::class,
            fn (SuperAdminMagicLinkMail $mail): bool => $mail->hasTo(self::SUPER_ADMIN_EMAIL)
        );

        $magicLink = MagicLink::sole();
        $this->assertSame(MagicLink::SUPER_ADMIN_EMAIL_HASH, $magicLink->email_hash);
        $this->assertSame('2026-09-15 14:40:00', $magicLink->expire_at->toDateTimeString());

        $this->assertDatabaseHas('stats_daily', [
            'date' => '2026-09-15',
            'metric' => StatsService::MAGIC_LINKS_REQUESTED,
            'count' => 1,
        ]);
        $this->assertDatabaseHas('stats_heatmap', [
            'date' => '2026-09-15',
            'hour' => 14,
            'metric' => StatsService::MAGIC_LINKS_REQUESTED,
            'count' => 1,
        ]);
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function refusedSuperAdminConfigurations(): array
    {
        return [
            'email différent du superadmin' => [self::SUPER_ADMIN_EMAIL],
            'aucun superadmin configuré' => [''],
        ];
    }

    /** Vérifie qu'un email refusé ne reçoit rien, ne crée aucun lien et obtient la même réponse, sans délai artificiel. */
    #[DataProvider('refusedSuperAdminConfigurations')]
    public function testRequestAccessForRefusedEmailSendsNothing(string $configuredEmail): void
    {
        Config::set('app.super_admin_email', $configuredEmail);
        Mail::fake();
        Sleep::fake();

        $response = $this->post('/fr/superadmin/request-access', ['email' => 'random@example.com']);

        $response->assertRedirect('/fr/superadmin/access-sent');

        Mail::assertNothingSent();

        $this->assertDatabaseCount('magic_links', 0);
        $this->assertDatabaseMissing('stats_daily', ['metric' => StatsService::MAGIC_LINKS_REQUESTED]);

        Sleep::assertNeverSlept();
    }

    /** Vérifie que la comparaison avec l'email superadmin et l'envoi n'ont lieu qu'après une réponse identique. */
    public function testRequestAccessDefersAllRecipientWorkAfterTheResponse(): void
    {
        Config::set('app.super_admin_email', self::SUPER_ADMIN_EMAIL);
        Mail::fake();
        $deferred = $this->holdDeferredCallbacks();

        $matchingResponse = $this->post('/fr/superadmin/request-access', ['email' => self::SUPER_ADMIN_EMAIL]);
        $refusedResponse = $this->post('/fr/superadmin/request-access', ['email' => 'random@example.com']);

        $this->assertSame($matchingResponse->getStatusCode(), $refusedResponse->getStatusCode());
        $this->assertSame($matchingResponse->headers->get('Location'), $refusedResponse->headers->get('Location'));

        Mail::assertNothingSent();

        $this->assertDatabaseCount('magic_links', 0);

        $deferred->invoke();

        Mail::assertSent(SuperAdminMagicLinkMail::class, 1);

        $this->assertDatabaseCount('magic_links', 1);
    }

    /** Vérifie que les envois au superadmin sont bornés par heure, quelle que soit l'IP, sans changer la réponse. */
    public function testRequestAccessIsLimitedPerHourRegardlessOfIp(): void
    {
        Config::set('app.super_admin_email', self::SUPER_ADMIN_EMAIL);
        Config::set('secrets.magic_link_max_per_recipient_per_hour', 2);
        Mail::fake();

        foreach (['203.0.113.1', '203.0.113.2', '203.0.113.3'] as $ip) {
            $this->withServerVariables(['REMOTE_ADDR' => $ip])
                ->post('/fr/superadmin/request-access', ['email' => self::SUPER_ADMIN_EMAIL])
                ->assertRedirect('/fr/superadmin/access-sent')
                ->assertSessionHasNoErrors();
        }

        Mail::assertSent(SuperAdminMagicLinkMail::class, 2);

        $this->assertDatabaseCount('magic_links', 2);

        $this->travel(61)->minutes();

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.4'])
            ->post('/fr/superadmin/request-access', ['email' => self::SUPER_ADMIN_EMAIL]);

        Mail::assertSent(SuperAdminMagicLinkMail::class, 3);
    }

    /** Vérifie qu'une demande faite depuis /en produit un mail superadmin rendu en anglais. */
    public function testRequestAccessFromEnglishPageSendsEnglishMail(): void
    {
        Mail::fake();

        $this->post('/en/superadmin/request-access', ['email' => self::SUPER_ADMIN_EMAIL]);

        $mail = Mail::sent(
            SuperAdminMagicLinkMail::class,
            fn (SuperAdminMagicLinkMail $mail): bool => $mail->hasTo(self::SUPER_ADMIN_EMAIL)
        )->sole();
        $this->assertSame('en', $mail->locale);
        $this->assertStringContainsString(e(__('messages.email_superadmin_button', [], 'en')), $mail->render());
    }

    /**
     * @return array<string, array{0: array<string, string>, 1: string}>
     */
    public static function invalidAccessRequests(): array
    {
        return [
            'email absent' => [[], 'messages.val_email_required'],
            'email invalide' => [['email' => 'not-an-email'], 'messages.val_email_invalid'],
            'email de plus de 255 caractères' => [
                ['email' => str_repeat('a', 60).'@'.implode('.', str_split(str_repeat('b', 200), 60)).'.com'],
                'messages.val_email_max',
            ],
        ];
    }

    /**
     * Vérifie que chaque règle de validation de l'email affiche son message sur le formulaire superadmin.
     *
     * @param  array<string, string>  $payload
     */
    #[DataProvider('invalidAccessRequests')]
    public function testRequestAccessRejectsInvalidEmailWithVisibleMessage(array $payload, string $messageKey): void
    {
        Mail::fake();
        Sleep::fake();

        $response = $this->from('/fr/superadmin')
            ->followingRedirects()
            ->post('/fr/superadmin/request-access', $payload);

        $response->assertViewIs('superadmin.index');
        $response->assertSee(__($messageKey));

        Mail::assertNothingSent();

        $this->assertDatabaseCount('magic_links', 0);
    }

    /** Vérifie que GET verify affiche la confirmation sans consommer le lien (protection contre les scanners de mail). */
    public function testVerifyGetShowsConfirmationWithoutConsumingLink(): void
    {
        $magicLink = MagicLink::factory()->superAdmin()->withToken(self::MAGIC_LINK_TOKEN)->create();

        $response = $this->get('/fr/superadmin/verify/'.self::MAGIC_LINK_TOKEN);

        $response->assertViewIs('superadmin.verify-confirm');
        $response->assertViewHas('token', self::MAGIC_LINK_TOKEN);
        $response->assertSessionMissing('super_admin_verified');

        $this->assertNull($magicLink->refresh()->used_at);
    }

    /** Vérifie que le lien réellement envoyé par mail ouvre la session superadmin. */
    public function testFollowingTheMailedLinkOpensSuperAdminSession(): void
    {
        Storage::fake('secrets');
        Mail::fake();
        $this->post('/fr/superadmin/request-access', ['email' => self::SUPER_ADMIN_EMAIL]);
        $verifyUrl = Mail::sent(
            SuperAdminMagicLinkMail::class,
            fn (SuperAdminMagicLinkMail $mail): bool => $mail->hasTo(self::SUPER_ADMIN_EMAIL)
        )->sole()->verifyUrl;

        $this->get($verifyUrl)->assertViewIs('superadmin.verify-confirm');
        $this->post($verifyUrl)->assertRedirect('/fr/superadmin/dashboard');
        $dashboard = $this->get('/fr/superadmin/dashboard');

        $dashboard->assertViewIs('superadmin.dashboard');
    }

    /** Vérifie qu'une nouvelle demande d'accès invalide le lien précédent : seul le dernier lien ouvre la session. */
    public function testNewAccessRequestInvalidatesPreviousMagicLink(): void
    {
        Mail::fake();
        $this->post('/fr/superadmin/request-access', ['email' => self::SUPER_ADMIN_EMAIL]);
        $this->post('/fr/superadmin/request-access', ['email' => self::SUPER_ADMIN_EMAIL]);
        $sentMails = Mail::sent(
            SuperAdminMagicLinkMail::class,
            fn (SuperAdminMagicLinkMail $mail): bool => $mail->hasTo(self::SUPER_ADMIN_EMAIL)
        );
        $this->assertCount(2, $sentMails);

        $firstLinkResponse = $this->post($sentMails->first()->verifyUrl);
        $secondLinkResponse = $this->post($sentMails->last()->verifyUrl);

        $firstLinkResponse->assertViewIs('superadmin.invalid-link');
        $secondLinkResponse->assertRedirect('/fr/superadmin/dashboard');
    }

    /** Vérifie que POST verify consomme le lien, régénère l'ID de session, pose l'expiration et compte l'usage. */
    public function testVerifyPostConsumesLinkAndOpensFreshSession(): void
    {
        $this->travelTo('2026-09-15 10:00:00');
        $magicLink = MagicLink::factory()->superAdmin()->withToken(self::MAGIC_LINK_TOKEN)->create();
        $this->startSession();
        $sessionIdBeforeLogin = session()->getId();

        $response = $this->withCookie(config('session.cookie'), $sessionIdBeforeLogin)
            ->post('/fr/superadmin/verify/'.self::MAGIC_LINK_TOKEN);

        $response->assertRedirect('/fr/superadmin/dashboard');
        $response->assertSessionHas('super_admin_verified', true);
        $response->assertSessionHas('super_admin_expires_at', 1789467300);
        $this->assertNotSame($sessionIdBeforeLogin, session()->getId());

        $this->assertSame('2026-09-15 10:00:00', $magicLink->refresh()->used_at?->toDateTimeString());

        $this->assertDatabaseHas('stats_daily', [
            'date' => '2026-09-15',
            'metric' => StatsService::MAGIC_LINKS_USED,
            'count' => 1,
        ]);
        $this->assertDatabaseHas('stats_heatmap', [
            'date' => '2026-09-15',
            'hour' => 10,
            'metric' => StatsService::MAGIC_LINKS_USED,
            'count' => 1,
        ]);
    }

    /**
     * @return array<string, array{0: Closure(string): MagicLink}>
     */
    public static function unusableMagicLinks(): array
    {
        return [
            'jeton inconnu' => [fn (string $token): MagicLink => MagicLink::factory()->superAdmin()->create()],
            'lien expiré' => [fn (string $token): MagicLink => MagicLink::factory()->superAdmin()->withToken($token)->expired()->create()],
            'lien déjà utilisé' => [fn (string $token): MagicLink => MagicLink::factory()->superAdmin()->withToken($token)->used()->create()],
            'lien admin ordinaire' => [fn (string $token): MagicLink => MagicLink::factory()->withToken($token)->create()],
        ];
    }

    /**
     * Vérifie qu'un lien inutilisable affiche la page d'erreur sans ouvrir de session ni toucher au lien.
     *
     * @param  Closure(string): MagicLink  $createMagicLink
     */
    #[DataProvider('unusableMagicLinks')]
    public function testVerifyPostWithUnusableLinkShowsInvalidPage(Closure $createMagicLink): void
    {
        $this->freezeTime();
        $magicLink = $createMagicLink(self::MAGIC_LINK_TOKEN);
        $usedAtBefore = $magicLink->used_at?->toDateTimeString();
        $this->travel(5)->minutes();

        $response = $this->post('/fr/superadmin/verify/'.self::MAGIC_LINK_TOKEN);

        $response->assertViewIs('superadmin.invalid-link');
        $response->assertSessionMissing('super_admin_verified');

        $this->assertSame($usedAtBefore, $magicLink->refresh()->used_at?->toDateTimeString());
        $this->assertDatabaseMissing('stats_daily', ['metric' => StatsService::MAGIC_LINKS_USED]);
    }

    /** Vérifie qu'un lien consommé par une requête concurrente entre sa lecture et sa consommation n'ouvre pas de session. */
    public function testVerifyPostLosingConsumptionRaceShowsInvalidPage(): void
    {
        MagicLink::factory()->superAdmin()->withToken(self::MAGIC_LINK_TOKEN)->create();
        MagicLink::retrieved(function (MagicLink $magicLink): void {
            MagicLink::whereKey($magicLink->id)->update(['used_at' => now()]);
        });

        $response = $this->post('/fr/superadmin/verify/'.self::MAGIC_LINK_TOKEN);

        $response->assertViewIs('superadmin.invalid-link');
        $response->assertSessionMissing('super_admin_verified');

        $this->assertDatabaseMissing('stats_daily', ['metric' => StatsService::MAGIC_LINKS_USED]);
    }

    /** Vérifie que verify est limité à 5 tentatives par minute, en GET comme en POST. */
    public function testVerifyIsThrottledToFiveAttemptsPerMinute(): void
    {
        foreach (['get', 'post', 'get', 'post', 'get'] as $method) {
            $this->{$method}('/fr/superadmin/verify/unknown-token')->assertViewIs('superadmin.invalid-link');
        }

        $response = $this->post('/fr/superadmin/verify/unknown-token');

        $response->assertTooManyRequests();
    }

    /** Vérifie que le dashboard redirige vers l'index sans session. */
    public function testDashboardRedirectsToIndexWithoutSession(): void
    {
        $response = $this->get('/fr/superadmin/dashboard');

        $response->assertRedirect('/fr/superadmin');
    }

    /**
     * Vérifie qu'une session expirée ou sans expiration redirige vers l'index et oublie les deux clés.
     *
     * @param  Closure(): array<string, mixed>  $session
     */
    #[DataProvider('unusableSessions')]
    public function testDashboardRedirectsAndForgetsUnusableSession(Closure $session): void
    {
        $this->freezeTime();

        $response = $this->withSession($session())->get('/fr/superadmin/dashboard');

        $response->assertRedirect('/fr/superadmin');
        $response->assertSessionMissing('super_admin_verified');
        $response->assertSessionMissing('super_admin_expires_at');
    }

    /** Vérifie qu'un accès au dashboard prolonge l'expiration de la session superadmin. */
    public function testDashboardSlidesSessionExpiry(): void
    {
        Storage::fake('secrets');
        $this->travelTo('2026-09-15 10:00:00');
        $session = [
            'super_admin_verified' => true,
            'super_admin_expires_at' => now()->addMinutes(2)->timestamp,
        ];

        $response = $this->withSession($session)->get('/fr/superadmin/dashboard');

        $response->assertViewIs('superadmin.dashboard');
        $response->assertSessionHas('super_admin_expires_at', 1789467300);
    }

    /** Vérifie que le poll renvoie 401 sans session. */
    public function testPollReturns401WithoutSession(): void
    {
        $response = $this->getJson('/fr/superadmin/dashboard/poll');

        $response->assertUnauthorized();
        $response->assertExactJson(['error' => 'unauthenticated']);
    }

    /** Vérifie que la déconnexion oublie la session superadmin et régénère le jeton CSRF. */
    public function testLogoutForgetsSessionAndRegeneratesCsrfToken(): void
    {
        $this->withSession($this->superAdminSession());
        $csrfTokenBefore = session()->token();

        $response = $this->post('/fr/superadmin/logout');
        $csrfTokenAfter = session()->token();

        $response->assertRedirect('/fr/superadmin');
        $response->assertSessionMissing('super_admin_verified');
        $response->assertSessionMissing('super_admin_expires_at');
        $this->assertNotEmpty($csrfTokenAfter);
        $this->assertNotSame($csrfTokenBefore, $csrfTokenAfter);
    }

    private function holdDeferredCallbacks(): DeferredCallbackCollection
    {
        $deferred = new class () extends DeferredCallbackCollection {
            public function invokeWhen(?Closure $callback = null): void
            {
            }

            public function invoke(): void
            {
                parent::invokeWhen();
            }
        };

        $this->app->instance(DeferredCallbackCollection::class, $deferred);

        return $deferred;
    }
}
