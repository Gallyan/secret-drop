<?php

namespace Tests\Feature;

use App\Mail\MagicLinkMail;
use App\Models\MagicLink;
use App\Models\Secret;
use App\Services\StatsService;
use Closure;
use Database\Factories\SecretFactory;
use Illuminate\Support\Defer\DeferredCallbackCollection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Sleep;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AdminControllerTest extends TestCase
{
    private const OWNER_EMAIL = 'owner@example.com';

    private const MAGIC_LINK_TOKEN = 'plain-magic-link-token-for-tests';

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('secrets.magic_link_ttl', 10);
        Config::set('secrets.admin_session_ttl', 15);
    }

    /** Vérifie que l'index affiche le formulaire de connexion sans session. */
    public function testIndexRendersLoginFormWithoutSession(): void
    {
        $response = $this->get('/fr/admin');

        $response->assertViewIs('admin.index');
    }

    /** Vérifie que l'index redirige vers le dashboard quand la session admin est valide. */
    public function testIndexRedirectsToDashboardWhenSessionIsValid(): void
    {
        $response = $this->withSession($this->adminSession(self::OWNER_EMAIL))->get('/fr/admin');

        $response->assertRedirect('/fr/admin/dashboard');
    }

    /**
     * @return array<string, array{0: Closure(): array<string, mixed>}>
     */
    public static function unusableSessions(): array
    {
        return [
            'sans date d\'expiration' => [fn (): array => [
                'admin_email_hash' => MagicLink::hashEmail(self::OWNER_EMAIL),
            ]],
            'expirée' => [fn (): array => [
                'admin_email_hash' => MagicLink::hashEmail(self::OWNER_EMAIL),
                'admin_expires_at' => now()->subSecond()->timestamp,
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

        $response = $this->withSession($session())->get('/fr/admin');

        $response->assertViewIs('admin.index');
    }

    /** Vérifie qu'une demande d'accès pour un email propriétaire envoie le lien, le persiste et compte la demande. */
    public function testRequestAccessSendsMagicLinkToSecretOwner(): void
    {
        $this->travelTo('2026-09-15 14:30:00');
        Mail::fake();
        Secret::factory()->withCreatorEmail(self::OWNER_EMAIL)->create();

        $response = $this->post('/fr/admin/request-access', ['email' => self::OWNER_EMAIL]);

        $response->assertRedirect('/fr/admin/access-sent');

        Mail::assertSent(MagicLinkMail::class, fn (MagicLinkMail $mail): bool => $mail->hasTo(self::OWNER_EMAIL));

        $magicLink = MagicLink::sole();
        $this->assertSame(MagicLink::hashEmail(self::OWNER_EMAIL), $magicLink->email_hash);
        $this->assertSame('2026-09-15 14:40:00', $magicLink->expire_at->toDateTimeString());
        $this->assertNull($magicLink->used_at);

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

    /** Vérifie qu'un email sans secret ne reçoit rien et obtient la même réponse, sans délai artificiel. */
    public function testRequestAccessForUnknownEmailSendsNothing(): void
    {
        Mail::fake();
        Sleep::fake();

        $response = $this->post('/fr/admin/request-access', ['email' => 'nobody@example.com']);

        $response->assertRedirect('/fr/admin/access-sent');

        Mail::assertNothingSent();

        $this->assertDatabaseCount('magic_links', 0);
        $this->assertDatabaseMissing('stats_daily', ['metric' => StatsService::MAGIC_LINKS_REQUESTED]);

        Sleep::assertNeverSlept();
    }

    /** Vérifie que la recherche du propriétaire et l'envoi n'ont lieu qu'après la réponse, identique dans tous les cas. */
    public function testRequestAccessDefersAllRecipientWorkAfterTheResponse(): void
    {
        Mail::fake();
        Secret::factory()->withCreatorEmail(self::OWNER_EMAIL)->create();
        $deferred = $this->holdDeferredCallbacks();

        $ownerResponse = $this->post('/fr/admin/request-access', ['email' => self::OWNER_EMAIL]);
        $unknownResponse = $this->post('/fr/admin/request-access', ['email' => 'nobody@example.com']);

        $this->assertSame($ownerResponse->getStatusCode(), $unknownResponse->getStatusCode());
        $this->assertSame($ownerResponse->headers->get('Location'), $unknownResponse->headers->get('Location'));

        Mail::assertNothingSent();

        $this->assertDatabaseCount('magic_links', 0);

        $deferred->invoke();

        Mail::assertSent(MagicLinkMail::class, 1);
        Mail::assertSent(MagicLinkMail::class, fn (MagicLinkMail $mail): bool => $mail->hasTo(self::OWNER_EMAIL));

        $this->assertDatabaseCount('magic_links', 1);
    }

    /** Vérifie que les envois vers un même propriétaire sont bornés par heure, quelle que soit l'IP, sans changer la réponse. */
    public function testRequestAccessIsLimitedPerRecipientRegardlessOfIp(): void
    {
        Config::set('secrets.magic_link_max_per_recipient_per_hour', 2);
        Mail::fake();
        Secret::factory()->withCreatorEmail(self::OWNER_EMAIL)->create();
        Secret::factory()->withCreatorEmail('other-owner@example.com')->create();

        foreach (['203.0.113.1', '203.0.113.2', '203.0.113.3'] as $ip) {
            $this->withServerVariables(['REMOTE_ADDR' => $ip])
                ->post('/fr/admin/request-access', ['email' => self::OWNER_EMAIL])
                ->assertRedirect('/fr/admin/access-sent')
                ->assertSessionHasNoErrors();
        }

        Mail::assertSent(MagicLinkMail::class, 2);

        $this->assertSame(2, MagicLink::where('email_hash', MagicLink::hashEmail(self::OWNER_EMAIL))->count());

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.4'])
            ->post('/fr/admin/request-access', ['email' => 'other-owner@example.com']);

        Mail::assertSent(MagicLinkMail::class, 3);

        $this->travel(61)->minutes();

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.5'])
            ->post('/fr/admin/request-access', ['email' => self::OWNER_EMAIL]);

        Mail::assertSent(MagicLinkMail::class, 4);
    }

    /** Vérifie qu'une limite par destinataire à zéro désactive la borne. */
    public function testRequestAccessRecipientLimitDisabledWhenZero(): void
    {
        Config::set('secrets.magic_link_max_per_recipient_per_hour', 0);
        Mail::fake();
        Secret::factory()->withCreatorEmail(self::OWNER_EMAIL)->create();

        foreach (['203.0.113.1', '203.0.113.2', '203.0.113.3', '203.0.113.4'] as $ip) {
            $this->withServerVariables(['REMOTE_ADDR' => $ip])
                ->post('/fr/admin/request-access', ['email' => self::OWNER_EMAIL]);
        }

        Mail::assertSent(MagicLinkMail::class, 4);
    }

    /** Vérifie qu'une demande faite depuis /en produit un mail rendu en anglais. */
    public function testRequestAccessFromEnglishPageSendsEnglishMail(): void
    {
        Mail::fake();
        Secret::factory()->withCreatorEmail(self::OWNER_EMAIL)->create();

        $this->post('/en/admin/request-access', ['email' => self::OWNER_EMAIL]);

        $mail = Mail::sent(MagicLinkMail::class, fn (MagicLinkMail $mail): bool => $mail->hasTo(self::OWNER_EMAIL))->sole();
        $this->assertSame('en', $mail->locale);
        $this->assertStringContainsString(e(__('messages.email_magic_link_button', [], 'en')), $mail->render());
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
     * Vérifie que chaque règle de validation de l'email affiche son message sur le formulaire.
     *
     * @param  array<string, string>  $payload
     */
    #[DataProvider('invalidAccessRequests')]
    public function testRequestAccessRejectsInvalidEmailWithVisibleMessage(array $payload, string $messageKey): void
    {
        Mail::fake();

        $response = $this->from('/fr/admin')
            ->followingRedirects()
            ->post('/fr/admin/request-access', $payload);

        $response->assertViewIs('admin.index');
        $response->assertSee(__($messageKey));

        Mail::assertNothingSent();

        $this->assertDatabaseCount('magic_links', 0);
    }

    /** Vérifie que GET verify affiche la confirmation sans consommer le lien (protection contre les scanners de mail). */
    public function testVerifyGetShowsConfirmationWithoutConsumingLink(): void
    {
        $magicLink = MagicLink::factory()->forEmail(self::OWNER_EMAIL)->withToken(self::MAGIC_LINK_TOKEN)->create();

        $response = $this->get('/fr/admin/verify/'.self::MAGIC_LINK_TOKEN);

        $response->assertViewIs('admin.verify-confirm');
        $response->assertViewHas('token', self::MAGIC_LINK_TOKEN);
        $response->assertSessionMissing('admin_email_hash');

        $this->assertNull($magicLink->refresh()->used_at);
    }

    /** Vérifie que le lien réellement envoyé par mail ouvre la session admin du destinataire. */
    public function testFollowingTheMailedLinkOpensAdminSession(): void
    {
        Mail::fake();
        $secret = Secret::factory()->withCreatorEmail(self::OWNER_EMAIL)->create();
        $this->post('/fr/admin/request-access', ['email' => self::OWNER_EMAIL]);
        $verifyUrl = Mail::sent(MagicLinkMail::class, fn (MagicLinkMail $mail): bool => $mail->hasTo(self::OWNER_EMAIL))
            ->sole()
            ->verifyUrl;

        $this->get($verifyUrl)->assertViewIs('admin.verify-confirm');
        $this->post($verifyUrl)->assertRedirect('/fr/admin/dashboard');
        $dashboard = $this->get('/fr/admin/dashboard');

        $dashboard->assertViewIs('admin.dashboard');
        $dashboard->assertSee("data-secret-id=\"{$secret->id}\"", false);
    }

    /** Vérifie que POST verify consomme le lien, régénère l'ID de session, pose l'expiration et compte l'usage. */
    public function testVerifyPostConsumesLinkAndOpensFreshSession(): void
    {
        $this->travelTo('2026-09-15 10:00:00');
        $magicLink = MagicLink::factory()->forEmail(self::OWNER_EMAIL)->withToken(self::MAGIC_LINK_TOKEN)->create();
        $this->startSession();
        $sessionIdBeforeLogin = session()->getId();

        $response = $this->withCookie(config('session.cookie'), $sessionIdBeforeLogin)
            ->post('/fr/admin/verify/'.self::MAGIC_LINK_TOKEN);

        $response->assertRedirect('/fr/admin/dashboard');
        $response->assertSessionHas('admin_email_hash', MagicLink::hashEmail(self::OWNER_EMAIL));
        $response->assertSessionHas('admin_expires_at', 1789467300);
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
            'jeton inconnu' => [fn (string $token): MagicLink => MagicLink::factory()->forEmail(self::OWNER_EMAIL)->create()],
            'lien expiré' => [fn (string $token): MagicLink => MagicLink::factory()->forEmail(self::OWNER_EMAIL)->withToken($token)->expired()->create()],
            'lien déjà utilisé' => [fn (string $token): MagicLink => MagicLink::factory()->forEmail(self::OWNER_EMAIL)->withToken($token)->used()->create()],
            'lien superadmin' => [fn (string $token): MagicLink => MagicLink::factory()->superAdmin()->withToken($token)->create()],
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

        $response = $this->post('/fr/admin/verify/'.self::MAGIC_LINK_TOKEN);

        $response->assertViewIs('admin.invalid-link');
        $response->assertSessionMissing('admin_email_hash');

        $this->assertSame($usedAtBefore, $magicLink->refresh()->used_at?->toDateTimeString());
        $this->assertDatabaseMissing('stats_daily', ['metric' => StatsService::MAGIC_LINKS_USED]);
    }

    /** Vérifie qu'un lien consommé par une requête concurrente entre sa lecture et sa consommation n'ouvre pas de session. */
    public function testVerifyPostLosingConsumptionRaceShowsInvalidPage(): void
    {
        MagicLink::factory()->forEmail(self::OWNER_EMAIL)->withToken(self::MAGIC_LINK_TOKEN)->create();
        MagicLink::retrieved(function (MagicLink $magicLink): void {
            MagicLink::whereKey($magicLink->id)->update(['used_at' => now()]);
        });

        $response = $this->post('/fr/admin/verify/'.self::MAGIC_LINK_TOKEN);

        $response->assertViewIs('admin.invalid-link');
        $response->assertSessionMissing('admin_email_hash');

        $this->assertDatabaseMissing('stats_daily', ['metric' => StatsService::MAGIC_LINKS_USED]);
    }

    /** Vérifie que verify est limité à 5 tentatives par minute, en GET comme en POST. */
    public function testVerifyIsThrottledToFiveAttemptsPerMinute(): void
    {
        foreach (['get', 'post', 'get', 'post', 'get'] as $method) {
            $this->{$method}('/fr/admin/verify/unknown-token')->assertViewIs('admin.invalid-link');
        }

        $response = $this->post('/fr/admin/verify/unknown-token');

        $response->assertTooManyRequests();
    }

    /** Vérifie que le dashboard redirige vers l'index sans session. */
    public function testDashboardRedirectsToIndexWithoutSession(): void
    {
        $response = $this->get('/fr/admin/dashboard');

        $response->assertRedirect('/fr/admin');
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

        $response = $this->withSession($session())->get('/fr/admin/dashboard');

        $response->assertRedirect('/fr/admin');
        $response->assertSessionMissing('admin_email_hash');
        $response->assertSessionMissing('admin_expires_at');
    }

    /** Vérifie que le dashboard n'affiche que les secrets du propriétaire et prolonge la session. */
    public function testDashboardShowsOwnSecretsAndSlidesSessionExpiry(): void
    {
        $this->travelTo('2026-09-15 10:00:00');
        $ownSecret = Secret::factory()->withCreatorEmail(self::OWNER_EMAIL)->create();
        $otherSecret = Secret::factory()->withCreatorEmail('someone-else@example.com')->create();
        $session = [
            'admin_email_hash' => MagicLink::hashEmail(self::OWNER_EMAIL),
            'admin_expires_at' => now()->addMinutes(2)->timestamp,
        ];

        $response = $this->withSession($session)->get('/fr/admin/dashboard');

        $response->assertViewIs('admin.dashboard');
        $response->assertSee("data-secret-id=\"{$ownSecret->id}\"", false);
        $response->assertDontSee($otherSecret->id);
        $response->assertSessionHas('admin_expires_at', 1789467300);
    }

    /** Vérifie que le dashboard affiche « sans expiration » pour un secret dont expire_at est nul. */
    public function testDashboardRendersSecretWithoutExpiry(): void
    {
        $secret = Secret::factory()->withCreatorEmail(self::OWNER_EMAIL)->withoutExpiry()->create();

        $response = $this->withSession($this->adminSession(self::OWNER_EMAIL))->get('/fr/admin/dashboard');

        $response->assertViewIs('admin.dashboard');
        $response->assertSee("data-secret-id=\"{$secret->id}\"", false);
        $response->assertSee('<span data-utc="" data-empty-label="'.e(__('messages.admin_no_expiry')).'">'.e(__('messages.admin_no_expiry')).'</span>', false);
    }

    /** Vérifie que le poll renvoie 401 sans session. */
    public function testPollReturns401WithoutSession(): void
    {
        $response = $this->getJson('/fr/admin/dashboard/poll');

        $response->assertUnauthorized();
        $response->assertExactJson(['error' => 'unauthenticated']);
    }

    /** Vérifie que le poll expose l'état exact des secrets du propriétaire, sans ceux des autres. */
    public function testPollReturnsStatusOfOwnSecretsOnly(): void
    {
        $this->travelTo('2026-09-15 10:00:00');
        $secret = Secret::factory()->withCreatorEmail(self::OWNER_EMAIL)->singleUse()->create();
        $secret->incrementReadCount();
        $secret->recordFetch();
        $secret->recordFetch();
        Secret::factory()->withCreatorEmail('someone-else@example.com')->create();

        $response = $this->withSession($this->adminSession(self::OWNER_EMAIL))->getJson('/fr/admin/dashboard/poll');

        $response->assertExactJson([
            'total' => 1,
            'secrets' => [[
                'id' => $secret->id,
                'read_count' => 1,
                'fetch_count' => 2,
                'max_views' => 1,
                'first_read_at' => '2026-09-15T10:00:00+00:00',
                'expire_at' => '2026-09-22T10:00:00+00:00',
                'is_revoked' => false,
                'is_expired' => false,
                'has_reached_max_views' => true,
                'is_accessible' => false,
            ]],
            'new_cards_html' => [],
        ]);
    }

    /** Vérifie que le poll pagine selon page et ne rend que les cartes absentes de known, y compris sans expiration. */
    public function testPollRendersCardsOnlyForUnknownSecretsOfRequestedPage(): void
    {
        $this->travelTo('2026-09-15 10:00:00');
        Secret::factory()
            ->count(5)
            ->withCreatorEmail(self::OWNER_EMAIL)
            ->sequence(fn ($sequence): array => ['created_at' => now()->subMinutes($sequence->index)])
            ->create();
        $knownOnSecondPage = Secret::factory()->withCreatorEmail(self::OWNER_EMAIL)->create(['created_at' => now()->subHour()]);
        $unknownOnSecondPage = Secret::factory()->withCreatorEmail(self::OWNER_EMAIL)->withoutExpiry()->create(['created_at' => now()->subHours(2)]);

        $response = $this->withSession($this->adminSession(self::OWNER_EMAIL))
            ->getJson("/fr/admin/dashboard/poll?page=2&known={$knownOnSecondPage->id}");

        $response->assertJsonPath('total', 7);
        $response->assertJsonPath('secrets.*.id', [$knownOnSecondPage->id, $unknownOnSecondPage->id]);
        $this->assertSame([$unknownOnSecondPage->id], array_keys($response->json('new_cards_html')));
        $this->assertStringContainsString(
            "data-secret-id=\"{$unknownOnSecondPage->id}\"",
            $response->json("new_cards_html.{$unknownOnSecondPage->id}")
        );
    }

    /** Vérifie que la révocation renvoie 401 sans session et laisse le secret intact. */
    public function testRevokeReturns401WithoutSession(): void
    {
        $secret = Secret::factory()->withCreatorEmail(self::OWNER_EMAIL)->create();

        $response = $this->postJson("/fr/admin/secrets/{$secret->id}/revoke");

        $response->assertUnauthorized();

        $this->assertNull($secret->refresh()->revoked_at);
    }

    /** Vérifie que la révocation d'un secret texte le marque révoqué, détruit son contenu et la compte. */
    public function testRevokeMarksTextSecretRevokedAndDestroysContent(): void
    {
        $this->travelTo('2026-09-15 10:00:00');
        $secret = Secret::factory()->withCreatorEmail(self::OWNER_EMAIL)->text()->create();

        $response = $this->withSession($this->adminSession(self::OWNER_EMAIL))
            ->postJson("/fr/admin/secrets/{$secret->id}/revoke");

        $response->assertExactJson(['success' => true]);

        $secret->refresh();
        $this->assertSame('2026-09-15 10:00:00', $secret->revoked_at?->toDateTimeString());
        $this->assertNull($secret->ciphertext);

        $this->assertDatabaseHas('stats_daily', [
            'date' => '2026-09-15',
            'metric' => StatsService::SECRETS_REVOKED,
            'count' => 1,
        ]);
    }

    /** Vérifie que la révocation d'un secret fichier supprime le blob du disque et invalide l'usage disque en cache. */
    public function testRevokeDeletesFileSecretBlob(): void
    {
        Storage::fake('secrets');
        Cache::put('disk_usage_secrets', 123, 3600);
        $secret = Secret::factory()->withCreatorEmail(self::OWNER_EMAIL)->withStoredBlob()->create();
        $blobPath = (string) $secret->file_path;

        $response = $this->withSession($this->adminSession(self::OWNER_EMAIL))
            ->postJson("/fr/admin/secrets/{$secret->id}/revoke");

        $response->assertExactJson(['success' => true]);

        $secret->refresh();
        $this->assertNotNull($secret->revoked_at);
        $this->assertNull($secret->file_path);

        Storage::disk('secrets')->assertMissing($blobPath);
        $this->assertFalse(Cache::has('disk_usage_secrets'));
    }

    /**
     * @return array<string, array{0: Closure(): SecretFactory, 1: string}>
     */
    public static function unrevokableSecrets(): array
    {
        return [
            'déjà révoqué' => [fn (): SecretFactory => Secret::factory()->revoked(), 'already_revoked'],
            'déjà consommé' => [fn (): SecretFactory => Secret::factory()->consumed(), 'already_consumed'],
        ];
    }

    /**
     * Vérifie qu'un secret déjà révoqué ou consommé renvoie 409 sans être modifié.
     *
     * @param  Closure(): SecretFactory  $secretFactory
     */
    #[DataProvider('unrevokableSecrets')]
    public function testRevokeReturns409ForUnrevokableSecret(Closure $secretFactory, string $error): void
    {
        $this->freezeTime();
        $secret = $secretFactory()->withCreatorEmail(self::OWNER_EMAIL)->create();
        $revokedAtBefore = $secret->revoked_at?->toDateTimeString();
        $this->travel(5)->minutes();

        $response = $this->withSession($this->adminSession(self::OWNER_EMAIL))
            ->postJson("/fr/admin/secrets/{$secret->id}/revoke");

        $response->assertConflict();
        $response->assertExactJson(['error' => $error]);

        $this->assertSame($revokedAtBefore, $secret->refresh()->revoked_at?->toDateTimeString());
        $this->assertDatabaseMissing('stats_daily', ['metric' => StatsService::SECRETS_REVOKED]);
    }

    /** Vérifie qu'un admin ne peut pas révoquer le secret d'un autre email : 404 et secret intact. */
    public function testRevokeOfAnotherOwnersSecretReturns404AndLeavesItIntact(): void
    {
        $secret = Secret::factory()->withCreatorEmail('victim@example.com')->text()->create();
        $ciphertext = $secret->ciphertext;

        $response = $this->withSession($this->adminSession('attacker@example.com'))
            ->postJson("/fr/admin/secrets/{$secret->id}/revoke");

        $response->assertNotFound();
        $response->assertExactJson(['error' => 'not_found']);

        $secret->refresh();
        $this->assertNull($secret->revoked_at);
        $this->assertSame($ciphertext, $secret->ciphertext);
    }

    /** Vérifie que la prolongation renvoie 401 sans session et laisse l'expiration intacte. */
    public function testExtendReturns401WithoutSession(): void
    {
        $this->freezeTime();
        $secret = Secret::factory()->withCreatorEmail(self::OWNER_EMAIL)->create();
        $expireAtBefore = $secret->expire_at?->toDateTimeString();

        $response = $this->postJson("/fr/admin/secrets/{$secret->id}/extend", ['hours' => 24]);

        $response->assertUnauthorized();

        $this->assertSame($expireAtBefore, $secret->refresh()->expire_at?->toDateTimeString());
    }

    /**
     * @return array<string, array{0: array<string, mixed>, 1: string}>
     */
    public static function invalidExtensions(): array
    {
        return [
            'heures absentes' => [[], 'messages.val_hours_required'],
            'heures non numériques' => [['hours' => 'abc'], 'messages.val_hours_integer'],
            'zéro heure' => [['hours' => 0], 'messages.val_hours_min'],
            'une heure au-delà du maximum' => [['hours' => 721], 'messages.val_hours_max'],
        ];
    }

    /**
     * Vérifie que chaque règle de validation des heures renvoie 422 avec son message traduit.
     *
     * @param  array<string, mixed>  $payload
     */
    #[DataProvider('invalidExtensions')]
    public function testExtendRejectsInvalidHoursWithTranslatedMessage(array $payload, string $messageKey): void
    {
        $this->freezeTime();
        $secret = Secret::factory()->withCreatorEmail(self::OWNER_EMAIL)->create();
        $expireAtBefore = $secret->expire_at?->toDateTimeString();

        $response = $this->withSession($this->adminSession(self::OWNER_EMAIL))
            ->postJson("/fr/admin/secrets/{$secret->id}/extend", $payload);

        $response->assertUnprocessable();
        $response->assertOnlyJsonValidationErrors(['hours' => __($messageKey)]);
        $this->assertNotSame($messageKey, __($messageKey));

        $this->assertSame($expireAtBefore, $secret->refresh()->expire_at?->toDateTimeString());
    }

    /**
     * @return array<string, array{0: Closure(): SecretFactory, 1: string}>
     */
    public static function extensionBaseDates(): array
    {
        return [
            'expiration future : ajout à l\'expiration' => [fn (): SecretFactory => Secret::factory()->expiresIn(2), '2026-09-16 12:00:00'],
            'expiration passée : ajout à maintenant' => [fn (): SecretFactory => Secret::factory()->expired(), '2026-09-16 10:00:00'],
            'sans expiration : ajout à maintenant' => [fn (): SecretFactory => Secret::factory()->withoutExpiry(), '2026-09-16 10:00:00'],
        ];
    }

    /**
     * Vérifie que la prolongation ajoute les heures à la bonne base, renvoie la date et compte l'action.
     *
     * @param  Closure(): SecretFactory  $secretFactory
     */
    #[DataProvider('extensionBaseDates')]
    public function testExtendAddsHoursToTheRightBaseDate(Closure $secretFactory, string $expectedExpireAt): void
    {
        $this->travelTo('2026-09-15 10:00:00');
        $secret = $secretFactory()->withCreatorEmail(self::OWNER_EMAIL)->create();

        $response = $this->withSession($this->adminSession(self::OWNER_EMAIL))
            ->postJson("/fr/admin/secrets/{$secret->id}/extend", ['hours' => 24]);

        $response->assertExactJson([
            'success' => true,
            'expire_at' => str_replace(' ', 'T', $expectedExpireAt).'+00:00',
        ]);

        $this->assertSame($expectedExpireAt, $secret->refresh()->expire_at?->toDateTimeString());

        $this->assertDatabaseHas('stats_daily', [
            'date' => '2026-09-15',
            'metric' => StatsService::SECRETS_EXTENDED,
            'count' => 1,
        ]);
        $this->assertDatabaseHas('stats_heatmap', [
            'date' => '2026-09-15',
            'hour' => 10,
            'metric' => StatsService::SECRETS_EXTENDED,
            'count' => 1,
        ]);
    }

    /** Vérifie qu'un secret révoqué ne peut pas être prolongé : 409 et expiration intacte. */
    public function testExtendReturns409ForRevokedSecret(): void
    {
        $this->freezeTime();
        $secret = Secret::factory()->withCreatorEmail(self::OWNER_EMAIL)->revoked()->create();
        $expireAtBefore = $secret->expire_at?->toDateTimeString();

        $response = $this->withSession($this->adminSession(self::OWNER_EMAIL))
            ->postJson("/fr/admin/secrets/{$secret->id}/extend", ['hours' => 24]);

        $response->assertConflict();
        $response->assertExactJson(['error' => 'revoked']);

        $this->assertSame($expireAtBefore, $secret->refresh()->expire_at?->toDateTimeString());
        $this->assertDatabaseMissing('stats_daily', ['metric' => StatsService::SECRETS_EXTENDED]);
    }

    /** Vérifie qu'un admin ne peut pas prolonger le secret d'un autre email : 404 et expiration intacte. */
    public function testExtendOfAnotherOwnersSecretReturns404AndLeavesExpiryIntact(): void
    {
        $this->freezeTime();
        $secret = Secret::factory()->withCreatorEmail('victim@example.com')->create();
        $expireAtBefore = $secret->expire_at?->toDateTimeString();

        $response = $this->withSession($this->adminSession('attacker@example.com'))
            ->postJson("/fr/admin/secrets/{$secret->id}/extend", ['hours' => 24]);

        $response->assertNotFound();
        $response->assertExactJson(['error' => 'not_found']);

        $this->assertSame($expireAtBefore, $secret->refresh()->expire_at?->toDateTimeString());
    }

    /** Vérifie que la déconnexion oublie la session admin et régénère le jeton CSRF. */
    public function testLogoutForgetsSessionAndRegeneratesCsrfToken(): void
    {
        $this->withSession($this->adminSession(self::OWNER_EMAIL));
        $csrfTokenBefore = session()->token();

        $response = $this->post('/fr/admin/logout');
        $csrfTokenAfter = session()->token();

        $response->assertRedirect('/fr/admin');
        $response->assertSessionMissing('admin_email_hash');
        $response->assertSessionMissing('admin_expires_at');
        $this->assertNotEmpty($csrfTokenAfter);
        $this->assertNotSame($csrfTokenBefore, $csrfTokenAfter);
    }

    /**
     * @return array{admin_email_hash: string, admin_expires_at: int}
     */
    private function adminSession(string $email): array
    {
        return [
            'admin_email_hash' => MagicLink::hashEmail($email),
            'admin_expires_at' => now()->addMinutes(15)->timestamp,
        ];
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
