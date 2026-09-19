<?php

namespace Tests\Feature;

use App\Models\Secret;
use App\Services\SecretStorageService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Mockery\MockInterface;
use Tests\TestCase;

class CleanOrphanBlobsCommandTest extends TestCase
{
    private const SECRET_TOKEN = 'ab34567890abcdef1234567890abcdef';

    private const ORPHAN_PATH = 'cd/cd34567890abcdef1234567890abcdef';

    /** Vérifie que la commande supprime un blob orphelin ancien sans toucher au blob d'un secret existant. */
    public function testDeletesOldOrphanBlobButKeepsSecretBlob(): void
    {
        Storage::fake('secrets');
        Secret::factory()->withStoredBlob('valid-blob')->create(['token' => self::SECRET_TOKEN]);
        $this->putAgedBlob(self::ORPHAN_PATH, 'orphan-blob');

        $this->artisan('secrets:clean-blobs')
            ->expectsOutput('Found 2 files in storage.')
            ->expectsOutput('Found 1 orphan blobs to delete.')
            ->expectsOutput('Processing orphan: cd345678…')
            ->doesntExpectOutputToContain('cd34567890abcdef1234567890abcdef')
            ->expectsOutput('Deleted 1 orphan blobs.')
            ->assertSuccessful();

        Storage::disk('secrets')->assertMissing(self::ORPHAN_PATH);
        Storage::disk('secrets')->assertExists('ab/ab34567890abcdef1234567890abcdef', 'valid-blob');
    }

    /** Vérifie que la commande signale l'absence d'orphelin quand chaque blob appartient à un secret. */
    public function testReportsNoOrphanBlobsWhenEveryBlobBelongsToSecret(): void
    {
        Storage::fake('secrets');
        Secret::factory()->withStoredBlob()->create();

        $this->artisan('secrets:clean-blobs')
            ->expectsOutput('No orphan blobs found.')
            ->assertSuccessful();
    }

    /** Vérifie que le blob résiduel d'un secret consommé, dont le chemin est effacé, est supprimé comme orphelin. */
    public function testDeletesLeftoverBlobOfConsumedSecret(): void
    {
        Storage::fake('secrets');
        $consumedSecret = Secret::factory()->file()->consumed()->create();
        $leftoverPath = substr($consumedSecret->token, 0, 2).'/'.$consumedSecret->token;
        $this->putAgedBlob($leftoverPath, 'leftover-blob');

        $this->artisan('secrets:clean-blobs')
            ->expectsOutput('Deleted 1 orphan blobs.')
            ->assertSuccessful();

        Storage::disk('secrets')->assertMissing($leftoverPath);
    }

    /** Vérifie que la suppression d'orphelins invalide les tailles de stockage mises en cache. */
    public function testInvalidatesCachedDiskUsageWhenOrphansAreDeleted(): void
    {
        Storage::fake('secrets');
        $this->putAgedBlob(self::ORPHAN_PATH, 'orphan-blob');
        Cache::put('disk_usage_secrets', 123, 3600);
        Cache::put('secrets:total_file_size', 123, 300);

        $this->artisan('secrets:clean-blobs')->assertSuccessful();

        $this->assertFalse(Cache::has('disk_usage_secrets'));
        $this->assertFalse(Cache::has('secrets:total_file_size'));
    }

    /** Vérifie que --dry-run annonce la suppression sans supprimer l'orphelin ni invalider le cache. */
    public function testDryRunKeepsOrphanBlobAndCachedDiskUsage(): void
    {
        Storage::fake('secrets');
        $this->putAgedBlob(self::ORPHAN_PATH, 'orphan-blob');
        Cache::put('disk_usage_secrets', 123, 3600);

        $this->artisan('secrets:clean-blobs', ['--dry-run' => true])
            ->expectsOutput('[DRY RUN] Found 1 orphan blobs to delete.')
            ->expectsOutput('[DRY RUN] Would delete 1 orphan blobs.')
            ->assertSuccessful();

        Storage::disk('secrets')->assertExists(self::ORPHAN_PATH, 'orphan-blob');
        $this->assertSame(123, Cache::get('disk_usage_secrets'));
    }

    /** Vérifie que la commande signale un stockage vide. */
    public function testReportsEmptyStorage(): void
    {
        Storage::fake('secrets');

        $this->artisan('secrets:clean-blobs')
            ->expectsOutput('No files in storage.')
            ->assertSuccessful();
    }

    /** Vérifie qu'un blob non référencé plus récent que le délai de grâce est conservé. */
    public function testKeepsUnreferencedBlobYoungerThanGracePeriod(): void
    {
        Storage::fake('secrets');
        Storage::disk('secrets')->put(self::ORPHAN_PATH, 'in-flight');
        touch(
            Storage::disk('secrets')->path(self::ORPHAN_PATH),
            now()->subSeconds(SecretStorageService::ORPHAN_GRACE_SECONDS - 60)->getTimestamp(),
        );

        $this->artisan('secrets:clean-blobs')
            ->expectsOutput('No orphan blobs found.')
            ->assertSuccessful();

        Storage::disk('secrets')->assertExists(self::ORPHAN_PATH, 'in-flight');
    }

    /** Vérifie qu'un blob référencé par un secret créé entre le listage et la suppression est conservé. */
    public function testKeepsBlobReferencedBySecretCreatedAfterListing(): void
    {
        Storage::fake('secrets');
        $path = 'ab/'.self::SECRET_TOKEN;
        $this->putAgedBlob($path, 'blob');
        $this->partialMock(SecretStorageService::class, function (MockInterface $mock) use ($path): void {
            $mock->shouldReceive('orphans')->andReturnUsing(function () use ($path): array {
                Secret::factory()->file()->create(['token' => self::SECRET_TOKEN]);

                return [$path];
            });
        });

        $this->artisan('secrets:clean-blobs')
            ->expectsOutput('Found 1 orphan blobs to delete.')
            ->expectsOutput('Skipped ab345678…: now referenced or already gone.')
            ->doesntExpectOutputToContain(self::SECRET_TOKEN)
            ->expectsOutput('Deleted 0 orphan blobs.')
            ->assertSuccessful();

        Storage::disk('secrets')->assertExists($path, 'blob');
    }

    private function putAgedBlob(string $path, string $contents): void
    {
        Storage::disk('secrets')->put($path, $contents);
        touch(Storage::disk('secrets')->path($path), now()->subHours(2)->getTimestamp());
    }
}
