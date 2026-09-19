<?php

namespace App\Console\Commands;

use App\Services\SecretStorageService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/** Removes encrypted files on disk that no longer have a matching secret record. */
class CleanOrphanBlobsCommand extends Command
{
    protected $signature = 'secrets:clean-blobs
                            {--dry-run : Show what would be deleted without actually deleting}';

    protected $description = 'Delete orphan blobs (files without corresponding secrets)';

    public function handle(SecretStorageService $storage): int
    {
        $dryRun = $this->option('dry-run');

        $files = $storage->disk()->allFiles();

        if (empty($files)) {
            $this->info('No files in storage.');

            return Command::SUCCESS;
        }

        $this->info('Found '.count($files).' files in storage.');

        $orphans = $storage->orphans($files);

        if (empty($orphans)) {
            $this->info('No orphan blobs found.');

            return Command::SUCCESS;
        }

        $this->info(($dryRun ? '[DRY RUN] ' : '').'Found '.count($orphans).' orphan blobs to delete.');

        $deleted = 0;

        foreach ($orphans as $file) {
            $this->line("Processing orphan: {$this->shorten(basename($file))}");

            if ($dryRun) {
                $deleted++;

                continue;
            }

            if (! $storage->deleteOrphan($file)) {
                $this->line("Skipped {$this->shorten(basename($file))}: now referenced or already gone.");

                continue;
            }

            $deleted++;
        }

        $prefix = $dryRun ? '[DRY RUN] Would delete' : 'Deleted';
        $this->info("{$prefix} {$deleted} orphan blobs.");

        return Command::SUCCESS;
    }

    private function shorten(string $value): string
    {
        return Str::substr($value, 0, 8).'…';
    }
}
