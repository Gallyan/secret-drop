<?php

namespace App\Http\Middleware;

use App\Services\StatsService;
use App\Support\StatsPages;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Symfony\Component\HttpFoundation\Response;

/**
 * Terminable middleware that records HTTP 4xx/5xx errors in stats_daily.
 * For 5xx errors, also tracks the offending route in stats_error_routes.
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

    /** Record error counters after the response has been sent. */
    public function terminate(Request $request, Response $response): void
    {
        $status = $response->getStatusCode();

        if ($status < 400) {
            return;
        }

        if ($status >= 500) {
            $this->stats->incrementDailyAndHourly(StatsService::HTTP_ERRORS_5XX);

            $this->stats->trackErrorRoute($status, mb_substr($this->identifyRoute($request), 0, 100));
        } else {
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
