<?php

namespace App\Http\Middleware;

use App\Support\CounterExpression;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Terminable middleware that records response time per route group in histogram buckets.
 * Buckets represent upper bounds in milliseconds (e.g. a 120 ms response lands in the 200 bucket).
 */
class TrackResponseTime
{
    private const BUCKETS = [50, 100, 200, 500, 1000, 2000, 5000, 10000];

    private const SECRET_ROUTE_GROUPS = [
        'secrets.store' => 'create',
        'secrets.show' => 'read',
        'secrets.fetch' => 'read',
        'secrets.confirmRead' => 'read',
        'secrets.download' => 'read',
        'secrets.revoke' => 'admin',
    ];

    /** Toute route admin.* ou superadmin.* appartient à son espace : une nouvelle route n'a pas besoin d'être déclarée. */
    private const AREA_PREFIXES = [
        'superadmin.' => 'superadmin',
        'admin.' => 'admin',
    ];

    private const DEFAULT_GROUP = 'pages';

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $request->attributes->set('_rt_start', now()->getPreciseTimestamp());

        return $next($request);
    }

    /** Enregistre la mesure après l'envoi de la réponse ; un échec des statistiques est signalé, jamais relancé. */
    public function terminate(Request $request, Response $response): void
    {
        $start = $request->attributes->get('_rt_start');

        if (! is_float($start)) {
            return;
        }

        $durationMs = (now()->getPreciseTimestamp() - $start) / 1000;
        $routeName = $request->route()?->getName() ?? '';

        try {
            $this->record(self::resolveGroup($routeName), self::resolveBucket($durationMs));
        } catch (Throwable $e) {
            report($e);
        }
    }

    /** Borne supérieure du premier seau qui contient la durée, plafonnée au dernier seau. */
    private static function resolveBucket(float $ms): int
    {
        foreach (self::BUCKETS as $bucket) {
            if ($ms <= $bucket) {
                return $bucket;
            }
        }

        return self::BUCKETS[array_key_last(self::BUCKETS)];
    }

    private static function resolveGroup(string $routeName): string
    {
        if (isset(self::SECRET_ROUTE_GROUPS[$routeName])) {
            return self::SECRET_ROUTE_GROUPS[$routeName];
        }

        foreach (self::AREA_PREFIXES as $prefix => $group) {
            if (str_starts_with($routeName, $prefix)) {
                return $group;
            }
        }

        return self::DEFAULT_GROUP;
    }

    private function record(string $group, int $bucket): void
    {
        $now = now();

        DB::table('stats_response_times')->upsert(
            [
                'date' => $now->toDateString(),
                'route_group' => $group,
                'bucket' => $bucket,
                'count' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            ['date', 'route_group', 'bucket'],
            ['count' => CounterExpression::addTo('stats_response_times', 1), 'updated_at' => $now]
        );
    }
}
