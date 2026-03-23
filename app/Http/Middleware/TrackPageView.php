<?php

namespace App\Http\Middleware;

use App\Services\PageviewService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class TrackPageView
{
    public function __construct(
        private PageviewService $pageviewService,
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $request->isMethod('GET') || $response->getStatusCode() !== 200) {
            return $response;
        }

        $page = $this->identifyPage($request);

        if (! $page) {
            return $response;
        }

        $this->pageviewService->track(
            $page,
            $request->userAgent() ?? '',
            $request->header('Accept-Language', ''),
            (int) $request->cookie('tz_offset', '0')
        );

        return $response;
    }

    private function identifyPage(Request $request): ?string
    {
        $route = $request->route();

        if (! $route || ! $route->getName()) {
            return null;
        }

        $name = $route->getName();

        if ($name === 'page.show') {
            return $route->parameter('pageSlug', 'unknown');
        }

        return Str::slug($name, '_');
    }
}
