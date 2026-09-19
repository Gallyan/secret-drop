<?php

namespace Tests\Feature;

use App\Models\MagicLink;
use App\Models\Secret;
use App\Services\StatsService;
use Closure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CleanExpiredSecretsCommandTest extends TestCase
{
    /**
     * @return array<string, array{0: Closure(): Secret}>
     */
    public static function secretsNotCountedAsExpiredUnread(): array
    {
        return [
            'expiré après avoir été lu' => [fn (): Secret => Secret::factory()->expired()->read()->create()],
            'expiré puis révoqué' => [fn (): Secret => Secret::factory()->expired()->revoked()->create()],
            'révoqué avant expiration' => [fn (): Secret => Secret::factory()->revoked()->create()],
            'vues épuisées avant expiration' => [fn (): Secret => Secret::factory()->consumed()->create()],
        ];
    }

    /** Vérifie que la commande supprime un secret expiré et conserve un secret encore valide. */
    public function testDeletesExpiredSecretAndKeepsValidOne(): void
    {
        $this->freezeTime();
        $expiredSecret = Secret::factory()->expired()->create();
        $validSecret = Secret::factory()->create();

        $this->artisan('secrets:clean')
            ->expectsOutput('Found 1 secrets to delete.')
            ->expectsOutput('Deleted 1 secrets and 0 files.')
            ->assertSuccessful();

        $this->assertModelMissing($expiredSecret);
        $this->assertModelExists($validSecret);
    }

    /** Vérifie que la commande supprime un secret révoqué non expiré. */
    public function testDeletesRevokedSecret(): void
    {
        $this->freezeTime();
        $revokedSecret = Secret::factory()->revoked()->create();

        $this->artisan('secrets:clean')->assertSuccessful();

        $this->assertModelMissing($revokedSecret);
    }

    /** Vérifie que la commande supprime un secret dont le nombre de lectures atteint la limite de vues. */
    public function testDeletesSecretWhoseMaxViewsAreReached(): void
    {
        $this->freezeTime();
        $maxViewsSecret = Secret::factory()->withMaxViews(3)->read(3)->create();

        $this->artisan('secrets:clean')->assertSuccessful();

        $this->assertModelMissing($maxViewsSecret);
    }

    /** Vérifie qu'un secret sans date d'expiration n'est jamais sélectionné pour le nettoyage. */
    public function testReportsNothingToCleanWhenOnlySecretHasNoExpiry(): void
    {
        $this->freezeTime();
        $secret = Secret::factory()->withoutExpiry()->create();

        $this->artisan('secrets:clean')
            ->expectsOutput('No expired secrets to clean.')
            ->assertSuccessful();

        $this->assertModelExists($secret);
    }

    /** Vérifie que la commande supprime le secret fichier expiré et son blob chiffré. */
    public function testDeletesExpiredFileSecretAndItsBlob(): void
    {
        $this->freezeTime();
        Storage::fake('secrets');
        $fileSecret = Secret::factory()->expired()->withStoredBlob()->create();
        $filePath = (string) $fileSecret->file_path;
        $tokenPrefix = substr($fileSecret->token, 0, 8);

        $this->artisan('secrets:clean')
            ->expectsOutput('Found 1 secrets to delete.')
            ->expectsOutput("Processing secret {$fileSecret->id}...")
            ->expectsOutput("  - Deleted file {$tokenPrefix}…")
            ->doesntExpectOutputToContain($fileSecret->token)
            ->expectsOutput('Deleted 1 secrets and 1 files.')
            ->assertSuccessful();

        $this->assertModelMissing($fileSecret);
        Storage::disk('secrets')->assertMissing($filePath);
    }

    /** Vérifie que la suppression d'un blob invalide les tailles de stockage mises en cache. */
    public function testInvalidatesCachedDiskUsageWhenBlobIsDeleted(): void
    {
        $this->freezeTime();
        Storage::fake('secrets');
        Secret::factory()->expired()->withStoredBlob()->create();
        Cache::put('disk_usage_secrets', 123, 3600);
        Cache::put('secrets:total_file_size', 123, 300);

        $this->artisan('secrets:clean')->assertSuccessful();

        $this->assertFalse(Cache::has('disk_usage_secrets'));
        $this->assertFalse(Cache::has('secrets:total_file_size'));
    }

    /** Vérifie qu'un secret dont le blob a disparu du disque est supprimé sans compter de fichier. */
    public function testDeletesSecretWhoseBlobIsMissingWithoutCountingFile(): void
    {
        $this->freezeTime();
        Storage::fake('secrets');
        $fileSecret = Secret::factory()->expired()->file()->create();

        $this->artisan('secrets:clean')
            ->expectsOutput('Deleted 1 secrets and 0 files.')
            ->assertSuccessful();

        $this->assertModelMissing($fileSecret);
    }

    /** Vérifie qu'un secret fichier révoqué dont le chemin est déjà effacé est supprimé sans compter de fichier. */
    public function testDeletesRevokedFileSecretWithoutFilePath(): void
    {
        $this->freezeTime();
        Storage::fake('secrets');
        $fileSecret = Secret::factory()->file()->revoked()->create();

        $this->artisan('secrets:clean')
            ->expectsOutput('Deleted 1 secrets and 0 files.')
            ->assertSuccessful();

        $this->assertModelMissing($fileSecret);
    }

    /** Vérifie que les secrets expirés jamais lus sont comptés dans la statistique du jour. */
    public function testCountsExpiredUnreadSecretsInDailyStats(): void
    {
        $this->travelTo('2026-09-15 12:00:00');
        Secret::factory()->count(2)->expired()->create();

        $this->artisan('secrets:clean')->assertSuccessful();

        $this->assertDatabaseHas('stats_daily', [
            'date' => '2026-09-15',
            'metric' => StatsService::SECRETS_EXPIRED_UNREAD,
            'count' => 2,
        ]);
    }

    /**
     * Vérifie que les secrets supprimés sans être expirés et non lus ne sont pas comptés comme expirés non lus.
     *
     * @param  Closure(): Secret  $createSecret
     */
    #[DataProvider('secretsNotCountedAsExpiredUnread')]
    public function testDoesNotCountSecretAsExpiredUnreadWhenReadOrDestroyed(Closure $createSecret): void
    {
        $this->travelTo('2026-09-15 12:00:00');
        $secret = $createSecret();

        $this->artisan('secrets:clean')->assertSuccessful();

        $this->assertModelMissing($secret);
        $this->assertDatabaseMissing('stats_daily', ['metric' => StatsService::SECRETS_EXPIRED_UNREAD]);
    }

    /** Vérifie que --dry-run annonce la suppression sans supprimer le secret ni alimenter la statistique. */
    public function testDryRunKeepsExpiredSecretAndStats(): void
    {
        $this->freezeTime();
        $expiredSecret = Secret::factory()->expired()->create();

        $this->artisan('secrets:clean', ['--dry-run' => true])
            ->expectsOutput('[DRY RUN] Found 1 secrets to delete.')
            ->expectsOutput('[DRY RUN] Would delete 1 secrets and 0 files.')
            ->assertSuccessful();

        $this->assertModelExists($expiredSecret);
        $this->assertDatabaseMissing('stats_daily', ['metric' => StatsService::SECRETS_EXPIRED_UNREAD]);
    }

    /** Vérifie que --dry-run conserve le blob d'un secret fichier expiré et le cache d'usage disque. */
    public function testDryRunKeepsFileSecretBlobAndCachedDiskUsage(): void
    {
        $this->freezeTime();
        Storage::fake('secrets');
        $fileSecret = Secret::factory()->expired()->withStoredBlob('encrypted-blob')->create();
        Cache::put('disk_usage_secrets', 123, 3600);

        $this->artisan('secrets:clean', ['--dry-run' => true])
            ->expectsOutput('[DRY RUN] Would delete 1 secrets and 1 files.')
            ->assertSuccessful();

        $this->assertModelExists($fileSecret);
        Storage::disk('secrets')->assertExists((string) $fileSecret->file_path, 'encrypted-blob');
        $this->assertSame(123, Cache::get('disk_usage_secrets'));
    }

    /** Vérifie que la commande supprime un magic link expiré et conserve un magic link valide. */
    public function testDeletesExpiredMagicLinkAndKeepsValidOne(): void
    {
        $this->freezeTime();
        $expiredLink = MagicLink::factory()->expired()->create();
        $validLink = MagicLink::factory()->valid()->create();

        $this->artisan('secrets:clean')
            ->expectsOutput('Deleted 1 magic links.')
            ->assertSuccessful();

        $this->assertModelMissing($expiredLink);
        $this->assertModelExists($validLink);
    }

    /** Vérifie que la commande supprime un magic link déjà utilisé non expiré. */
    public function testDeletesUsedMagicLink(): void
    {
        $this->freezeTime();
        $usedLink = MagicLink::factory()->used()->create();

        $this->artisan('secrets:clean')->assertSuccessful();

        $this->assertModelMissing($usedLink);
    }

    /** Vérifie que --dry-run conserve les magic links expirés. */
    public function testDryRunKeepsExpiredMagicLink(): void
    {
        $this->freezeTime();
        $expiredLink = MagicLink::factory()->expired()->create();

        $this->artisan('secrets:clean', ['--dry-run' => true])
            ->expectsOutput('[DRY RUN] Would delete 1 magic links.')
            ->assertSuccessful();

        $this->assertModelExists($expiredLink);
    }
}
