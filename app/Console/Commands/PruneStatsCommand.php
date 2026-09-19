<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Deletes detail analytics rows older than the retention window.
 *
 * `stats_daily` is never pruned: it backs the all-time totals, the 'all'
 * period and every ratio derived from totals, and only holds one row per
 * day and metric. The pruned tables are the high-cardinality detail ones;
 * their 'all' period views are simply limited to the retention window.
 */
class PruneStatsCommand extends Command
{
    public const int DEFAULT_RETENTION_DAYS = 400;

    /** @var list<string> */
    public const array TABLES = [
        'stats_heatmap',
        'stats_pageviews',
        'stats_local_hours',
        'stats_referrers',
        'stats_devices',
        'stats_bots',
        'stats_error_routes',
        'stats_response_times',
    ];

    protected $signature = 'stats:prune
                            {--days= : Number of days of statistics to keep}';

    protected $description = 'Delete statistics rows older than the retention window';

    public function handle(): int
    {
        $days = $this->retentionDays();

        if ($days === null) {
            $this->error('The --days option must be a positive integer.');

            return Command::FAILURE;
        }

        $cutoff = now()->subDays($days)->toDateString();
        $total = 0;

        $this->info("Deleting statistics dated before {$cutoff} ({$days} days kept)...");

        foreach (self::TABLES as $table) {
            $this->line("Pruning `{$table}`...");

            $deleted = DB::table($table)->where('date', '<', $cutoff)->delete();
            $total += $deleted;

            $this->line("Deleted {$deleted} rows from `{$table}`.");
        }

        $this->comment("Deleted {$total} statistics rows in total.");

        return Command::SUCCESS;
    }

    private function retentionDays(): ?int
    {
        $option = $this->option('days');

        if ($option === null) {
            return self::DEFAULT_RETENTION_DAYS;
        }

        if (! ctype_digit($option)) {
            return null;
        }

        $days = (int) $option;

        if ($days < 1) {
            return null;
        }

        return $days;
    }
}
