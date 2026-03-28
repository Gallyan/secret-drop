<?php

namespace App\Http\Middleware;

use App\Services\StatsService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

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

    public function terminate(Request $request, Response $response): void
    {
        $status = $response->getStatusCode();

        if ($status < 400) {
            return;
        }

        if ($status >= 500) {
            $this->stats->increment(StatsService::HTTP_ERRORS_5XX);
        } else {
            $this->stats->increment(StatsService::HTTP_ERRORS_4XX);
        }

        match ($status) {
            404 => $this->stats->increment(StatsService::HTTP_ERRORS_404),
            422 => $this->stats->increment(StatsService::HTTP_ERRORS_422),
            429 => $this->stats->increment(StatsService::HTTP_ERRORS_429),
            500 => $this->stats->increment(StatsService::HTTP_ERRORS_500),
            default => null,
        };
    }
}
