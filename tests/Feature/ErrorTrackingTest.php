<?php

namespace Tests\Feature;

use App\Services\StatsService;
use App\Support\StatsPages;
use Illuminate\Contracts\Foundation\MaintenanceMode;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\CacheBasedMaintenanceMode;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use RuntimeException;
use Tests\TestCase;

class ErrorTrackingTest extends TestCase
{
    /** @return array<string, int> */
    private function httpErrorCounters(): array
    {
        return DB::table('stats_daily')
            ->where('metric', 'like', 'http_errors_%')
            ->orderBy('metric')
            ->pluck('count', 'metric')
            ->map(fn (mixed $count): int => is_numeric($count) ? (int) $count : 0)
            ->all();
    }

    /** Vérifie qu'une URL sans route compte une seule 404, sans enregistrer de route. */
    public function testUnroutedUrlCountsOne404(): void
    {
        $this->get('/foo/bar/baz')->assertNotFound();

        $this->assertSame(
            [StatsService::HTTP_ERRORS_404 => 1, StatsService::HTTP_ERRORS_4XX => 1],
            $this->httpErrorCounters()
        );
        $this->assertDatabaseCount('stats_error_routes', 0);
    }

    /** Vérifie qu'une 404 incrémente aussi la heatmap horaire des 4xx. */
    public function testClientErrorCounts4xxHourly(): void
    {
        $this->travelTo('2026-09-15 14:30:00');

        $this->get('/fr/une-page-qui-nexiste-pas')->assertNotFound();

        $this->assertDatabaseHas('stats_heatmap', [
            'date' => '2026-09-15',
            'hour' => 14,
            'metric' => StatsService::HTTP_ERRORS_4XX,
            'count' => 1,
        ]);
    }

    /** Vérifie qu'une 404 sur une route existante est comptée une seule fois, en 4xx et jamais en 5xx. */
    public function testRouted404CountsOnceAs4xx(): void
    {
        $this->getJson('/api/secrets/'.str_repeat('a', 32))->assertNotFound();

        $this->assertSame(
            [StatsService::HTTP_ERRORS_404 => 1, StatsService::HTTP_ERRORS_4XX => 1],
            $this->httpErrorCounters()
        );
    }

    /** Vérifie que les erreurs de validation 422 sont trackées. */
    public function testTracks422ValidationErrors(): void
    {
        $this->postJson('/api/secrets', [
            'type' => 'text',
        ])->assertUnprocessable();

        $this->assertSame(
            [StatsService::HTTP_ERRORS_422 => 1, StatsService::HTTP_ERRORS_4XX => 1],
            $this->httpErrorCounters()
        );
    }

    /** Vérifie qu'un 429 est compté en 4xx et sous son propre code. */
    public function testTooManyRequestsCountsAs4xxAnd429(): void
    {
        Route::middleware(['web', 'throttle:1,1'])->get('/test-throttled', fn () => 'ok');

        $this->get('/test-throttled')->assertOk();
        $this->get('/test-throttled')->assertTooManyRequests();

        $this->assertSame(
            [StatsService::HTTP_ERRORS_429 => 1, StatsService::HTTP_ERRORS_4XX => 1],
            $this->httpErrorCounters()
        );
    }

    /** Vérifie que les réponses 200 ne sont pas trackées. */
    public function testDoesNotTrackSuccessfulResponses(): void
    {
        $this->get('/fr')->assertOk();

        $this->assertSame([], $this->httpErrorCounters());
        $this->assertDatabaseCount('stats_error_routes', 0);
    }

    /** Vérifie qu'une 500 incrémente les compteurs 5xx et 500 du jour et la heatmap horaire. */
    public function testServerErrorCounts5xxDailyAndHourly(): void
    {
        Exceptions::fake([RuntimeException::class]);
        $this->travelTo('2026-09-15 14:20:00');
        Route::middleware('web')->get('/test-5xx', function (): void {
            throw new RuntimeException('boom');
        });

        $this->get('/test-5xx')->assertInternalServerError();

        $this->assertSame(
            [StatsService::HTTP_ERRORS_500 => 1, StatsService::HTTP_ERRORS_5XX => 1],
            $this->httpErrorCounters()
        );
        $this->assertDatabaseHas('stats_heatmap', [
            'date' => '2026-09-15',
            'day_of_week' => 2,
            'hour' => 14,
            'metric' => StatsService::HTTP_ERRORS_5XX,
            'count' => 1,
        ]);
        $this->assertDatabaseHas('stats_error_routes', ['date' => '2026-09-15', 'status' => 500, 'route' => 'test-5xx']);
        Exceptions::assertReported(RuntimeException::class);
    }

    /** Vérifie qu'une 5xx sur une page de contenu enregistre la page réelle, pas page.show. */
    public function testContentPage5xxRecordsTheResolvedPage(): void
    {
        Exceptions::fake([RuntimeException::class]);
        View::composer('faq', function (): void {
            throw new RuntimeException('boom');
        });

        $this->get('/fr/faq')->assertInternalServerError();

        $this->assertSame(['faq'], DB::table('stats_error_routes')->pluck('route')->all());
        Exceptions::assertReported(RuntimeException::class);
    }

    /** Vérifie qu'une 5xx sur une route sans nom enregistre le gabarit d'URI, jamais le token de l'URL. */
    public function testUnnamedRoute5xxRecordsTheUriTemplateNotThePath(): void
    {
        Exceptions::fake([RuntimeException::class]);
        Route::middleware('web')->get('/test-5xx/{token}', function (): void {
            throw new RuntimeException('boom');
        });

        $this->get('/test-5xx/super-secret-token-value')->assertInternalServerError();

        $this->assertSame(['test-5xx/{token}'], DB::table('stats_error_routes')->pluck('route')->all());
        Exceptions::assertReported(RuntimeException::class);
    }

    /** Vérifie que le gabarit de route enregistré est tronqué à 100 caractères. */
    public function testLongRouteTemplateIsTruncatedTo100Characters(): void
    {
        Exceptions::fake([RuntimeException::class]);
        $template = 'test-'.str_repeat('x', 120).'/{token}';
        Route::middleware('web')->get("/{$template}", function (): void {
            throw new RuntimeException('boom');
        });

        $this->get('/test-'.str_repeat('x', 120).'/abc')->assertInternalServerError();

        $this->assertSame(['test-'.str_repeat('x', 95)], DB::table('stats_error_routes')->pluck('route')->all());
        Exceptions::assertReported(RuntimeException::class);
    }

    /** Vérifie qu'une 503 levée avant le routage enregistre la route inconnue, jamais le chemin demandé. */
    public function testServerErrorBeforeRoutingRecordsUnknownRoute(): void
    {
        $maintenance = new CacheBasedMaintenanceMode(app('cache'), 'array', 'test:down');
        $maintenance->activate([]);
        $this->app->instance(MaintenanceMode::class, $maintenance);

        $this->getJson('/api/secrets/'.str_repeat('b', 32))->assertServiceUnavailable();

        $this->assertSame(
            [['status' => 503, 'route' => StatsPages::UNKNOWN]],
            DB::table('stats_error_routes')->get(['status', 'route'])
                ->map(fn (object $row): array => ['status' => (int) $row->status, 'route' => $row->route])
                ->all()
        );
    }

    /** Vérifie qu'un échec d'écriture des statistiques d'erreur est signalé sans interrompre la requête. */
    public function testStatsWriteFailureIsReportedWithoutBreakingTheRequest(): void
    {
        Exceptions::fake([QueryException::class]);
        Schema::drop('stats_daily');

        $this->get('/foo/bar/baz')->assertNotFound();

        Exceptions::assertReported(QueryException::class);
    }
}
