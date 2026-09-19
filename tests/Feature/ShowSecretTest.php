<?php

namespace Tests\Feature;

use App\Models\Secret;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ShowSecretTest extends TestCase
{
    private const UNKNOWN_TOKEN = '0123456789abcdef0123456789abcdef';

    private const READ_ID = 'fedcba9876543210fedcba9876543210';

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
            'single_use' => false,
            'previous_fetches' => 0,
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
            'single_use' => false,
            'previous_fetches' => 0,
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

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function malformedTokenRoutes(): array
    {
        return [
            'page trop courte' => ['GET', '/s/abc123'],
            'page en majuscules' => ['GET', '/s/0123456789ABCDEF0123456789ABCDEF'],
            'téléchargement trop long' => ['GET', '/s/0123456789abcdef0123456789abcdef0/download'],
            'fetch non hexadécimal' => ['GET', '/api/secrets/0123456789abcdef0123456789abcdeg'],
            'confirmation trop courte' => ['POST', '/api/secrets/nonexistenttoken12345678901/read'],
        ];
    }

    /** Vérifie qu'un token hors format (32 caractères hexadécimaux minuscules) ne correspond à aucune route. */
    #[DataProvider('malformedTokenRoutes')]
    public function testMalformedTokenMatchesNoRoute(string $method, string $uri): void
    {
        $response = $this->call($method, $uri);

        $response->assertNotFound();
        $this->assertNull($response->baseRequest->route());
    }

    /** Vérifie que le fetch renvoie le nombre de récupérations antérieures à la requête et signale l'usage unique. */
    public function testApiFetchReturnsFetchCountBeforeThisRequestAndSingleUseFlag(): void
    {
        $secret = Secret::factory()->singleUse()->create();

        $this->getJson("/api/secrets/{$secret->token}")
            ->assertOk()
            ->assertJsonPath('single_use', true)
            ->assertJsonPath('previous_fetches', 0);
        $this->getJson("/api/secrets/{$secret->token}")
            ->assertOk()
            ->assertJsonPath('previous_fetches', 1);

        $secret->refresh();
        $this->assertSame(2, $secret->fetch_count);
    }

    /** Vérifie que le fetch d'un fichier annonce les téléchargements déjà effectués sans compter le fetch lui-même. */
    public function testApiFetchOfFileSecretReturnsPreviousDownloads(): void
    {
        Storage::fake('secrets');
        $secret = Secret::factory()->withMaxViews(3)->withStoredBlob()->create();

        $this->get("/s/{$secret->token}/download")->assertOk();

        $this->getJson("/api/secrets/{$secret->token}")
            ->assertOk()
            ->assertJsonPath('single_use', false)
            ->assertJsonPath('previous_fetches', 1);

        $secret->refresh();
        $this->assertSame(1, $secret->fetch_count);
    }

    /** Vérifie qu'une confirmation rejouée avec le même read_id n'est comptée qu'une fois et reçoit la même réponse. */
    public function testApiConfirmReadWithSameReadIdCountsOnce(): void
    {
        $secret = Secret::factory()->create();

        $first = $this->postJson("/api/secrets/{$secret->token}/read", ['read_id' => self::READ_ID]);
        $retry = $this->postJson("/api/secrets/{$secret->token}/read", ['read_id' => self::READ_ID]);

        $first->assertOk()->assertExactJson(['success' => true]);
        $retry->assertOk()->assertExactJson(['success' => true]);

        $secret->refresh();
        $this->assertSame(1, $secret->read_count);
    }

    /** Vérifie que deux read_id distincts comptent deux lectures. */
    public function testApiConfirmReadWithDistinctReadIdsCountsEach(): void
    {
        $secret = Secret::factory()->create();

        $this->postJson("/api/secrets/{$secret->token}/read", ['read_id' => self::READ_ID])->assertOk();
        $this->postJson("/api/secrets/{$secret->token}/read", ['read_id' => str_repeat('a', 32)])->assertOk();

        $secret->refresh();
        $this->assertSame(2, $secret->read_count);
    }

    /** Vérifie que le rejeu de la confirmation qui a détruit un secret à usage unique reçoit toujours un succès. */
    public function testApiConfirmReadRetryAfterDestructionStillSucceeds(): void
    {
        $secret = Secret::factory()->singleUse()->create();

        $this->postJson("/api/secrets/{$secret->token}/read", ['read_id' => self::READ_ID])->assertOk();
        $retry = $this->postJson("/api/secrets/{$secret->token}/read", ['read_id' => self::READ_ID]);

        $retry->assertOk()->assertExactJson(['success' => true]);
        $this->postJson("/api/secrets/{$secret->token}/read", ['read_id' => str_repeat('a', 32)])->assertNotFound();

        $secret->refresh();
        $this->assertSame(1, $secret->read_count);
        $this->assertNull($secret->ciphertext);
    }

    /** Vérifie que le read_id est accepté depuis un corps JSON sans en-tête Accept, comme l'envoie sendBeacon. */
    public function testApiConfirmReadAcceptsBeaconStyleJsonBody(): void
    {
        $secret = Secret::factory()->create();
        $body = (string) json_encode(['read_id' => self::READ_ID]);

        $this->call('POST', "/api/secrets/{$secret->token}/read", [], [], [], ['CONTENT_TYPE' => 'application/json'], $body)->assertOk();
        $this->postJson("/api/secrets/{$secret->token}/read", ['read_id' => self::READ_ID])->assertOk();

        $secret->refresh();
        $this->assertSame(1, $secret->read_count);
    }

    /**
     * @return array<string, array{0: mixed}>
     */
    public static function invalidReadIds(): array
    {
        return [
            'trop court' => ['abc123'],
            'majuscules' => [strtoupper(self::READ_ID)],
            'non hexadécimal' => [str_repeat('z', 32)],
            'tableau' => [[self::READ_ID]],
            'entier' => [123],
        ];
    }

    /** Vérifie qu'un read_id mal formé est refusé en 422 sans compter de lecture. */
    #[DataProvider('invalidReadIds')]
    public function testApiConfirmReadRejectsMalformedReadId(mixed $readId): void
    {
        $secret = Secret::factory()->create();

        $response = $this->postJson("/api/secrets/{$secret->token}/read", ['read_id' => $readId]);

        $response->assertUnprocessable();
        $response->assertExactJson(['error' => 'invalid_read_id']);

        $secret->refresh();
        $this->assertSame(0, $secret->read_count);
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
