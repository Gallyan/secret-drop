<?php

namespace Tests\Feature;

use App\Models\Secret;
use App\Services\StatsService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ShowSecretTest extends TestCase
{
    private const ADMIN_TOKEN = '0123456789abcdef0123456789abcdef';

    private const UNKNOWN_TOKEN = 'nonexistenttoken12345678901';

    /** Vérifie que la page de consultation affiche le token d'un secret existant. */
    public function testShowPageRendersTokenOfExistingSecret(): void
    {
        $secret = Secret::factory()->create();

        $response = $this->get("/s/{$secret->token}");

        $response->assertOk();
        $response->assertSee($secret->token);
    }

    /** Vérifie que la page de consultation répond 200 pour un token inconnu (pas de fuite d'existence). */
    public function testShowPageReturns200ForUnknownToken(): void
    {
        $response = $this->get('/s/'.self::UNKNOWN_TOKEN);

        $response->assertOk();
    }

    /** Vérifie que le fetch renvoie le chiffré et les métadonnées sans compter de lecture. */
    public function testApiFetchReturnsCiphertextAndCipherMetaWithoutCountingRead(): void
    {
        $secret = Secret::factory()->withPassphrase()->create();

        $response = $this->getJson("/api/secrets/{$secret->token}");

        $response->assertOk();
        $response->assertExactJson([
            'type' => 'text',
            'cipher_meta' => $secret->cipher_meta,
            'will_be_destroyed' => false,
            'ciphertext' => $secret->ciphertext,
        ]);

        $secret->refresh();
        $this->assertSame(0, $secret->read_count);
        $this->assertNull($secret->first_read_at);
    }

    /**
     * Vérifie que max_views reste coopératif : des fetch répétés sans /read ne consomment pas
     * un secret à usage unique (décision utilisateur, comportement figé).
     */
    public function testRepeatedFetchWithoutReadConfirmationDoesNotConsumeSingleUseSecret(): void
    {
        $secret = Secret::factory()->singleUse()->create();

        $this->getJson("/api/secrets/{$secret->token}")->assertOk();
        $this->getJson("/api/secrets/{$secret->token}")->assertOk();
        $this->getJson("/api/secrets/{$secret->token}")->assertOk()->assertJsonPath('ciphertext', $secret->ciphertext);

        $secret->refresh();
        $this->assertSame(0, $secret->read_count);
        $this->assertSame(3, $secret->fetch_count);
        $this->assertNotNull($secret->ciphertext);
    }

    /** Vérifie que le fetch d'un secret fichier renvoie uniquement les métadonnées sans incrémenter fetch_count. */
    public function testApiFetchReturnsOnlyMetadataForFileSecretWithoutCountingFetch(): void
    {
        $secret = Secret::factory()->file()->create();

        $response = $this->getJson("/api/secrets/{$secret->token}");

        $response->assertOk();
        $response->assertExactJson([
            'type' => 'file',
            'cipher_meta' => $secret->cipher_meta,
            'will_be_destroyed' => false,
        ]);

        $secret->refresh();
        $this->assertSame(0, $secret->fetch_count);
    }

    /** Vérifie que le fetch retourne 404 pour un token inconnu. */
    public function testApiFetchReturns404ForUnknownToken(): void
    {
        $response = $this->getJson('/api/secrets/'.self::UNKNOWN_TOKEN);

        $response->assertNotFound();
        $response->assertExactJson(['error' => 'not_found']);
    }

    /**
     * @return array<string, array{0: 'expired'|'revoked'|'consumed'}>
     */
    public static function inaccessibleStates(): array
    {
        return [
            'expiré' => ['expired'],
            'révoqué' => ['revoked'],
            'max_views atteint' => ['consumed'],
        ];
    }

    /**
     * Vérifie qu'un secret inaccessible renvoie un 404 uniforme sans incrémenter fetch_count.
     *
     * @param  'expired'|'revoked'|'consumed'  $state
     */
    #[DataProvider('inaccessibleStates')]
    public function testApiFetchReturns404WithoutCountingFetchForInaccessibleSecret(string $state): void
    {
        $secret = $this->createInaccessibleSecret($state);

        $response = $this->getJson("/api/secrets/{$secret->token}");

        $response->assertNotFound();
        $response->assertExactJson(['error' => 'not_found']);

        $secret->refresh();
        $this->assertSame(0, $secret->fetch_count);
    }

    /** Vérifie que chaque confirmation de lecture incrémente read_count et ne fixe first_read_at qu'une fois. */
    public function testApiConfirmReadIncrementsReadCountAndSetsFirstReadAtOnce(): void
    {
        $this->travelTo('2026-03-10 14:00:00');
        $secret = Secret::factory()->create();

        $this->travel(5)->minutes();
        $first = $this->postJson("/api/secrets/{$secret->token}/read");
        $this->travel(5)->minutes();
        $second = $this->postJson("/api/secrets/{$secret->token}/read");

        $first->assertOk()->assertExactJson(['success' => true]);
        $second->assertOk()->assertExactJson(['success' => true]);

        $secret->refresh();
        $this->assertSame(2, $secret->read_count);
        $this->assertSame('2026-03-10 14:05:00', $secret->first_read_at?->toDateTimeString());
        $this->assertSame('2026-03-10 14:10:00', $secret->last_read_at?->toDateTimeString());
        $this->assertNotNull($secret->ciphertext);
    }

    /** Vérifie que la confirmation de lecture retourne 404 pour un token inconnu. */
    public function testApiConfirmReadReturns404ForUnknownToken(): void
    {
        $response = $this->postJson('/api/secrets/'.self::UNKNOWN_TOKEN.'/read');

        $response->assertNotFound();
        $response->assertExactJson(['error' => 'not_found']);
    }

    /** Vérifie que la confirmation de lecture d'un secret expiré retourne 404 sans compter de lecture. */
    public function testApiConfirmReadReturns404ForExpiredSecret(): void
    {
        $secret = Secret::factory()->expired()->create();

        $response = $this->postJson("/api/secrets/{$secret->token}/read");

        $response->assertNotFound();
        $response->assertExactJson(['error' => 'not_found']);

        $secret->refresh();
        $this->assertSame(0, $secret->read_count);
        $this->assertNull($secret->first_read_at);
    }

    /** Vérifie qu'un secret à usage unique est détruit à la lecture puis renvoie 404 sans recompter. */
    public function testSingleUseSecretIsDestroyedOnReadThenReturns404(): void
    {
        $secret = Secret::factory()->singleUse()->create();

        $this->getJson("/api/secrets/{$secret->token}")
            ->assertOk()
            ->assertJsonPath('will_be_destroyed', true);
        $this->postJson("/api/secrets/{$secret->token}/read")->assertOk();

        $secret->refresh();
        $this->assertNull($secret->ciphertext);
        $this->assertSame(1, $secret->read_count);

        $this->getJson("/api/secrets/{$secret->token}")->assertNotFound();
        $this->postJson("/api/secrets/{$secret->token}/read")->assertNotFound();

        $secret->refresh();
        $this->assertSame(1, $secret->read_count);
    }

    /** Vérifie qu'un secret à max_views 3 garde son chiffré jusqu'à la 3e lecture et refuse la 4e. */
    public function testMaxViewsSecretKeepsCiphertextUntilLastAllowedRead(): void
    {
        $secret = Secret::factory()->withMaxViews(3)->create();
        $ciphertext = $secret->ciphertext;

        $this->getJson("/api/secrets/{$secret->token}")->assertJsonPath('will_be_destroyed', false);
        $this->postJson("/api/secrets/{$secret->token}/read")->assertOk();
        $this->getJson("/api/secrets/{$secret->token}")->assertJsonPath('will_be_destroyed', false);
        $this->postJson("/api/secrets/{$secret->token}/read")->assertOk();

        $secret->refresh();
        $this->assertSame($ciphertext, $secret->ciphertext);

        $this->getJson("/api/secrets/{$secret->token}")->assertJsonPath('will_be_destroyed', true);
        $this->postJson("/api/secrets/{$secret->token}/read")->assertOk();
        $this->postJson("/api/secrets/{$secret->token}/read")->assertNotFound();

        $secret->refresh();
        $this->assertNull($secret->ciphertext);
        $this->assertSame(3, $secret->read_count);
    }

    /** Vérifie que la lecture d'un fichier à usage unique supprime le blob, vide file_path et invalide l'usage disque en cache. */
    public function testSingleUseFileSecretBlobIsDeletedOnRead(): void
    {
        Storage::fake('secrets');
        Cache::put('disk_usage_secrets', 123, 3600);
        $secret = Secret::factory()->singleUse()->withStoredBlob()->create();
        $filePath = (string) $secret->file_path;

        $this->postJson("/api/secrets/{$secret->token}/read")->assertOk();

        Storage::disk('secrets')->assertMissing($filePath);
        $this->assertFalse(Cache::has('disk_usage_secrets'));

        $secret->refresh();
        $this->assertNull($secret->file_path);
        $this->assertSame(1, $secret->read_count);
    }

    /**
     * @return array<string, array{0: ?int}>
     */
    public static function remainingViewsLimits(): array
    {
        return [
            'max_views 2' => [2],
            'sans limite' => [null],
        ];
    }

    /** Vérifie que la lecture d'un fichier conserve le blob tant qu'il reste des vues. */
    #[DataProvider('remainingViewsLimits')]
    public function testFileSecretBlobIsKeptOnReadWhileViewsRemain(?int $maxViews): void
    {
        Storage::fake('secrets');
        $secret = Secret::factory()->withStoredBlob('encrypted-bytes')->create(['max_views' => $maxViews]);
        $filePath = (string) $secret->file_path;

        $this->postJson("/api/secrets/{$secret->token}/read")->assertOk();

        Storage::disk('secrets')->assertExists($filePath, 'encrypted-bytes');

        $secret->refresh();
        $this->assertSame($filePath, $secret->file_path);
        $this->assertSame(1, $secret->read_count);
    }

    /** Vérifie que la dernière lecture d'un fichier dont le blob a disparu détruit le contenu sans erreur. */
    public function testReadOfFileSecretWithMissingBlobDestroysContentWithoutError(): void
    {
        Storage::fake('secrets');
        $secret = Secret::factory()->singleUse()->file()->create();

        $response = $this->postJson("/api/secrets/{$secret->token}/read");

        $response->assertOk();
        $response->assertExactJson(['success' => true]);

        $secret->refresh();
        $this->assertNull($secret->file_path);
        $this->assertSame(1, $secret->read_count);
    }

    /** Vérifie que la révocation détruit le chiffré, date la révocation, rend le secret inaccessible et compte la stat. */
    public function testRevokeDestroysCiphertextMarksRevokedAndTracksStat(): void
    {
        $this->travelTo('2026-03-10 14:00:00');
        $secret = Secret::factory()->withAdminToken(self::ADMIN_TOKEN)->create();

        $response = $this->postJson('/api/secrets/'.self::ADMIN_TOKEN.'/revoke');

        $response->assertOk();
        $response->assertExactJson(['success' => true]);

        $secret->refresh();
        $this->assertSame('2026-03-10 14:00:00', $secret->revoked_at?->toDateTimeString());
        $this->assertNull($secret->ciphertext);

        $this->assertDatabaseHas('stats_daily', [
            'date' => '2026-03-10',
            'metric' => StatsService::SECRETS_REVOKED,
            'count' => 1,
        ]);

        $this->getJson("/api/secrets/{$secret->token}")->assertNotFound();
        $this->postJson("/api/secrets/{$secret->token}/read")->assertNotFound();
    }

    /** Vérifie que la révocation d'un secret fichier supprime son blob et invalide l'usage disque en cache. */
    public function testRevokeDeletesStoredBlobOfFileSecret(): void
    {
        Storage::fake('secrets');
        Cache::put('disk_usage_secrets', 123, 3600);
        $secret = Secret::factory()->withStoredBlob()->withAdminToken(self::ADMIN_TOKEN)->create();
        $filePath = (string) $secret->file_path;

        $this->postJson('/api/secrets/'.self::ADMIN_TOKEN.'/revoke')->assertOk();

        Storage::disk('secrets')->assertMissing($filePath);
        $this->assertFalse(Cache::has('disk_usage_secrets'));

        $secret->refresh();
        $this->assertNotNull($secret->revoked_at);
        $this->assertNull($secret->file_path);
    }

    /** Vérifie que la révocation retourne 404 pour un admin token inconnu. */
    public function testRevokeReturns404ForUnknownAdminToken(): void
    {
        $response = $this->postJson('/api/secrets/invalidtoken123/revoke');

        $response->assertNotFound();
        $response->assertExactJson(['error' => 'not_found']);
    }

    /** Vérifie que la révocation retourne 409 already_revoked pour un secret déjà révoqué, sans stat. */
    public function testRevokeReturns409ForAlreadyRevokedSecret(): void
    {
        $this->travelTo('2026-03-10 14:00:00');
        $secret = Secret::factory()->revoked()->withAdminToken(self::ADMIN_TOKEN)->create();
        $this->travel(1)->hour();

        $response = $this->postJson('/api/secrets/'.self::ADMIN_TOKEN.'/revoke');

        $response->assertConflict();
        $response->assertExactJson(['error' => 'already_revoked']);

        $secret->refresh();
        $this->assertSame('2026-03-10 14:00:00', $secret->revoked_at?->toDateTimeString());
        $this->assertDatabaseMissing('stats_daily', ['metric' => StatsService::SECRETS_REVOKED]);
    }

    /** Vérifie que la révocation retourne 409 already_consumed pour un secret dont les vues sont épuisées, sans stat. */
    public function testRevokeReturns409ForConsumedSecret(): void
    {
        $secret = Secret::factory()->consumed()->withAdminToken(self::ADMIN_TOKEN)->create();

        $response = $this->postJson('/api/secrets/'.self::ADMIN_TOKEN.'/revoke');

        $response->assertConflict();
        $response->assertExactJson(['error' => 'already_consumed']);

        $secret->refresh();
        $this->assertNull($secret->revoked_at);
        $this->assertDatabaseMissing('stats_daily', ['metric' => StatsService::SECRETS_REVOKED]);
    }

    /**
     * @param  'expired'|'revoked'|'consumed'  $state
     */
    private function createInaccessibleSecret(string $state): Secret
    {
        $factory = Secret::factory();

        return match ($state) {
            'expired' => $factory->expired()->create(),
            'revoked' => $factory->revoked()->create(),
            'consumed' => $factory->consumed()->create(),
        };
    }
}
