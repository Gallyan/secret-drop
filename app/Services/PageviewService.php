<?php

namespace App\Services;

use App\Support\CounterExpression;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/** Records anonymous pageview statistics with bot detection, device classification, and referrer tracking. */
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
        // UAs sans mot-clé évident
        'googledocs', 'google web preview', 'prefetch proxy',
        'l9scan', 'paloaltonetworks', 'meta-web',
    ];

    /** @var array<string, string> */
    private const BOT_NAMES = [
        // Google (patterns spécifiques avant googlebot)
        'google-extended' => 'Google Gemini',
        'google-inspectiontool' => 'Google Inspection',
        'adsbot-google' => 'Google Ads',
        'mediapartners-google' => 'Google AdSense',
        'googlebot-image' => 'Google Images',
        'googlebot-video' => 'Google Video',
        'googlebot-news' => 'Google News',
        'googlebot' => 'Googlebot',
        'googleother' => 'Google Other',
        'googledocs' => 'Google Docs',
        'google web preview' => 'Google Preview',
        'prefetch proxy' => 'Chrome Prefetch',
        // Autres moteurs de recherche
        'bingbot' => 'Bingbot',
        'msnbot' => 'Bingbot',
        'slurp' => 'Yahoo',
        'duckduckbot' => 'DuckDuckGo',
        'qwantify' => 'Qwant',
        'mojeekbot' => 'Mojeek',
        'bravebot' => 'Brave',
        'kagibot' => 'Kagi',
        'youbot' => 'You.com',
        'baiduspider' => 'Baidu',
        'yandexbot' => 'Yandex',
        'yandex.com/bots' => 'Yandex',
        'seznambot' => 'Seznam',
        'yeti' => 'Naver',
        'sogou' => 'Sogou',
        // Apple (extended avant applebot)
        'applebot-extended' => 'Apple Intelligence',
        'applebot' => 'Apple',
        // IA / LLM
        'oai-searchbot' => 'OpenAI Search',
        'chatgpt-user' => 'OpenAI',
        'gptbot' => 'OpenAI',
        'anthropic-ai' => 'Anthropic Training',
        'claudebot' => 'Anthropic',
        'claude-web' => 'Anthropic',
        'perplexitybot' => 'Perplexity',
        'ccbot' => 'Common Crawl',
        'cohere-ai' => 'Cohere',
        'meta-externalagent' => 'Meta AI',
        'meta-webindexer' => 'Meta AI',
        'diffbot' => 'Diffbot',
        'mistralai-user' => 'Mistral',
        'amazonbot' => 'Amazon',
        'bytespider' => 'ByteDance',
        'tiktokspider' => 'TikTok',
        'petalbot' => 'Huawei',
        'linkupbot' => 'Linkup',
        'yisouspider' => 'Yisou',
        'coccocbot' => 'Cốc Cốc',
        // Réseaux sociaux
        'facebot' => 'Facebook',
        'facebookexternalhit' => 'Facebook',
        'twitterbot' => 'Twitter',
        'linkedinbot' => 'LinkedIn',
        'telegrambot' => 'Telegram',
        'whatsapp' => 'WhatsApp',
        'discordbot' => 'Discord',
        'slackbot' => 'Slack',
        'pinterestbot' => 'Pinterest',
        'redditbot' => 'Reddit',
        'blueskybot' => 'Bluesky',
        // SEO
        'semrushbot' => 'SEMrush',
        'ahrefsbot' => 'Ahrefs',
        'mj12bot' => 'Majestic',
        'dotbot' => 'Moz',
        'rogerbot' => 'Moz',
        'blexbot' => 'WebMeUp',
        'seranking' => 'SE Ranking',
        'serpstatbot' => 'Serpstat',
        'dataforseobot' => 'DataForSEO',
        'halobot' => 'Haloscan',
        'barkrowler' => 'Babbar',
        'iboubot' => 'Ibou',
        'awariobot' => 'Awario',
        'imagesiftbot' => 'ImageSift',
        // Monitoring
        'uptimerobot' => 'UptimeRobot',
        'feedly' => 'Feedly',
        // Sécurité
        'l9scan' => 'LeakIX',
        'leakix' => 'LeakIX',
        'bitsightbot' => 'BitSight',
        'paloaltonetworks' => 'Palo Alto',
        'aliyunsecbot' => 'Aliyun Security',
        // Divers
        '360spider' => 'Qihoo 360',
        'zoominfobot' => 'ZoomInfo',
        'semanticscholar' => 'Semantic Scholar',
        'bnf.fr_bot' => 'BnF',
        'snap url preview' => 'Snapchat',
        // Outils
        'lighthouse' => 'Lighthouse',
        'pagespeed' => 'PageSpeed',
        'headlesschrome' => 'HeadlessChrome',
        'curl' => 'curl',
        'wget' => 'wget',
        'python-requests' => 'Python',
        'python-urllib' => 'Python',
        'scrapy' => 'Scrapy',
        'go-http-client' => 'Go',
        'node-fetch' => 'Node.js',
        'axios' => 'Axios',
        'postman' => 'Postman',
    ];

    /** @var array<string, string> */
    private const AI_APP_PATTERNS = [
        'chatgpt/' => '(chatgpt-app)',
        'perplexity/' => '(perplexity-app)',
        'claude/' => '(claude-app)',
    ];

    public function track(string $page, string $userAgent, string $acceptLanguage, int $tzOffset = 0, string $locale = '', string $referrer = ''): void
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
            ['count' => CounterExpression::addTo('stats_pageviews', 1), 'updated_at' => $now]
        );

        if ($isBot) {
            $botName = $this->identifyBot($userAgent);

            DB::table('stats_bots')->upsert(
                [
                    'date' => $now->toDateString(),
                    'bot_name' => $botName,
                    'count' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                ['date', 'bot_name'],
                ['count' => CounterExpression::addTo('stats_bots', 1), 'updated_at' => $now]
            );
        }

        if (! $isBot) {
            DB::table('stats_devices')->upsert(
                [
                    'date' => $now->toDateString(),
                    'device_type' => $this->detectDevice($userAgent),
                    'count' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                ['date', 'device_type'],
                ['count' => CounterExpression::addTo('stats_devices', 1), 'updated_at' => $now]
            );

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
                ['count' => CounterExpression::addTo('stats_local_hours', 1), 'updated_at' => $now]
            );
        }

        $aiApp = $this->detectAiApp($userAgent);
        $domain = $this->extractReferrerDomain($referrer);

        if ($aiApp !== null) {
            $domain = $aiApp;
        } elseif ($domain === '') {
            $domain = '(direct)';
        }

        DB::table('stats_referrers')->upsert(
            [
                'date' => $now->toDateString(),
                'referrer_domain' => $domain,
                'is_bot' => $isBot,
                'count' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            ['date', 'referrer_domain', 'is_bot'],
            ['count' => CounterExpression::addTo('stats_referrers', 1), 'updated_at' => $now]
        );
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

    public function detectAiApp(string $userAgent): ?string
    {
        $ua = strtolower($userAgent);

        foreach (self::AI_APP_PATTERNS as $pattern => $domain) {
            if (str_contains($ua, $pattern)) {
                return $domain;
            }
        }

        return null;
    }

    public function detectDevice(string $userAgent): string
    {
        $ua = strtolower($userAgent);

        if (str_contains($ua, 'tablet') || str_contains($ua, 'ipad')) {
            return 'tablet';
        }

        if (str_contains($ua, 'mobile') || str_contains($ua, 'android') || str_contains($ua, 'iphone')) {
            return 'mobile';
        }

        return 'desktop';
    }

    public function identifyBot(string $userAgent): string
    {
        $ua = strtolower($userAgent);

        foreach (self::BOT_NAMES as $pattern => $name) {
            if (str_contains($ua, $pattern)) {
                return $name;
            }
        }

        return 'Other';
    }

    private const MIN_TZ_OFFSET_MINUTES = -840;

    private const MAX_TZ_OFFSET_MINUTES = 720;

    /** @var array<string, string> */
    private const LANG_TO_COUNTRY = [
        'en' => 'US',
        'fr' => 'FR',
        'de' => 'DE',
        'es' => 'ES',
        'it' => 'IT',
        'pt' => 'PT',
        'nl' => 'NL',
        'pl' => 'PL',
        'ja' => 'JP',
        'ko' => 'KR',
        'ar' => 'SA',
        'zh' => 'CN',
        'ru' => 'RU',
        'sv' => 'SE',
        'da' => 'DK',
        'fi' => 'FI',
        'nb' => 'NO',
        'uk' => 'UA',
        'cs' => 'CZ',
        'el' => 'GR',
        'he' => 'IL',
        'hi' => 'IN',
        'th' => 'TH',
        'vi' => 'VN',
        'tr' => 'TR',
        'id' => 'ID',
        'ms' => 'MY',
        'ro' => 'RO',
        'hu' => 'HU',
    ];

    /**
     * Pays de la langue préférée, en code de deux lettres comme la colonne l'exige.
     *
     * Seul un sous-tag de région de deux lettres est un pays : script (zh-Hant), région
     * ONU M.49 (es-419) et variantes sont ignorés, avec repli sur la langue principale.
     */
    private function detectCountry(string $acceptLanguage): string
    {
        $firstRange = explode(',', $acceptLanguage)[0];
        $subtags = explode('-', trim(explode(';', $firstRange)[0]));
        $language = strtolower(array_shift($subtags));

        foreach ($subtags as $subtag) {
            if (strlen($subtag) === 1) {
                break;
            }

            if (preg_match('/^[A-Za-z]{2}$/', $subtag) === 1) {
                return strtoupper($subtag);
            }
        }

        return self::LANG_TO_COUNTRY[$language] ?? 'XX';
    }

    /**
     * Heure locale d'après Date.getTimezoneOffset() du navigateur (minutes, positives à l'ouest d'UTC).
     *
     * Un décalage hors de la plage réelle (UTC-12 à UTC+14) vient d'un cookie falsifié : il est ignoré.
     */
    private function getLocalHour(Carbon $now, int $tzOffset): int
    {
        if ($tzOffset < self::MIN_TZ_OFFSET_MINUTES || $tzOffset > self::MAX_TZ_OFFSET_MINUTES) {
            $tzOffset = 0;
        }

        $minutesSinceMidnight = $now->hour * 60 + $now->minute - $tzOffset;

        return intdiv(($minutesSinceMidnight % 1440 + 1440) % 1440, 60);
    }

    private function extractReferrerDomain(string $referrer): string
    {
        if ($referrer === '') {
            return '';
        }

        $host = parse_url($referrer, PHP_URL_HOST);

        if (! $host) {
            return '';
        }

        $host = strtolower($host);

        if (str_starts_with($host, 'www.')) {
            $host = substr($host, 4);
        }

        $appHost = parse_url(config_string('app.url'), PHP_URL_HOST);

        if ($appHost) {
            $appHost = strtolower($appHost);

            if (str_starts_with($appHost, 'www.')) {
                $appHost = substr($appHost, 4);
            }

            $local = ['localhost', '127.0.0.1', '[::1]'];

            if ($host === $appHost || (in_array($host, $local, true) && in_array($appHost, $local, true))) {
                return '';
            }
        }

        return mb_substr($host, 0, 100);
    }
}
