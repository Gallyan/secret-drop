<?php

namespace Tests\Feature\Middleware;

use App\Http\Middleware\TrackResponseTime;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class TrackResponseTimeTest extends TestCase
{
    /** @return array<string, array{float, int}> */
    public static function durations(): array
    {
        return [
            'réponse instantanée' => [0.0, 50],
            'borne du premier seau incluse' => [50.0, 50],
            'juste au-dessus du premier seau' => [50.01, 100],
            'réponse de 120 ms' => [120.0, 200],
            'borne intermédiaire incluse' => [1000.0, 1000],
            'borne du dernier seau incluse' => [10000.0, 10000],
            'au-delà du dernier seau, ramenée au dernier' => [10000.5, 10000],
            'réponse de 60 s ramenée au dernier seau' => [60000.0, 10000],
        ];
    }

    /** Vérifie que la mesure enregistrée tombe dans le premier seau dont la borne supérieure contient la durée, plafonnée au dernier. */
    #[DataProvider('durations')]
    public function testRecordsBucketAsUpperBoundClampedToLast(float $milliseconds, int $expectedBucket): void
    {
        $this->travelTo('2026-09-15 10:00:00');
        $request = $this->timedRequestFor('home', milliseconds: $milliseconds);

        (new TrackResponseTime())->terminate($request, new Response());

        $this->assertSame(
            [$expectedBucket],
            DB::table('stats_response_times')->pluck('bucket')
                ->map(fn (mixed $bucket): int => is_numeric($bucket) ? (int) $bucket : 0)
                ->all()
        );
    }

    /** @return array<string, array{?string, string}> */
    public static function routeGroups(): array
    {
        return [
            'secrets.store' => ['secrets.store', 'create'],
            'secrets.show' => ['secrets.show', 'read'],
            'secrets.fetch' => ['secrets.fetch', 'read'],
            'secrets.confirmRead' => ['secrets.confirmRead', 'read'],
            'secrets.download' => ['secrets.download', 'read'],
            'admin.index' => ['admin.index', 'admin'],
            'admin.requestAccess' => ['admin.requestAccess', 'admin'],
            'admin.accessSent' => ['admin.accessSent', 'admin'],
            'admin.verify' => ['admin.verify', 'admin'],
            'admin.dashboard' => ['admin.dashboard', 'admin'],
            'admin.poll' => ['admin.poll', 'admin'],
            'admin.logout' => ['admin.logout', 'admin'],
            'admin.revoke' => ['admin.revoke', 'admin'],
            'admin.extend' => ['admin.extend', 'admin'],
            'superadmin.index' => ['superadmin.index', 'superadmin'],
            'superadmin.requestAccess' => ['superadmin.requestAccess', 'superadmin'],
            'superadmin.accessSent' => ['superadmin.accessSent', 'superadmin'],
            'superadmin.verify' => ['superadmin.verify', 'superadmin'],
            'superadmin.dashboard' => ['superadmin.dashboard', 'superadmin'],
            'superadmin.poll' => ['superadmin.poll', 'superadmin'],
            'superadmin.logout' => ['superadmin.logout', 'superadmin'],
            'home' => ['home', 'pages'],
            'page.show' => ['page.show', 'pages'],
            'sitemap' => ['sitemap', 'pages'],
            'contact.email' => ['contact.email', 'pages'],
            'route sans nom' => [null, 'pages'],
        ];
    }

    /** Vérifie le groupe de temps de réponse enregistré pour chaque route de l'application. */
    #[DataProvider('routeGroups')]
    public function testRecordsRouteGroupOfTheRoute(?string $routeName, string $expectedGroup): void
    {
        $this->travelTo('2026-09-15 10:00:00');
        $request = $this->timedRequestFor($routeName, milliseconds: 3000.0);

        (new TrackResponseTime())->terminate($request, new Response());

        $this->assertDatabaseCount('stats_response_times', 1);
        $this->assertDatabaseHas('stats_response_times', [
            'date' => '2026-09-15',
            'route_group' => $expectedGroup,
            'bucket' => 5000,
            'count' => 1,
        ]);
    }

    /** Vérifie que deux mesures du même groupe et du même seau incrémentent la même ligne. */
    public function testIncrementsExistingBucketRow(): void
    {
        $middleware = new TrackResponseTime();

        $middleware->terminate($this->timedRequestFor('home', milliseconds: 3000.0), new Response());
        $middleware->terminate($this->timedRequestFor('home', milliseconds: 3000.0), new Response());

        $this->assertDatabaseCount('stats_response_times', 1);
        $this->assertDatabaseHas('stats_response_times', ['route_group' => 'pages', 'bucket' => 5000, 'count' => 2]);
    }

    /** Vérifie que rien n'est enregistré quand l'heure de début n'a pas été posée par handle(). */
    public function testRecordsNothingWithoutStartTime(): void
    {
        $request = Request::create('/fr');

        (new TrackResponseTime())->terminate($request, new Response());

        $this->assertDatabaseCount('stats_response_times', 0);
    }

    /** Vérifie qu'une vraie requête web enregistre une mesure dans le groupe de sa route. */
    public function testRealRequestRecordsOneMeasure(): void
    {
        $this->get('/fr')->assertOk();

        $this->assertSame(
            ['pages' => 1],
            DB::table('stats_response_times')->pluck('count', 'route_group')
                ->map(fn (mixed $count): int => is_numeric($count) ? (int) $count : 0)
                ->all()
        );
    }

    /** Vérifie qu'un échec d'écriture de la mesure est signalé sans interrompre la requête. */
    public function testStatsWriteFailureIsReportedWithoutBreakingTheRequest(): void
    {
        Exceptions::fake([QueryException::class]);
        Schema::drop('stats_response_times');

        $this->get('/fr')->assertOk();

        Exceptions::assertReported(QueryException::class);
    }

    private function timedRequestFor(?string $routeName, float $milliseconds): Request
    {
        $request = Request::create('/test');
        $route = $routeName === null
            ? new RoutingRoute(['GET'], 'test-unnamed', fn () => 'ok')
            : Route::getRoutes()->getByName($routeName);

        $this->assertInstanceOf(RoutingRoute::class, $route);

        $request->setRouteResolver(fn () => $route);
        $request->attributes->set('_rt_start', now()->getPreciseTimestamp() - $milliseconds * 1000);

        return $request;
    }
}
