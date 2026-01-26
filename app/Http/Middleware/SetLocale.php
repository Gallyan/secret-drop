<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    private const SUPPORTED_LOCALES = ['en', 'fr', 'de', 'es', 'it', 'pt', 'nl', 'pl', 'ja', 'ko', 'ar'];

    private const DEFAULT_LOCALE = 'fr';

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->detectLocale($request);
        app()->setLocale($locale);

        $response = $next($request);

        $response->headers->set('Content-Language', $locale);

        return $response;
    }

    private function detectLocale(Request $request): string
    {
        $acceptLanguage = $request->header('Accept-Language', '');

        if (empty($acceptLanguage)) {
            return self::DEFAULT_LOCALE;
        }

        $preferredLocales = $this->parseAcceptLanguage($acceptLanguage);

        foreach ($preferredLocales as $locale) {
            $shortLocale = substr($locale, 0, 2);
            if (in_array($shortLocale, self::SUPPORTED_LOCALES, true)) {
                return $shortLocale;
            }
        }

        return self::DEFAULT_LOCALE;
    }

    /**
     * @return array<int, string>
     */
    private function parseAcceptLanguage(string $header): array
    {
        $locales = [];

        foreach (explode(',', $header) as $part) {
            $part = trim($part);
            if (empty($part)) {
                continue;
            }

            $segments = explode(';', $part);
            $locale = trim($segments[0]);
            $quality = 1.0;

            if (isset($segments[1])) {
                $qPart = trim($segments[1]);
                if (str_starts_with($qPart, 'q=')) {
                    $quality = (float) substr($qPart, 2);
                }
            }

            $locales[$locale] = $quality;
        }

        arsort($locales);

        return array_keys($locales);
    }
}
