<?php

namespace App\Console\Commands;

use Illuminate\Cache\FileStore;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\Finder\SplFileInfo;

/** Deletes expired entries of the file cache store, which Laravel only removes when the same key is read again. */
class PruneExpiredFileCacheCommand extends Command
{
    protected $signature = 'cache:prune-expired
                            {--dry-run : Show how many entries would be deleted without deleting them}';

    protected $description = 'Delete expired entries from the file cache store';

    public function handle(): int
    {
        $store = Cache::store()->getStore();

        if (! $store instanceof FileStore) {
            $this->info('The default cache store does not use the file driver, nothing to prune.');

            return Command::SUCCESS;
        }

        $directory = $store->getDirectory();
        $files = $store->getFilesystem();

        if (! $files->isDirectory($directory)) {
            $this->info('The cache directory does not exist, nothing to prune.');

            return Command::SUCCESS;
        }

        $dryRun = (bool) $this->option('dry-run');
        $now = now()->getTimestamp();
        $scanned = 0;
        $expired = 0;

        foreach ($files->allFiles($directory) as $file) {
            $scanned++;

            if (! $this->isExpired($file, $now)) {
                continue;
            }

            $expired++;

            if (! $dryRun) {
                $files->delete($file->getPathname());
            }
        }

        $prefix = $dryRun ? '[DRY RUN] Would delete' : 'Deleted';
        $this->info("{$prefix} {$expired} expired cache entries out of {$scanned} scanned.");

        return Command::SUCCESS;
    }

    /** FileStore prefixes each entry with its expiration as a 10-digit UNIX timestamp. */
    private function isExpired(SplFileInfo $file, int $now): bool
    {
        $expiration = @file_get_contents($file->getPathname(), false, null, 0, 10);

        if (! is_string($expiration)) {
            return false;
        }

        if (strlen($expiration) !== 10) {
            return false;
        }

        if (! ctype_digit($expiration)) {
            return false;
        }

        return $now >= (int) $expiration;
    }
}
