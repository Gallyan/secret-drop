<?php

namespace App\Support;

/** Canonical list of publicly indexable URLs, shared by the sitemap and IndexNow submissions. */
class PublicUrls
{
    /**
     * Home page of every locale, then every translatable page of every locale.
     *
     * Mirrors exactly the <loc> entries rendered by resources/views/sitemap.blade.php.
     *
     * @return array<int, string>
     */
    public static function all(): array
    {
        $urls = [];

        foreach (LocaleConfig::SUPPORTED_LOCALES as $locale) {
            $urls[] = route('home', ['locale' => $locale]);
        }

        foreach (LocaleConfig::translatablePages() as $page) {
            foreach (LocaleConfig::SUPPORTED_LOCALES as $locale) {
                $urls[] = localized_route($page, $locale);
            }
        }

        return $urls;
    }
}
