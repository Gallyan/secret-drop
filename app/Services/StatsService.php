<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class StatsService
{
    public const SECRETS_CREATED_TEXT = 'secrets_created_text';

    public const SECRETS_CREATED_FILE = 'secrets_created_file';

    public const SECRETS_WITH_PASSPHRASE = 'secrets_with_passphrase';

    public const SECRETS_SINGLE_USE = 'secrets_single_use';

    public const SECRETS_WITH_MAX_VIEWS = 'secrets_with_max_views';

    public const TOTAL_FILE_SIZE_BYTES = 'total_file_size_bytes';

    public const SECRETS_READ = 'secrets_read';

    public const SECRETS_EXPIRED_UNREAD = 'secrets_expired_unread';

    public const SECRETS_REVOKED = 'secrets_revoked';

    public const SECRETS_MAX_VIEWS_REACHED = 'secrets_max_views_reached';

    public const MAGIC_LINKS_REQUESTED = 'magic_links_requested';

    public const MAGIC_LINKS_USED = 'magic_links_used';

    public const SECRETS_EXTENDED = 'secrets_extended';

    public function increment(string $metric, int $amount = 1): void
    {
        $date = now()->toDateString();

        DB::table('stats_daily')->upsert(
            [
                'date' => $date,
                'metric' => $metric,
                'count' => $amount,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            ['date', 'metric'],
            ['count' => DB::raw("count + {$amount}"), 'updated_at' => now()]
        );
    }

    public function getStats(string $period = '30d'): array
    {
        $days = match ($period) {
            '7d' => 7,
            '30d' => 30,
            '90d' => 90,
            '1y' => 365,
            default => 30,
        };

        $startDate = now()->subDays($days)->toDateString();

        $stats = DB::table('stats_daily')
            ->where('date', '>=', $startDate)
            ->orderBy('date')
            ->get()
            ->groupBy('metric')
            ->map(fn ($items) => $items->pluck('count', 'date')->toArray())
            ->toArray();

        return [
            'period' => $period,
            'days' => $days,
            'start_date' => $startDate,
            'end_date' => now()->toDateString(),
            'metrics' => $stats,
            'totals' => $this->getTotals($startDate),
        ];
    }

    public function getTotals(?string $startDate = null): array
    {
        $query = DB::table('stats_daily')
            ->select('metric', DB::raw('SUM(count) as total'))
            ->groupBy('metric');

        if ($startDate) {
            $query->where('date', '>=', $startDate);
        }

        return $query->pluck('total', 'metric')->toArray();
    }

    public function getAllTimeTotals(): array
    {
        return $this->getTotals(null);
    }
}
