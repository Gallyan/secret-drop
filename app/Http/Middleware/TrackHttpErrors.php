<?php

namespace App\Http\Middleware;

use App\Services\StatsService;
use App\Support\NotFoundPath;
use App\Support\StatsPages;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Terminable middleware that records HTTP 4xx/5xx errors in stats_daily.
 * For 5xx errors, also tracks the offending route in stats_error_routes.
 * For 404 errors, also tracks the sanitized path in stats_not_found_paths.
 *
 * Enregistré en global : les middlewares de groupe ne s'exécutent pas pour une URL
 * sans route, dont les 404 ne seraient alors jamais comptées.
 */
class TrackHttpErrors
{
    private const NOT_FOUND_PATHS_PER_IP_PER_MINUTE = 30;

    public function __construct(
        private StatsService $stats,
    ) {
    }

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    /** Enregistre les compteurs après l'envoi de la réponse ; un échec des statistiques est signalé, jamais relancé. */
    public function terminate(Request $request, Response $response): void
    {
        $status = $response->getStatusCode();

        if ($status < 400) {
            return;
        }

        try {
            $this->record($request, $status);
        } catch (Throwable $e) {
            report($e);
        }
    }

    private function record(Request $request, int $status): void
    {
        if ($status >= 500) {
            $this->stats->incrementDailyAndHourly(StatsService::HTTP_ERRORS_5XX);

            $this->stats->trackErrorRoute($status, mb_substr($this->identifyRoute($request), 0, 100));
        }

        if ($status < 500) {
            $this->stats->incrementDailyAndHourly(StatsService::HTTP_ERRORS_4XX);
        }

        if ($status === 404) {
            $this->stats->trackNotFoundPath($this->notFoundPath($request));
        }

        $this->stats->increment("http_errors_{$status}");
    }

    /**
     * URLs without a route escape the group throttle, so each IP gets its own bound on the distinct paths it can
     * add; beyond it, its 404s are still counted, under the shared overflow row.
     */
    private function notFoundPath(Request $request): string
    {
        $key = 'not-found-paths:'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, self::NOT_FOUND_PATHS_PER_IP_PER_MINUTE)) {
            return StatsService::NOT_FOUND_OVERFLOW_PATH;
        }

        RateLimiter::hit($key, 60);

        return NotFoundPath::template($request);
    }

    /** Page identifier, or the route URI template: the actual path may carry secret or admin tokens. */
    private function identifyRoute(Request $request): string
    {
        $route = $request->route();

        if (! $route instanceof Route) {
            return StatsPages::UNKNOWN;
        }

        return StatsPages::identify($route) ?? $route->uri();
    }
}
