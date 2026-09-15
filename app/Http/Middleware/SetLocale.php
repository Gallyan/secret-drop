<?php

namespace App\Http\Middleware;

use App\Support\LocaleConfig;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

/** Resolves the active locale from the URL prefix or Accept-Language header and sets it app-wide. */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->localeFromUrl($request) ?? $this->detectFromAcceptLanguage($request);

        app()->setLocale($locale);
        URL::defaults(['locale' => $locale]);

        $response = $next($request);

        $response->headers->set('Content-Language', $locale);

        return $response;
    }

    private function localeFromUrl(Request $request): ?string
    {
        $firstSegment = $request->segment(1);

        if ($firstSegment === null) {
            return null;
        }

        if (! LocaleConfig::isSupported($firstSegment)) {
            return null;
        }

        return $firstSegment;
    }

    private function detectFromAcceptLanguage(Request $request): string
    {
        $acceptLanguage = (string) $request->header('Accept-Language', '');

        foreach ($this->parseAcceptLanguage($acceptLanguage) as $language) {
            if (LocaleConfig::isSupported($language)) {
                return $language;
            }
        }

        return LocaleConfig::DEFAULT_LOCALE;
    }

    /**
     * Primary language subtags ordered by quality, highest first; q=0 means "not acceptable" (RFC 9110).
     *
     * @return array<int, string>
     */
    private function parseAcceptLanguage(string $header): array
    {
        $qualities = [];

        foreach (explode(',', strtolower($header)) as $part) {
            $parameters = array_map('trim', explode(';', $part));
            $range = array_shift($parameters);

            if ($range === '' || $range === '*') {
                continue;
            }

            $language = explode('-', str_replace('_', '-', $range))[0];
            $quality = $this->quality($parameters);

            if ($quality <= 0.0) {
                continue;
            }

            $qualities[$language] = max($quality, $qualities[$language] ?? 0.0);
        }

        arsort($qualities);

        return array_keys($qualities);
    }

    /**
     * @param  array<int, string>  $parameters
     */
    private function quality(array $parameters): float
    {
        foreach ($parameters as $parameter) {
            if (str_starts_with($parameter, 'q=')) {
                return (float) substr($parameter, 2);
            }
        }

        return 1.0;
    }
}
