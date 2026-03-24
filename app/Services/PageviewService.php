<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PageviewService
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

    public function track(string $page, string $userAgent, string $acceptLanguage, int $tzOffset = 0, string $locale = ''): void
    {
        $now = now();
        $isBot = $this->isBot($userAgent);

        DB::table('stats_pageviews')->upsert(
            [
                'date' => $now->toDateString(),
                'page' => $page,
                'is_bot' => $isBot,
                'hour' => $now->hour,
                'country' => $this->detectCountry($acceptLanguage),
                'locale' => $locale,
                'count' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            ['date', 'page', 'is_bot', 'hour', 'country', 'locale'],
            ['count' => DB::raw('count + 1'), 'updated_at' => $now]
        );

        if (! $isBot) {
            $localHour = $this->getLocalHour($now, $tzOffset);

            DB::table('stats_local_hours')->upsert(
                [
                    'date' => $now->toDateString(),
                    'local_hour' => $localHour,
                    'count' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                ['date', 'local_hour'],
                ['count' => DB::raw('count + 1'), 'updated_at' => $now]
            );
        }
    }

    public function isBot(string $userAgent): bool
    {
        $userAgent = strtolower($userAgent);

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

    private function detectCountry(string $acceptLanguage): string
    {
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

    private function getLocalHour(Carbon $now, int $tzOffset): int
    {
        $localHour = ($now->hour - (int) ($tzOffset / 60)) % 24;

        return ($localHour + 24) % 24;
    }
}
