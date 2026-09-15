<?php

namespace Tests\Unit;

use App\Models\Secret;
use App\Services\StatsService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class StatsServiceTest extends TestCase
{
    private const TODAY = '2026-09-15';

    private StatsService $statsService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->statsService = new StatsService();
    }

    /** Vérifie que increment crée un nouvel enregistrement du jour. */
    public function testIncrementCreatesNewRecord(): void
    {
        $this->travelTo(self::TODAY.' 12:00:00');

        $this->statsService->increment('test_metric');

        $this->assertDatabaseHas('stats_daily', ['date' => self::TODAY, 'metric' => 'test_metric', 'count' => 1]);
    }

    /** Vérifie que increment insère le montant demandé puis l'ajoute à la ligne existante. */
    public function testIncrementAddsAmountToExistingRecord(): void
    {
        $this->travelTo(self::TODAY.' 12:00:00');

        $this->statsService->increment('test_amount', 100);
        $this->statsService->increment('test_amount', 25);

        $this->assertDatabaseCount('stats_daily', 1);
        $this->assertDatabaseHas('stats_daily', ['date' => self::TODAY, 'metric' => 'test_amount', 'count' => 125]);
    }

    /** @return array<string, array{string, ?int, string}> */
    public static function periods(): array
    {
        return [
            'aujourd\'hui' => ['today', 0, '2026-09-15'],
            '7 jours' => ['7d', 7, '2026-09-08'],
            '30 jours' => ['30d', 30, '2026-08-16'],
            '90 jours' => ['90d', 90, '2026-06-17'],
            '1 an' => ['1y', 365, '2025-09-15'],
            'période inconnue ramenée à 30 jours' => ['bogus', 30, '2026-08-16'],
        ];
    }

    /** Vérifie le nombre de jours et les bornes de dates de chaque période. */
    #[DataProvider('periods')]
    public function testGetStatsResolvesPeriodBounds(string $period, ?int $expectedDays, string $expectedStartDate): void
    {
        $this->travelTo(self::TODAY.' 12:00:00');

        $stats = $this->statsService->getStats($period);

        $this->assertSame($period, $stats['period']);
        $this->assertSame($expectedDays, $stats['days']);
        $this->assertSame($expectedStartDate, $stats['start_date']);
        $this->assertSame(self::TODAY, $stats['end_date']);
    }

    /** Vérifie que la période du jour ne retient que les compteurs du jour. */
    public function testGetStatsTodayKeepsOnlyTodayCounters(): void
    {
        $this->travelTo(self::TODAY.' 12:00:00');
        $this->insertDaily('2026-09-14', 'test_metric', 5);
        $this->insertDaily(self::TODAY, 'test_metric', 2);

        $stats = $this->statsService->getStats('today');

        $this->assertSame(['test_metric' => [self::TODAY => 2]], $stats['metrics']);
        $this->assertSame(['test_metric' => 2], $stats['totals']);
    }

    /** Vérifie que la période « all » part du premier jour enregistré et cumule tout l'historique. */
    public function testGetStatsAllStartsAtFirstRecordedDate(): void
    {
        $this->travelTo(self::TODAY.' 12:00:00');
        $this->insertDaily('2025-01-10', 'test_metric', 3);
        $this->insertDaily(self::TODAY, 'test_metric', 2);

        $stats = $this->statsService->getStats('all');

        $this->assertNull($stats['days']);
        $this->assertSame('2025-01-10', $stats['start_date']);
        $this->assertSame(['test_metric' => ['2025-01-10' => 3, self::TODAY => 2]], $stats['metrics']);
        $this->assertSame(['test_metric' => 5], $stats['totals']);
    }

    /** Vérifie que la période « all » sans aucune donnée commence aujourd'hui. */
    public function testGetStatsAllWithoutDataStartsToday(): void
    {
        $this->travelTo(self::TODAY.' 12:00:00');

        $stats = $this->statsService->getStats('all');

        $this->assertSame(self::TODAY, $stats['start_date']);
        $this->assertSame([], $stats['metrics']);
    }

    /** Vérifie que getTotals inclut la date de début et exclut les lignes antérieures. */
    public function testGetTotalsExcludesRowsBeforeStartDate(): void
    {
        $this->insertDaily('2026-09-07', 'test_totals', 50);
        $this->insertDaily('2026-09-08', 'test_totals', 5);
        $this->insertDaily(self::TODAY, 'test_totals', 10);

        $totals = $this->statsService->getTotals('2026-09-08');

        $this->assertSame(['test_totals' => 15], $totals);
    }

    /** Vérifie que les totaux all-time cumulent toutes les dates. */
    public function testGetAllTimeTotalsIncludesEveryDate(): void
    {
        $this->insertDaily('2025-06-07', 'test_alltime', 50);
        $this->insertDaily(self::TODAY, 'test_alltime', 25);

        $this->assertSame(['test_alltime' => 75], $this->statsService->getAllTimeTotals());
    }

    /** Vérifie que le taux de lecture vient des compteurs texte et fichier, et vaut null sans création. */
    public function testGetReadRateUsesStatsCounters(): void
    {
        $this->assertNull($this->statsService->getReadRate());

        $this->insertDaily(self::TODAY, StatsService::SECRETS_CREATED_TEXT, 4);
        $this->insertDaily(self::TODAY, StatsService::SECRETS_CREATED_FILE, 1);
        $this->insertDaily(self::TODAY, StatsService::SECRETS_READ, 4);

        $this->assertSame(80.0, $this->statsService->getReadRate());
    }

    /** Vérifie la ventilation par code HTTP, sans les agrégats 4xx/5xx, triée par volume et filtrée par date. */
    public function testGetErrorCodeBreakdownListsCodesByVolume(): void
    {
        $this->insertDaily('2026-08-01', StatsService::HTTP_ERRORS_404, 100);
        $this->insertDaily(self::TODAY, StatsService::HTTP_ERRORS_4XX, 9);
        $this->insertDaily(self::TODAY, StatsService::HTTP_ERRORS_5XX, 3);
        $this->insertDaily(self::TODAY, StatsService::HTTP_ERRORS_404, 7);
        $this->insertDaily(self::TODAY, StatsService::HTTP_ERRORS_429, 2);
        $this->insertDaily(self::TODAY, StatsService::HTTP_ERRORS_500, 3);

        $breakdown = $this->statsService->getErrorCodeBreakdown('2026-09-01');

        $this->assertSame([404 => 7, 500 => 3, 429 => 2], $breakdown);
    }

    /** Vérifie que trackFirstReadDelay cumule durée et nombre, et que la moyenne en découle. */
    public function testFirstReadDelayAverageComesFromTrackedTotals(): void
    {
        $this->travelTo(self::TODAY.' 12:00:00');
        $this->assertNull($this->statsService->getAverageFirstReadDelay());

        $this->statsService->trackFirstReadDelay(120);
        $this->statsService->trackFirstReadDelay(60);

        $this->assertDatabaseHas('stats_daily', ['metric' => StatsService::FIRST_READ_DELAY_TOTAL, 'count' => 180]);
        $this->assertDatabaseHas('stats_daily', ['metric' => StatsService::FIRST_READ_DELAY_COUNT, 'count' => 2]);
        $this->assertSame(90.0, $this->statsService->getAverageFirstReadDelay(self::TODAY));
    }

    /** Vérifie le nombre de créateurs et le coefficient de Gini de leurs volumes de secrets. */
    public function testGetCreatorConcentrationComputesGini(): void
    {
        Secret::factory()->withCreatorEmail('alice@example.com')->create();
        Secret::factory()->withCreatorEmail('bob@example.com')->create();
        Secret::factory()->withCreatorEmail('carol@example.com')->count(4)->create();
        Secret::factory()->create();

        $concentration = $this->statsService->getCreatorConcentration();

        // Volumes 1, 1, 4 : somme des écarts absolus 12 / (2 × 3² × moyenne 2) = 1/3
        $this->assertSame(['unique_creators' => 3, 'gini' => 0.33], $concentration);
    }

    /** Vérifie qu'un volume égal entre créateurs donne un Gini nul. */
    public function testGetCreatorConcentrationIsZeroForEqualCreators(): void
    {
        Secret::factory()->withCreatorEmail('alice@example.com')->count(2)->create();
        Secret::factory()->withCreatorEmail('bob@example.com')->count(2)->create();

        $this->assertSame(['unique_creators' => 2, 'gini' => 0.0], $this->statsService->getCreatorConcentration());
    }

    /** Vérifie qu'un seul créateur donne un Gini nul sans calcul. */
    public function testGetCreatorConcentrationIsZeroForSingleCreator(): void
    {
        Secret::factory()->withCreatorEmail('alice@example.com')->count(3)->create();

        $this->assertSame(['unique_creators' => 1, 'gini' => 0.0], $this->statsService->getCreatorConcentration());
    }

    /** Vérifie le décompte des secrets actifs, des fichiers sur disque et des contenus en attente de purge. */
    public function testGetSystemHealthCountsActiveFilesAndPendingCleanup(): void
    {
        $this->travelTo(self::TODAY.' 12:00:00');
        Storage::fake('secrets');
        Secret::factory()->create();
        Secret::factory()->withoutExpiry()->create();
        Secret::factory()->withStoredBlob()->create();
        Secret::factory()->expired()->create();
        Secret::factory()->expiresIn(0)->create();
        Secret::factory()->withMaxViews(2)->read(2)->create();
        Secret::factory()->revoked()->create();
        Secret::factory()->consumed()->create();

        $health = $this->statsService->getSystemHealth();

        $this->assertSame(['active_secrets' => 3, 'total_files' => 1, 'pending_cleanup' => 3], $health);
    }

    /** Vérifie que l'usage disque mesuré couvre au moins le contenu stocké et reste en cache une heure. */
    public function testGetCurrentDiskUsageMeasuresDiskAndCachesTheResult(): void
    {
        Storage::fake('secrets');
        Storage::disk('secrets')->put('ab/blob', str_repeat('x', 5000));

        $measured = $this->statsService->getCurrentDiskUsage();
        Storage::disk('secrets')->put('cd/blob', str_repeat('x', 50000));

        // du -sb ajoute les entrées de répertoire, dont la taille apparente dépend du système de fichiers
        $this->assertGreaterThanOrEqual(5000, $measured);
        $this->assertLessThan(55000, $measured);
        $this->assertSame($measured, $this->statsService->getCurrentDiskUsage());
        $this->assertSame($measured, Cache::get('disk_usage_secrets'));
    }

    /** Vérifie que l'usage disque vaut 0 quand le répertoire du disque n'existe pas. */
    public function testGetCurrentDiskUsageIsZeroWithoutDirectory(): void
    {
        Storage::fake('secrets');
        File::deleteDirectory(Storage::disk('secrets')->path(''));

        $this->assertSame(0, $this->statsService->getCurrentDiskUsage());
    }

    /** Vérifie les agrégations des vues de page : totaux, pages, pays, langues, heures et jours, sur la période. */
    public function testGetPageviewsAggregatesRowsWithinPeriod(): void
    {
        $this->insertPageview('2026-09-01', 'home', false, 8, 'FR', 'fr', 100);
        $this->insertPageview('2026-09-14', 'home', false, 9, 'FR', 'fr', 3);
        $this->insertPageview(self::TODAY, 'home', false, 10, 'DE', 'de', 2);
        $this->insertPageview(self::TODAY, 'faq', false, 10, 'FR', '', 4);
        $this->insertPageview(self::TODAY, 'home', true, 10, 'US', 'en', 5);
        $this->insertCounter('stats_local_hours', '2026-09-01', ['local_hour' => 12], 50);
        $this->insertCounter('stats_local_hours', self::TODAY, ['local_hour' => 12], 6);

        $pageviews = $this->statsService->getPageviews('2026-09-10');

        $this->assertSame(9, $pageviews['total_human']);
        $this->assertSame(5, $pageviews['total_bot']);
        $this->assertSame(
            ['home' => ['human' => 5, 'bot' => 5], 'faq' => ['human' => 4, 'bot' => 0]],
            $pageviews['by_page']
        );
        $this->assertSame(['FR' => 7, 'DE' => 2], $pageviews['by_country']);
        $this->assertSame(['fr' => 3, 'de' => 2], $pageviews['by_language']);
        $this->assertSame(array_replace(array_fill(0, 24, 0), [9 => 3, 10 => 6]), $pageviews['by_hour']);
        $this->assertSame(array_replace(array_fill(0, 24, 0), [12 => 6]), $pageviews['by_local_hour']);
        $daily = $pageviews['daily'];
        ksort($daily);
        $this->assertSame(
            ['2026-09-14' => ['human' => 3, 'bot' => 0], self::TODAY => ['human' => 6, 'bot' => 5]],
            $daily
        );
    }

    /** Vérifie la ventilation des référents humains et bots, triée par visites humaines. */
    public function testGetReferrersSplitsHumansAndBots(): void
    {
        $this->insertCounter('stats_referrers', '2026-09-01', ['referrer_domain' => 'old.example', 'is_bot' => false], 99);
        $this->insertCounter('stats_referrers', self::TODAY, ['referrer_domain' => 'google.com', 'is_bot' => false], 3);
        $this->insertCounter('stats_referrers', self::TODAY, ['referrer_domain' => 'google.com', 'is_bot' => true], 1);
        $this->insertCounter('stats_referrers', self::TODAY, ['referrer_domain' => '(direct)', 'is_bot' => false], 5);

        $referrers = $this->statsService->getReferrers('2026-09-10');

        $this->assertSame([
            '(direct)' => ['human' => 5, 'bot' => 0],
            'google.com' => ['human' => 3, 'bot' => 1],
        ], $referrers);
    }

    /** Vérifie le cumul par bot sur la période, trié par volume puis par nom. */
    public function testGetBotStatsSumsByBotName(): void
    {
        $this->insertCounter('stats_bots', '2026-09-01', ['bot_name' => 'Bingbot'], 99);
        $this->insertCounter('stats_bots', '2026-09-14', ['bot_name' => 'Googlebot'], 4);
        $this->insertCounter('stats_bots', self::TODAY, ['bot_name' => 'Googlebot'], 2);
        $this->insertCounter('stats_bots', self::TODAY, ['bot_name' => 'Bingbot'], 5);
        $this->insertCounter('stats_bots', self::TODAY, ['bot_name' => 'Ahrefs'], 5);

        $this->assertSame(
            ['Googlebot' => 6, 'Ahrefs' => 5, 'Bingbot' => 5],
            $this->statsService->getBotStats('2026-09-10')
        );
    }

    /** Vérifie le cumul par type d'appareil sur la période, trié par volume. */
    public function testGetDeviceStatsSumsByDeviceType(): void
    {
        $this->insertCounter('stats_devices', '2026-09-01', ['device_type' => 'tablet'], 99);
        $this->insertCounter('stats_devices', self::TODAY, ['device_type' => 'mobile'], 3);
        $this->insertCounter('stats_devices', self::TODAY, ['device_type' => 'desktop'], 7);

        $this->assertSame(['desktop' => 7, 'mobile' => 3], $this->statsService->getDeviceStats('2026-09-10'));
    }

    /** Vérifie les tailles moyennes texte et fichier issues des compteurs, null sans création. */
    public function testGetAverageSecretSizeFromCounters(): void
    {
        $this->assertSame(['text' => null, 'file' => null], $this->statsService->getAverageSecretSize());

        $this->insertDaily(self::TODAY, StatsService::SECRETS_CREATED_TEXT, 2);
        $this->insertDaily(self::TODAY, StatsService::TOTAL_TEXT_SIZE_BYTES, 300);
        $this->insertDaily(self::TODAY, StatsService::SECRETS_CREATED_FILE, 4);
        $this->insertDaily(self::TODAY, StatsService::TOTAL_FILE_SIZE_BYTES, 3000);

        $this->assertSame(['text' => 150.0, 'file' => 750.0], $this->statsService->getAverageSecretSize());
    }

    /** Vérifie le P95 global et par groupe tiré des seaux d'histogramme, borne incluse et période filtrée. */
    public function testGetResponseTimeP95FromBuckets(): void
    {
        $this->insertCounter('stats_response_times', '2026-09-01', ['route_group' => 'create', 'bucket' => 10000], 1000);
        $this->insertCounter('stats_response_times', self::TODAY, ['route_group' => 'create', 'bucket' => 50], 90);
        $this->insertCounter('stats_response_times', self::TODAY, ['route_group' => 'create', 'bucket' => 500], 10);
        $this->insertCounter('stats_response_times', self::TODAY, ['route_group' => 'read', 'bucket' => 100], 95);
        $this->insertCounter('stats_response_times', self::TODAY, ['route_group' => 'read', 'bucket' => 1000], 5);

        $responseTime = $this->statsService->getResponseTimeP95('2026-09-10');

        // Global : 90 + 95 = 185 sous la 190e réponse sur 200, atteinte dans le seau de 500 ms
        $this->assertSame(500.0, $responseTime['p95']);
        $byGroup = $responseTime['by_group'];
        ksort($byGroup);
        $this->assertSame(['create' => 500.0, 'read' => 100.0], $byGroup);
    }

    /** Vérifie que le P95 est null sans aucune mesure. */
    public function testGetResponseTimeP95IsNullWithoutMeasures(): void
    {
        $this->assertSame(['p95' => null, 'by_group' => []], $this->statsService->getResponseTimeP95());
    }

    private function insertDaily(string $date, string $metric, int $count): void
    {
        $this->insertCounter('stats_daily', $date, ['metric' => $metric], $count);
    }

    private function insertPageview(string $date, string $page, bool $isBot, int $hour, string $country, string $locale, int $count): void
    {
        $this->insertCounter('stats_pageviews', $date, [
            'page' => $page,
            'is_bot' => $isBot,
            'hour' => $hour,
            'country' => $country,
            'locale' => $locale,
        ], $count);
    }

    /** @param array<string, mixed> $dimensions */
    private function insertCounter(string $table, string $date, array $dimensions, int $count): void
    {
        DB::table($table)->insert(['date' => $date] + $dimensions + [
            'count' => $count,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
