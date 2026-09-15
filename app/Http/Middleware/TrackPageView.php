<?php

namespace App\Http\Middleware;

use App\Services\PageviewService;
use App\Support\LocaleConfig;
use App\Support\StatsPages;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/** Fires a pageview tracking event after successful GET responses using the named route as page identifier. */
class TrackPageView
{
    public function __construct(
        private PageviewService $pageviewService,
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $request->isMethod('GET') || $response->getStatusCode() !== 200 || $request->ajax()) {
            return $response;
        }

        $page = StatsPages::identify($request->route());

        if (! $page) {
            return $response;
        }

        try {
            $this->pageviewService->track(
                $page,
                $request->userAgent() ?? '',
                $request->header('Accept-Language', ''),
                (int) $request->cookie('tz_offset', '0'),
                $this->extractLocale($request),
                $request->header('Referer', '')
            );
        } catch (Throwable $e) {
            report($e);
        }

        return $response;
    }

    private function extractLocale(Request $request): string
    {
        $route = $request->route();
        $locale = $route?->parameter('locale');

        if (is_string($locale) && LocaleConfig::isSupported($locale)) {
            return $locale;
        }

        return app()->getLocale();
    }
}
