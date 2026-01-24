<?php

namespace App\Console\Commands;

use App\Models\Secret;
use App\Services\SecretStorageService;
use Illuminate\Console\Command;

class CleanExpiredSecretsCommand extends Command
{
    protected $signature = 'secrets:clean
                            {--dry-run : Show what would be deleted without actually deleting}';

    protected $description = 'Delete expired secrets and their associated files';

    public function handle(SecretStorageService $storage): int
    {
        $dryRun = $this->option('dry-run');

        $secrets = Secret::query()
            ->where(function ($query) {
                $query->where('expire_at', '<', now())
                    ->orWhereNotNull('revoked_at')
                    ->orWhere(function ($q) {
                        $q->whereNotNull('max_views')
                            ->whereColumn('read_count', '>=', 'max_views');
                    })
                    ->orWhere(function ($q) {
                        $q->where('usage_unique', true)
                            ->where('read_count', '>', 0);
                    });
            })
            ->get();

        if ($secrets->isEmpty()) {
            $this->info('No expired secrets to clean.');

            return Command::SUCCESS;
        }

        $this->info(($dryRun ? '[DRY RUN] ' : '')."Found {$secrets->count()} secrets to delete.");

        $deletedFiles = 0;
        $deletedSecrets = 0;

        foreach ($secrets as $secret) {
            $this->line("Processing secret {$secret->token}...");

            if ($secret->type === 'file' && $secret->file_path) {
                if ($storage->exists($secret->file_path)) {
                    if (! $dryRun) {
                        $storage->delete($secret->file_path);
                    }
                    $deletedFiles++;
                    $this->line("  - Deleted file: {$secret->file_path}");
                }
            }

            if (! $dryRun) {
                $secret->delete();
            }
            $deletedSecrets++;
        }

        $prefix = $dryRun ? '[DRY RUN] Would delete' : 'Deleted';
        $this->info("{$prefix} {$deletedSecrets} secrets and {$deletedFiles} files.");

        return Command::SUCCESS;
    }
}
