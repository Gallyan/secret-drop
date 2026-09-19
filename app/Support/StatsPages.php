<?php

namespace App\Support;

use Illuminate\Routing\Route;

/** Identifies the page behind a route for statistics and provides the labels shown on the superadmin dashboard. */
class StatsPages
{
    public const CONTENT_PAGE = 'page.show';

    public const UNKNOWN = 'unknown';

    /** @var array<string, string> */
    private const LABEL_KEYS = [
        'home' => 'messages.stat_page_home',
        'how-it-works' => 'messages.how_it_works_title',
        'use-cases' => 'messages.use_cases_title',
        'legal' => 'messages.legal_title',
        'faq' => 'messages.faq_title',
        self::CONTENT_PAGE => 'messages.stat_page_content',
        self::UNKNOWN => 'messages.stat_page_unknown',
        'contact.email' => 'messages.stat_page_contact',
        'sitemap' => 'messages.stat_page_sitemap',
        'secrets.store' => 'messages.stat_route_create',
        'secrets.show' => 'messages.view_secret_title',
        'secrets.fetch' => 'messages.stat_page_secret_fetch',
        'secrets.confirmRead' => 'messages.stat_route_confirm_read',
        'secrets.download' => 'messages.stat_page_download',
        'admin.index' => 'messages.stat_page_admin_login',
        'admin.requestAccess' => 'messages.stat_page_admin_request_access',
        'admin.accessSent' => 'messages.stat_page_admin_access_sent',
        'admin.verify' => 'messages.stat_page_admin_verify',
        'admin.dashboard' => 'messages.stat_page_admin_dashboard',
        'admin.poll' => 'messages.stat_page_admin_poll',
        'admin.extend' => 'messages.stat_page_admin_extend',
        'admin.revoke' => 'messages.stat_page_admin_revoke',
        'admin.logout' => 'messages.stat_page_admin_logout',
        'superadmin.index' => 'messages.stat_page_superadmin_login',
        'superadmin.requestAccess' => 'messages.stat_page_superadmin_request_access',
        'superadmin.accessSent' => 'messages.stat_page_superadmin_access_sent',
        'superadmin.verify' => 'messages.stat_page_superadmin_verify',
        'superadmin.dashboard' => 'messages.stat_page_superadmin_dashboard',
        'superadmin.poll' => 'messages.stat_page_superadmin_poll',
        'superadmin.logout' => 'messages.stat_page_superadmin_logout',
        // Legacy underscore variants
        'admin' => 'messages.stat_page_admin_login',
        'admin_dashboard' => 'messages.stat_page_admin_dashboard',
        'superadmin' => 'messages.stat_page_superadmin_login',
        'superadmin_dashboard' => 'messages.stat_page_superadmin_dashboard',
    ];

    /**
     * Page identifier of a route: its name, or the translatable page for localized content pages.
     *
     * Falls back to the generic content page rather than the requested slug, so user input is never stored.
     */
    public static function identify(?Route $route): ?string
    {
        $name = $route?->getName();

        if (! $name || str_starts_with($name, 'generated::')) {
            return null;
        }

        if ($name !== self::CONTENT_PAGE) {
            return $name;
        }

        $slug = $route->parameter('pageSlug');
        $locale = $route->parameter('locale', LocaleConfig::DEFAULT_LOCALE);

        if (! is_string($slug) || ! is_string($locale)) {
            return $name;
        }

        return LocaleConfig::findRouteBySlug($slug, $locale) ?? $name;
    }

    /**
     * Human-readable label of every page identifier, including the localized slugs recorded by older versions.
     *
     * @return array<string, string>
     */
    public static function labels(): array
    {
        $labels = array_map(fn (string $key): string => __($key), self::LABEL_KEYS);

        foreach (LocaleConfig::translatablePages() as $page) {
            foreach (LocaleConfig::SUPPORTED_LOCALES as $locale) {
                $labels[LocaleConfig::translatedSlug($page, $locale)] ??= $labels[$page] ?? $page;
            }
        }

        return $labels;
    }
}
