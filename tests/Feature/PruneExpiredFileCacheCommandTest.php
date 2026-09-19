<?php

namespace Tests\Feature;

use Illuminate\Cache\FileStore;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class PruneExpiredFileCacheCommandTest extends TestCase
{
    private string $cacheDirectory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cacheDirectory = storage_path('framework/testing/prune-cache-'.bin2hex(random_bytes(8)));

        config([
            'cache.default' => 'file',
            'cache.stores.file.path' => $this->cacheDirectory,
            'cache.stores.file.lock_path' => $this->cacheDirectory,
        ]);

        Cache::purge('file');
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->cacheDirectory);

        parent::tearDown();
    }

    /** Vérifie que les entrées expirées jamais relues sont supprimées et que les entrées valides sont conservées. */
    public function testDeletesExpiredEntriesAndKeepsValidOnes(): void
    {
        Cache::put('pow:unused-challenge', 'challenge', 300);
        Cache::put('long-lived', 'value', 7200);
        Cache::forever('forever', 'value');

        $this->travel(3600)->seconds();

        $this->artisan('cache:prune-expired')
            ->expectsOutput('Deleted 1 expired cache entries out of 3 scanned.')
            ->assertSuccessful();

        $this->assertFileDoesNotExist($this->pathFor('pow:unused-challenge'));
        $this->assertSame('value', Cache::get('long-lived'));
        $this->assertSame('value', Cache::get('forever'));
    }

    /** Vérifie que le mode dry-run compte les entrées expirées sans les supprimer. */
    public function testDryRunKeepsExpiredEntries(): void
    {
        Cache::put('pow:unused-challenge', 'challenge', 300);

        $this->travel(301)->seconds();

        $this->artisan('cache:prune-expired', ['--dry-run' => true])
            ->expectsOutput('[DRY RUN] Would delete 1 expired cache entries out of 1 scanned.')
            ->assertSuccessful();

        $this->assertFileExists($this->pathFor('pow:unused-challenge'));
    }

    /** Vérifie qu'un fichier sans préfixe d'expiration FileStore n'est pas supprimé. */
    public function testKeepsFilesWithoutExpirationPrefix(): void
    {
        $foreignFile = "{$this->cacheDirectory}/ab/cd/not-a-cache-entry";
        File::ensureDirectoryExists(dirname($foreignFile));
        File::put($foreignFile, 'plain content');

        $this->artisan('cache:prune-expired')
            ->expectsOutput('Deleted 0 expired cache entries out of 1 scanned.')
            ->assertSuccessful();

        $this->assertFileExists($foreignFile);
    }

    /** Vérifie que la commande ne fait rien quand le store par défaut n'utilise pas le driver file. */
    public function testSkipsWhenDefaultStoreIsNotFileDriver(): void
    {
        config(['cache.default' => 'array']);

        $this->artisan('cache:prune-expired')
            ->expectsOutput('The default cache store does not use the file driver, nothing to prune.')
            ->assertSuccessful();
    }

    /** Vérifie que la purge des entrées expirées est planifiée toutes les heures. */
    public function testCommandIsScheduledHourly(): void
    {
        $event = collect(app(Schedule::class)->events())
            ->first(fn (Event $event): bool => str_contains((string) $event->command, 'cache:prune-expired'));

        $this->assertInstanceOf(Event::class, $event);
        $this->assertSame('0 * * * *', $event->expression);
        $this->assertTrue($event->withoutOverlapping);
    }

    private function pathFor(string $key): string
    {
        $store = Cache::store('file')->getStore();
        $this->assertInstanceOf(FileStore::class, $store);

        return $store->path($key);
    }
}
