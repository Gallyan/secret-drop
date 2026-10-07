<?php

namespace Tests\Feature;

use App\Services\StatsService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SuperAdminDashboardTest extends TestCase
{
    /** Vérifie que les infobulles horaires affichent une plage et une unité. */
    public function testHourlyChartTooltipsShowARangeAndAUnit(): void
    {
        Storage::fake('secrets');

        $response = $this->withSession($this->superAdminSession())->get('/fr/superadmin/dashboard');

        $response->assertSee("title=\"14:00–15:00 · 0\u{00A0}vue\"", false);
        $response->assertSee("title=\"23:00–00:00 · 0\u{00A0}vue\"", false);
        $response->assertDontSee('title="14h:', false);
    }

    /** Vérifie que la note d'horaire serveur des heatmaps suit la langue de la page. */
    public function testHeatmapServerTimeNoteIsTranslated(): void
    {
        Storage::fake('secrets');

        $response = $this->withSession($this->superAdminSession())->get('/en/superadmin/dashboard');

        $response->assertSee('UTC — server time');
        $response->assertDontSee('heure serveur');
    }

    /** Vérifie que la période today expose une ventilation horaire issue de la heatmap. */
    public function testTodayPeriodExposesAnHourlyBreakdown(): void
    {
        Storage::fake('secrets');
        $this->travelTo('2026-09-15 16:00:00');
        DB::table('stats_heatmap')->insert([
            'date' => '2026-09-15',
            'day_of_week' => 2,
            'hour' => 14,
            'metric' => StatsService::HEATMAP_SECRETS_CREATED,
            'count' => 3,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->withSession($this->superAdminSession())->get('/fr/superadmin/dashboard?period=today');

        $response->assertViewHas('period', 'today');
        $hourly = $response->viewData('hourly');
        $this->assertCount(24, $hourly['created']);
        $this->assertSame(3, $hourly['created'][14]);
        $this->assertSame(0, $hourly['created'][13]);

        foreach (['read', 'magic_links_requested', 'magic_links_used', 'secrets_extended', 'errors_4xx', 'errors_5xx'] as $series) {
            $this->assertCount(24, $hourly[$series], "La série {$series} doit couvrir 24 heures");
        }
    }

    /** Vérifie que les autres périodes conservent l'affichage par jour. */
    public function testOtherPeriodsKeepTheDailyBreakdown(): void
    {
        Storage::fake('secrets');

        $response = $this->withSession($this->superAdminSession())->get('/fr/superadmin/dashboard?period=7d');

        $response->assertViewHas('period', '7d');
        $this->assertNull($response->viewData('hourly'));
    }

    /** Vérifie qu'une période invalide est remplacée par la valeur par défaut (30d). */
    public function testInvalidPeriodFallsBackTo30Days(): void
    {
        Storage::fake('secrets');

        $response = $this->withSession($this->superAdminSession())->get('/fr/superadmin/dashboard?period=2d');

        $response->assertViewHas('period', '30d');
        $this->assertNull($response->viewData('hourly'));
    }

    /** Vérifie que les erreurs 5xx par page affichent le libellé de la page, y compris pour page.show. */
    public function testErrorRoutesAreDisplayedWithTheirPageLabel(): void
    {
        Storage::fake('secrets');
        $this->travelTo('2026-09-15 16:00:00');

        foreach (['faq' => 2, 'page.show' => 1] as $route => $count) {
            DB::table('stats_error_routes')->insert([
                'date' => '2026-09-15',
                'status' => 500,
                'route' => $route,
                'count' => $count,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $response = $this->withSession($this->superAdminSession())->get('/fr/superadmin/dashboard');

        $response->assertSeeInOrder([
            __('messages.stat_5xx_by_route', [], 'fr'),
            __('messages.faq_title', [], 'fr'),
            __('messages.stat_page_content', [], 'fr'),
        ]);
    }

    /** Vérifie que l'anneau de polling expose le template de son infobulle de décompte. */
    public function testPollRingCarriesRefreshTitleTemplate(): void
    {
        Storage::fake('secrets');

        $response = $this->withSession($this->superAdminSession())->get('/fr/superadmin/dashboard');

        $response->assertSee('data-title-template="'.__('messages.poll_refresh_in', [], 'fr').'"', false);
        $response->assertSee('pollRingTitle');
    }

    /** Vérifie que le polling ne renvoie que les principaux référents, triés par visites humaines. */
    public function testPollReturnsOnlyTopReferrers(): void
    {
        Storage::fake('secrets');
        $this->travelTo('2026-09-15 16:00:00');

        $rows = [];

        foreach (range(1, StatsService::TOP_REFERRERS_LIMIT + 5) as $index) {
            foreach ([false, true] as $isBot) {
                $rows[] = [
                    'date' => '2026-09-15',
                    'referrer_domain' => "site{$index}.example",
                    'is_bot' => $isBot,
                    'count' => $isBot ? 1 : $index,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        DB::table('stats_referrers')->insert($rows);

        $response = $this->withSession($this->superAdminSession())->getJson('/fr/superadmin/dashboard/poll');

        $response->assertOk();
        $referrers = $response->json('referrers');
        $this->assertCount(StatsService::TOP_REFERRERS_LIMIT, $referrers);
        $this->assertSame(['human' => 25, 'bot' => 1], reset($referrers));
        $this->assertSame('site25.example', array_key_first($referrers));
        $this->assertArrayNotHasKey('site5.example', $referrers);
    }

    /** Vérifie que les chemins en 404 de la période sont renvoyés par volume décroissant et bornés. */
    public function testPollReturnsNotFoundPathsByDescendingVolume(): void
    {
        Storage::fake('secrets');
        $this->travelTo('2026-09-15 16:00:00');

        $rows = [];

        foreach (range(1, StatsService::TOP_NOT_FOUND_PATHS_LIMIT + 5) as $index) {
            $rows[] = [
                'date' => '2026-09-15',
                'path' => "/probe-{$index}",
                'count' => $index,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        $rows[] = ['date' => '2025-01-01', 'path' => '/ancien', 'count' => 999, 'created_at' => now(), 'updated_at' => now()];

        DB::table('stats_not_found_paths')->insert($rows);

        $response = $this->withSession($this->superAdminSession())->getJson('/fr/superadmin/dashboard/poll?period=7d');

        $paths = $response->json('errorStats.not_found_paths');
        $this->assertCount(StatsService::TOP_NOT_FOUND_PATHS_LIMIT, $paths);
        $this->assertSame('/probe-55', array_key_first($paths));
        $this->assertArrayNotHasKey('/ancien', $paths);
        $counts = array_values($paths);
        rsort($counts);
        $this->assertSame($counts, array_values($paths));
    }

    /** Vérifie que le dashboard affiche les chemins en 404. */
    public function testDashboardDisplaysNotFoundPaths(): void
    {
        Storage::fake('secrets');
        $this->travelTo('2026-09-15 16:00:00');

        DB::table('stats_not_found_paths')->insert([
            'date' => '2026-09-15',
            'path' => '/wp-login.php',
            'count' => 3,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->withSession($this->superAdminSession())->get('/fr/superadmin/dashboard');

        $response->assertSeeInOrder([__('messages.stat_404_paths', [], 'fr'), '/wp-login.php']);
    }
}
