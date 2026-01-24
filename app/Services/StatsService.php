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

    public const SECRETS_SPLIT_MODE = 'secrets_split_mode';

    public const TOTAL_FILE_SIZE_BYTES = 'total_file_size_bytes';

    public const SECRETS_READ = 'secrets_read';

    public const SECRETS_EXPIRED_UNREAD = 'secrets_expired_unread';

    public const SECRETS_REVOKED = 'secrets_revoked';

    public const SECRETS_MAX_VIEWS_REACHED = 'secrets_max_views_reached';

    public const MAGIC_LINKS_REQUESTED = 'magic_links_requested';

    public const MAGIC_LINKS_USED = 'magic_links_used';

    public const SECRETS_EXTENDED = 'secrets_extended';

    public const FIRST_READ_DELAY_TOTAL = 'first_read_delay_total';

    public const FIRST_READ_DELAY_COUNT = 'first_read_delay_count';

    public const HEATMAP_SECRETS_CREATED = 'secrets_created';

    public const HEATMAP_SECRETS_READ = 'secrets_read';

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

    /**
     * @return array<string, mixed>
     */
    public function getStats(string $period = '30d'): array
    {
        $days = match ($period) {
            '7d' => 7,
            '30d' => 30,
            '90d' => 90,
            '1y' => 365,
            'all' => null,
            default => 30,
        };

        $startDate = $days ? now()->subDays($days)->toDateString() : null;

        $query = DB::table('stats_daily')->orderBy('date');

        if ($startDate) {
            $query->where('date', '>=', $startDate);
        }

        $stats = $query->get()
            ->groupBy('metric')
            ->map(fn ($items) => $items->pluck('count', 'date')->toArray())
            ->toArray();

        $firstDate = $startDate ?? DB::table('stats_daily')->min('date') ?? now()->toDateString();

        return [
            'period' => $period,
            'days' => $days,
            'start_date' => $firstDate,
            'end_date' => now()->toDateString(),
            'metrics' => $stats,
            'totals' => $this->getTotals($startDate),
        ];
    }

    /**
     * @return array<string, int>
     */
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

    /**
     * @return array<string, int>
     */
    public function getAllTimeTotals(): array
    {
        return $this->getTotals(null);
    }

    public function incrementHeatmap(string $metric): void
    {
        $now = now();
        $dayOfWeek = (int) $now->dayOfWeek;
        $hour = (int) $now->hour;

        DB::table('stats_heatmap')->upsert(
            [
                'day_of_week' => $dayOfWeek,
                'hour' => $hour,
                'metric' => $metric,
                'count' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            ['day_of_week', 'hour', 'metric'],
            ['count' => DB::raw('count + 1'), 'updated_at' => $now]
        );
    }

    /**
     * @return array<int, array<int, int>>
     */
    public function getHeatmap(string $metric): array
    {
        $data = DB::table('stats_heatmap')
            ->where('metric', $metric)
            ->get()
            ->keyBy(fn ($row) => "{$row->day_of_week}-{$row->hour}");

        $heatmap = [];
        for ($day = 0; $day < 7; $day++) {
            $heatmap[$day] = [];
            for ($hour = 0; $hour < 24; $hour++) {
                $key = "{$day}-{$hour}";
                $heatmap[$day][$hour] = isset($data[$key]) ? (int) $data[$key]->count : 0;
            }
        }

        return $heatmap;
    }

    public function trackFirstReadDelay(int $delaySeconds): void
    {
        $this->increment(self::FIRST_READ_DELAY_TOTAL, $delaySeconds);
        $this->increment(self::FIRST_READ_DELAY_COUNT);
    }

    public function getAverageFirstReadDelay(): ?float
    {
        $totals = $this->getAllTimeTotals();
        $total = $totals[self::FIRST_READ_DELAY_TOTAL] ?? 0;
        $count = $totals[self::FIRST_READ_DELAY_COUNT] ?? 0;

        if ($count === 0) {
            return null;
        }

        return $total / $count;
    }
}
