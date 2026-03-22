<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class TrackPageView
{
    /** @var array<int, string> */
    private const BOT_PATTERNS = [
        'bot', 'crawl', 'spider', 'slurp', 'mediapartners',
        'lighthouse', 'pagespeed', 'headlesschrome', 'phantomjs',
        'curl', 'wget', 'python-', 'go-http', 'java/', 'ruby',
        'perl', 'libwww', 'apache-http', 'node-fetch', 'axios',
        'postman', 'insomnia', 'httpclient', 'scrapy', 'semrush',
        'ahrefs', 'mj12bot', 'dotbot', 'yandex', 'baidu',
    ];

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

        $now = now();

        DB::table('stats_pageviews')->upsert(
            [
                'date' => $now->toDateString(),
                'page' => $page,
                'is_bot' => $this->isBot($request),
                'hour' => $now->hour,
                'country' => $this->detectCountry($request),
                'count' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            ['date', 'page', 'is_bot', 'hour', 'country'],
            ['count' => DB::raw('count + 1'), 'updated_at' => $now]
        );

        return $response;
    }

    private function identifyPage(Request $request): ?string
    {
        $route = $request->route();

        if (! $route) {
            return null;
        }

        $name = $route->getName();

        return match ($name) {
            'home' => 'home',
            'page.show' => $route->parameter('pageSlug', 'unknown'),
            'secrets.show' => 'secret_view',
            'admin.index' => 'admin',
            'admin.dashboard' => 'admin_dashboard',
            'superadmin.index' => 'superadmin',
            'superadmin.dashboard' => 'superadmin_dashboard',
            default => null,
        };
    }

    private function isBot(Request $request): bool
    {
        $userAgent = strtolower($request->userAgent() ?? '');

        if ($userAgent === '') {
            return true;
        }

        foreach (self::BOT_PATTERNS as $pattern) {
            if (str_contains($userAgent, $pattern)) {
                return true;
            }
        }

        return false;
    }

    private function detectCountry(Request $request): string
    {
        $acceptLanguage = $request->header('Accept-Language', '');

        if (empty($acceptLanguage)) {
            return 'XX';
        }

        $first = explode(',', $acceptLanguage)[0];
        $locale = trim(explode(';', $first)[0]);

        if (str_contains($locale, '-')) {
            $parts = explode('-', $locale);

            return strtoupper($parts[1]);
        }

        return strtoupper(substr($locale, 0, 2));
    }
}
