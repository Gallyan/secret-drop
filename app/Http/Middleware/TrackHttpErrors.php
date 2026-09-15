<?php

namespace App\Http\Middleware;

use App\Services\StatsService;
use App\Support\StatsPages;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Terminable middleware that records HTTP 4xx/5xx errors in stats_daily.
 * For 5xx errors, also tracks the offending route in stats_error_routes.
 *
 * Enregistré en global : les middlewares de groupe ne s'exécutent pas pour une URL
 * sans route, dont les 404 ne seraient alors jamais comptées.
 */
class TrackHttpErrors
{
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

        $this->stats->increment("http_errors_{$status}");
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
