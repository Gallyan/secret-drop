<?php

namespace Tests\Feature;

use App\Models\Secret;
use App\Services\SecretStorageService;
use Closure;
use Illuminate\Support\Facades\Storage;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AuditDataConsistencyCommandTest extends TestCase
{
    private const TOKEN = 'ab34567890abcdef1234567890abcdef';

    private const FILE_PATH = 'ab/ab34567890abcdef1234567890abcdef';

    private const ORPHAN_PATH = 'cd/cd34567890abcdef1234567890abcdef';

    /**
     * @return array<string, array{0: Closure(): Secret}>
     */
    public static function destroyedSecretsHoldingContent(): array
    {
        return [
            'révoqué avec ciphertext' => [fn (): Secret => Secret::factory()->create(['revoked_at' => now()])],
            'vues épuisées avec ciphertext' => [fn (): Secret => Secret::factory()->singleUse()->read()->create()],
            'révoqué avec blob' => [fn (): Secret => Secret::factory()->withStoredBlob()->create(['revoked_at' => now()])],
        ];
    }

    /**
     * @return array<string, array{0: Closure(): Secret}>
     */
    public static function activeTextSecretsWithoutCiphertext(): array
    {
        return [
            'expiration future' => [fn (): Secret => Secret::factory()->create(['ciphertext' => null])],
            'sans expiration' => [fn (): Secret => Secret::factory()->withoutExpiry()->create(['ciphertext' => null])],
            'vues restantes' => [fn (): Secret => Secret::factory()->withMaxViews(3)->read(1)->create(['ciphertext' => null])],
        ];
    }

    /**
     * @return array<string, array{0: int, 1: string}>
     */
    public static function fileSizes(): array
    {
        return [
            'juste sous un kilo-octet' => [1023, '1023 B'],
            'un kilo-octet' => [1024, '1.0 KB'],
            'un kilo-octet et demi' => [1536, '1.5 KB'],
            'un méga-octet' => [1048576, '1.0 MB'],
        ];
    }

    /** Vérifie que la commande réussit sans anomalie quand base et disque sont cohérents. */
    public function testSucceedsWhenDatabaseAndStorageAreConsistent(): void
    {
        $this->freezeTime();
        Storage::fake('secrets');
        Secret::factory()->withStoredBlob()->create();
        Secret::factory()->create();
        Secret::factory()->revoked()->create();
        Secret::factory()->consumed()->create();
        Secret::factory()->expired()->create(['ciphertext' => null]);

        $this->artisan('secrets:audit')
            ->expectsOutput('No inconsistencies found.')
            ->assertSuccessful();
    }

    /** Vérifie qu'un fichier orphelin est signalé avec sa taille sans être supprimé. */
    public function testReportsOrphanFileWithoutDeletingIt(): void
    {
        Storage::fake('secrets');
        $this->putAgedBlob(self::ORPHAN_PATH, 'orphan');

        $this->artisan('secrets:audit')
            ->expectsOutput('  Orphan file: cd345678… (6 B)')
            ->doesntExpectOutputToContain('cd34567890abcdef1234567890abcdef')
            ->expectsOutputToContain('Found 1 orphan files (use --fix to delete)')
            ->expectsOutput('Found 1 inconsistencies.')
            ->assertFailed();

        Storage::disk('secrets')->assertExists(self::ORPHAN_PATH, 'orphan');
    }

    /** Vérifie que --fix supprime le fichier orphelin tout en terminant en échec. */
    public function testFixDeletesOrphanFileAndStillFails(): void
    {
        Storage::fake('secrets');
        $this->putAgedBlob(self::ORPHAN_PATH, 'orphan');

        $this->artisan('secrets:audit', ['--fix' => true])
            ->expectsOutput('    -> Deleted')
            ->expectsOutputToContain('Fixed 1 orphan files')
            ->expectsOutput('Found 1 inconsistencies.')
            ->assertFailed();

        Storage::disk('secrets')->assertMissing(self::ORPHAN_PATH);
    }

    /** Vérifie qu'un fichier absent du disque est signalé sans révoquer le secret. */
    public function testReportsMissingFileWithoutRevokingSecret(): void
    {
        $this->freezeTime();
        Storage::fake('secrets');
        $secret = Secret::factory()->file()->create(['token' => self::TOKEN]);

        $this->artisan('secrets:audit')
            ->expectsOutput("  Missing file: ab345678… (secret {$secret->id})")
            ->doesntExpectOutputToContain(self::TOKEN)
            ->expectsOutputToContain('Found 1 missing files (use --fix to revoke)')
            ->expectsOutput('Found 1 inconsistencies.')
            ->assertFailed();

        $secret->refresh();
        $this->assertSame(self::FILE_PATH, $secret->file_path);
        $this->assertNull($secret->revoked_at);
    }

    /** Vérifie que --fix révoque le secret dont le fichier est absent et efface son chemin. */
    public function testFixRevokesSecretAndClearsFilePathWhenFileIsMissing(): void
    {
        $this->travelTo('2026-09-15 12:00:00');
        Storage::fake('secrets');
        $secret = Secret::factory()->file()->create(['token' => self::TOKEN]);

        $this->artisan('secrets:audit', ['--fix' => true])
            ->expectsOutput('    -> Revoked and cleared file_path')
            ->expectsOutputToContain('Fixed 1 missing files (revoked)')
            ->expectsOutput('Found 1 inconsistencies.')
            ->assertFailed();

        $secret->refresh();
        $this->assertNull($secret->file_path);
        $this->assertSame('2026-09-15 12:00:00', $secret->revoked_at?->toDateTimeString());
    }

    /**
     * Vérifie qu'un secret détruit qui conserve son contenu est signalé en attente de nettoyage.
     *
     * @param  Closure(): Secret  $createSecret
     */
    #[DataProvider('destroyedSecretsHoldingContent')]
    public function testReportsDestroyedSecretStillHoldingContent(Closure $createSecret): void
    {
        $this->freezeTime();
        Storage::fake('secrets');
        $createSecret();

        $this->artisan('secrets:audit')
            ->expectsOutputToContain('Found 1 destroyed secrets still holding content (pending cleanup).')
            ->expectsOutput('Found 1 inconsistencies.')
            ->assertFailed();
    }

    /**
     * Vérifie qu'un secret texte encore actif sans ciphertext est signalé.
     *
     * @param  Closure(): Secret  $createSecret
     */
    #[DataProvider('activeTextSecretsWithoutCiphertext')]
    public function testReportsActiveTextSecretWithoutCiphertext(Closure $createSecret): void
    {
        $this->freezeTime();
        Storage::fake('secrets');
        $createSecret();

        $this->artisan('secrets:audit')
            ->expectsOutputToContain('Found 1 active text secrets with missing ciphertext.')
            ->expectsOutput('Found 1 inconsistencies.')
            ->assertFailed();
    }

    /** Vérifie que la taille d'un fichier orphelin est affichée dans l'unité adaptée. */
    #[DataProvider('fileSizes')]
    public function testDisplaysOrphanFileSizeInReadableUnit(int $bytes, string $expectedSize): void
    {
        Storage::fake('secrets');
        $this->putAgedBlob(self::ORPHAN_PATH, str_repeat('x', $bytes));

        $this->artisan('secrets:audit')
            ->expectsOutput("  Orphan file: cd345678… ({$expectedSize})")
            ->assertFailed();
    }

    /** Vérifie qu'un blob non référencé plus récent que le délai de grâce n'est ni signalé ni supprimé. */
    public function testIgnoresUnreferencedBlobYoungerThanGracePeriod(): void
    {
        Storage::fake('secrets');
        Storage::disk('secrets')->put(self::ORPHAN_PATH, 'in-flight');

        $this->artisan('secrets:audit', ['--fix' => true])
            ->doesntExpectOutputToContain('Orphan file')
            ->expectsOutput('No inconsistencies found.')
            ->assertSuccessful();

        Storage::disk('secrets')->assertExists(self::ORPHAN_PATH, 'in-flight');
    }

    /** Vérifie que --fix conserve un blob référencé par un secret créé entre le listage et la suppression. */
    public function testFixKeepsBlobReferencedAfterListing(): void
    {
        Storage::fake('secrets');
        $this->putAgedBlob(self::FILE_PATH, 'blob');
        $this->partialMock(SecretStorageService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('orphans')->andReturnUsing(function (): array {
                Secret::factory()->file()->create(['token' => self::TOKEN]);

                return [self::FILE_PATH];
            });
        });

        $this->artisan('secrets:audit', ['--fix' => true])
            ->expectsOutput('    -> Skipped (now referenced or already gone)')
            ->expectsOutput('No inconsistencies found.')
            ->assertSuccessful();

        Storage::disk('secrets')->assertExists(self::FILE_PATH, 'blob');
    }

    private function putAgedBlob(string $path, string $contents): void
    {
        Storage::disk('secrets')->put($path, $contents);
        touch(Storage::disk('secrets')->path($path), now()->subHours(2)->getTimestamp());
    }
}
