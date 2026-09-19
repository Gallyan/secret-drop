<?php

namespace Tests\Feature;

use App\Console\Commands\PruneStatsCommand;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PruneStatsCommandTest extends TestCase
{
    /** Vérifie que les lignes plus anciennes que la rétention par défaut sont supprimées de chaque table de détail. */
    public function testDeletesRowsOlderThanDefaultRetentionFromEveryDetailTable(): void
    {
        $this->freezeTime();
        $oldDate = now()->subDays(PruneStatsCommand::DEFAULT_RETENTION_DAYS + 1)->toDateString();
        $boundaryDate = now()->subDays(PruneStatsCommand::DEFAULT_RETENTION_DAYS)->toDateString();
        $recentDate = now()->toDateString();

        $this->seedEveryTable($oldDate);
        $this->seedEveryTable($boundaryDate);
        $this->seedEveryTable($recentDate);

        $command = $this->artisan('stats:prune');

        foreach (PruneStatsCommand::TABLES as $table) {
            $command->expectsOutput("Deleted 1 rows from `{$table}`.");
        }

        $command->expectsOutput('Deleted 8 statistics rows in total.')
            ->assertSuccessful()
            ->run();

        foreach (PruneStatsCommand::TABLES as $table) {
            $this->assertDatabaseMissing($table, ['date' => $oldDate]);
            $this->assertDatabaseHas($table, ['date' => $boundaryDate]);
            $this->assertDatabaseHas($table, ['date' => $recentDate]);
        }
    }

    /** Vérifie que stats_daily n'est jamais purgée, pour préserver les totaux globaux et la période « all ». */
    public function testKeepsDailyTotalsBeyondRetention(): void
    {
        $this->freezeTime();
        $oldDate = now()->subDays(PruneStatsCommand::DEFAULT_RETENTION_DAYS + 1)->toDateString();
        $this->seedEveryTable($oldDate);

        $this->artisan('stats:prune')
            ->doesntExpectOutputToContain('stats_daily')
            ->assertSuccessful();

        $this->assertNotContains('stats_daily', PruneStatsCommand::TABLES);
        $this->assertDatabaseHas('stats_daily', ['date' => $oldDate, 'metric' => 'secrets_created']);
    }

    /** Vérifie que l'option --days remplace la durée de rétention par défaut. */
    public function testDaysOptionOverridesRetention(): void
    {
        $this->freezeTime();
        $olderDate = now()->subDays(31)->toDateString();
        $keptDate = now()->subDays(30)->toDateString();

        $this->seedEveryTable($olderDate);
        $this->seedEveryTable($keptDate);

        $this->artisan('stats:prune', ['--days' => '30'])
            ->expectsOutput('Deleted 8 statistics rows in total.')
            ->assertSuccessful();

        foreach (PruneStatsCommand::TABLES as $table) {
            $this->assertDatabaseMissing($table, ['date' => $olderDate]);
            $this->assertDatabaseHas($table, ['date' => $keptDate]);
        }
    }

    /** Vérifie qu'une valeur --days invalide ne supprime rien, pour éviter une purge totale accidentelle. */
    public function testInvalidDaysOptionDeletesNothing(): void
    {
        $this->freezeTime();
        $this->seedEveryTable(now()->toDateString());

        foreach (['0', '-5', 'abc', ''] as $days) {
            $this->artisan('stats:prune', ['--days' => $days])
                ->expectsOutput('The --days option must be a positive integer.')
                ->assertFailed();
        }

        foreach (['stats_daily', ...PruneStatsCommand::TABLES] as $table) {
            $this->assertDatabaseCount($table, 1);
        }
    }

    /** Vérifie que la purge est planifiée chaque jour sans chevauchement. */
    public function testCommandIsScheduledDaily(): void
    {
        $event = collect(app(Schedule::class)->events())
            ->first(fn (Event $event): bool => str_contains((string) $event->command, 'stats:prune'));

        $this->assertInstanceOf(Event::class, $event);
        $this->assertSame('0 0 * * *', $event->expression);
        $this->assertTrue($event->withoutOverlapping);
        $this->assertSame(1440, $event->expiresAt);
    }

    private function seedEveryTable(string $date): void
    {
        $rows = [
            'stats_daily' => ['metric' => 'secrets_created'],
            'stats_heatmap' => ['day_of_week' => 1, 'hour' => 10, 'metric' => 'secrets_created'],
            'stats_pageviews' => ['page' => 'home', 'hour' => 10],
            'stats_local_hours' => ['local_hour' => 10],
            'stats_referrers' => ['referrer_domain' => 'example.com'],
            'stats_devices' => ['device_type' => 'desktop'],
            'stats_bots' => ['bot_name' => 'Googlebot'],
            'stats_error_routes' => ['status' => 404, 'route' => 'secrets.show'],
            'stats_response_times' => ['route_group' => 'secrets', 'bucket' => 100],
        ];

        foreach ($rows as $table => $columns) {
            DB::table($table)->insert([
                'date' => $date,
                'count' => 1,
                'created_at' => now(),
                'updated_at' => now(),
                ...$columns,
            ]);
        }
    }
}
