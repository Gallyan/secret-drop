<?php

namespace Tests\Feature;

use Dom\Element;
use Dom\HTMLDocument;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class LocalizedRoutingTest extends TestCase
{
    private const HOME_URLS = [
        'en' => 'http://localhost/en',
        'fr' => 'http://localhost/fr',
        'de' => 'http://localhost/de',
        'es' => 'http://localhost/es',
        'it' => 'http://localhost/it',
        'pt' => 'http://localhost/pt',
        'nl' => 'http://localhost/nl',
        'pl' => 'http://localhost/pl',
        'ja' => 'http://localhost/ja',
        'ko' => 'http://localhost/ko',
        'ar' => 'http://localhost/ar',
    ];

    private const HOW_IT_WORKS_URLS = [
        'en' => 'http://localhost/en/how-it-works',
        'fr' => 'http://localhost/fr/comment-ca-marche',
        'de' => 'http://localhost/de/so-funktioniert-es',
        'es' => 'http://localhost/es/como-funciona',
        'it' => 'http://localhost/it/come-funziona',
        'pt' => 'http://localhost/pt/como-funciona',
        'nl' => 'http://localhost/nl/hoe-het-werkt',
        'pl' => 'http://localhost/pl/jak-to-dziala',
        'ja' => 'http://localhost/ja/how-it-works',
        'ko' => 'http://localhost/ko/how-it-works',
        'ar' => 'http://localhost/ar/how-it-works',
    ];

    /**
     * @return array<string, array{string, string}>
     */
    public static function localizedHomes(): array
    {
        return [
            'en' => ['/en', 'en'],
            'fr' => ['/fr', 'fr'],
            'de' => ['/de', 'de'],
            'es' => ['/es', 'es'],
            'it' => ['/it', 'it'],
            'pt' => ['/pt', 'pt'],
            'nl' => ['/nl', 'nl'],
            'pl' => ['/pl', 'pl'],
            'ja' => ['/ja', 'ja'],
            'ko' => ['/ko', 'ko'],
            'ar' => ['/ar', 'ar'],
        ];
    }

    /** Vérifie que l'accueil de chaque locale rend le formulaire de création dans la langue de l'URL. */
    #[DataProvider('localizedHomes')]
    public function testHomePageRendersForAllLocales(string $uri, string $locale): void
    {
        $response = $this->get($uri);

        $response->assertOk();
        $response->assertViewIs('secrets.create');
        $this->assertSame($locale, $this->document($response->getContent())->documentElement->getAttribute('lang'));
    }

    /**
     * @return array<string, array{string, string, string}>
     */
    public static function translatablePagesOfEveryLocale(): array
    {
        return [
            'en how-it-works' => ['/en/how-it-works', 'how-it-works', 'en'],
            'fr how-it-works' => ['/fr/comment-ca-marche', 'how-it-works', 'fr'],
            'de how-it-works' => ['/de/so-funktioniert-es', 'how-it-works', 'de'],
            'es how-it-works' => ['/es/como-funciona', 'how-it-works', 'es'],
            'it how-it-works' => ['/it/come-funziona', 'how-it-works', 'it'],
            'pt how-it-works' => ['/pt/como-funciona', 'how-it-works', 'pt'],
            'nl how-it-works' => ['/nl/hoe-het-werkt', 'how-it-works', 'nl'],
            'pl how-it-works' => ['/pl/jak-to-dziala', 'how-it-works', 'pl'],
            'ja how-it-works' => ['/ja/how-it-works', 'how-it-works', 'ja'],
            'ko how-it-works' => ['/ko/how-it-works', 'how-it-works', 'ko'],
            'ar how-it-works' => ['/ar/how-it-works', 'how-it-works', 'ar'],
            'en use-cases' => ['/en/use-cases', 'use-cases', 'en'],
            'fr use-cases' => ['/fr/cas-d-usage', 'use-cases', 'fr'],
            'de use-cases' => ['/de/anwendungsfaelle', 'use-cases', 'de'],
            'es use-cases' => ['/es/casos-de-uso', 'use-cases', 'es'],
            'it use-cases' => ['/it/casi-d-uso', 'use-cases', 'it'],
            'pt use-cases' => ['/pt/casos-de-uso', 'use-cases', 'pt'],
            'nl use-cases' => ['/nl/gebruikssituaties', 'use-cases', 'nl'],
            'pl use-cases' => ['/pl/przypadki-uzycia', 'use-cases', 'pl'],
            'ja use-cases' => ['/ja/use-cases', 'use-cases', 'ja'],
            'ko use-cases' => ['/ko/use-cases', 'use-cases', 'ko'],
            'ar use-cases' => ['/ar/use-cases', 'use-cases', 'ar'],
            'en legal' => ['/en/legal-notice', 'legal', 'en'],
            'fr legal' => ['/fr/mentions-legales', 'legal', 'fr'],
            'de legal' => ['/de/impressum', 'legal', 'de'],
            'es legal' => ['/es/aviso-legal', 'legal', 'es'],
            'it legal' => ['/it/avviso-legale', 'legal', 'it'],
            'pt legal' => ['/pt/aviso-legal', 'legal', 'pt'],
            'nl legal' => ['/nl/juridische-kennisgeving', 'legal', 'nl'],
            'pl legal' => ['/pl/oswiadczenie-prawne', 'legal', 'pl'],
            'ja legal' => ['/ja/legal-notice', 'legal', 'ja'],
            'ko legal' => ['/ko/legal-notice', 'legal', 'ko'],
            'ar legal' => ['/ar/legal-notice', 'legal', 'ar'],
            'en faq' => ['/en/faq', 'faq', 'en'],
            'fr faq' => ['/fr/faq', 'faq', 'fr'],
            'de faq' => ['/de/faq', 'faq', 'de'],
            'es faq' => ['/es/preguntas-frecuentes', 'faq', 'es'],
            'it faq' => ['/it/faq', 'faq', 'it'],
            'pt faq' => ['/pt/perguntas-frequentes', 'faq', 'pt'],
            'nl faq' => ['/nl/faq', 'faq', 'nl'],
            'pl faq' => ['/pl/faq', 'faq', 'pl'],
            'ja faq' => ['/ja/faq', 'faq', 'ja'],
            'ko faq' => ['/ko/faq', 'faq', 'ko'],
            'ar faq' => ['/ar/faq', 'faq', 'ar'],
        ];
    }

    /** Vérifie que chaque page traduisible rend sa vue dans la langue de l'URL pour chaque locale, via son slug traduit. */
    #[DataProvider('translatablePagesOfEveryLocale')]
    public function testAllTranslatablePagesWorkForAllLocales(string $uri, string $view, string $locale): void
    {
        $response = $this->get($uri);

        $response->assertOk();
        $response->assertViewIs($view);
        $this->assertSame($locale, $this->document($response->getContent())->documentElement->getAttribute('lang'));
    }

    /** Vérifie qu'une locale non supportée ne correspond à aucune route. */
    public function testInvalidLocaleDoesNotMatchRoute(): void
    {
        $response = $this->get('/xx/how-it-works');

        $response->assertNotFound();
    }

    /** Vérifie que l'en-tête Content-Language reprend la locale de l'URL. */
    public function testContentLanguageHeaderMatchesLocale(): void
    {
        $response = $this->get('/de');

        $response->assertHeader('Content-Language', 'de');
    }

    /** Vérifie que l'accueil déclare canonical, Open Graph et les 11 alternates hreflang plus x-default vers le français. */
    public function testHomeHeadDeclaresCanonicalOpenGraphAndHreflangAlternates(): void
    {
        $document = $this->document($this->get('/ja')->getContent());

        $this->assertSame('http://localhost/ja', $this->attribute($document, 'head link[rel="canonical"]', 'href'));
        $this->assertSame('http://localhost/ja', $this->attribute($document, 'head meta[property="og:url"]', 'content'));
        $this->assertSame('ja', $this->attribute($document, 'head meta[property="og:locale"]', 'content'));
        $this->assertNull($document->querySelector('head meta[name="robots"]'));
        $this->assertSame(
            [...self::HOME_URLS, 'x-default' => 'http://localhost/fr'],
            $this->hreflangAlternates($document),
        );
    }

    /** Vérifie qu'une page traduite déclare un canonical sans query string, Open Graph et les alternates vers chaque slug traduit. */
    public function testLocalizedPageHeadDeclaresCanonicalOpenGraphAndTranslatedAlternates(): void
    {
        $document = $this->document($this->get('/de/so-funktioniert-es?utm_source=newsletter')->getContent());

        $this->assertSame('http://localhost/de/so-funktioniert-es', $this->attribute($document, 'head link[rel="canonical"]', 'href'));
        $this->assertSame('http://localhost/de/so-funktioniert-es', $this->attribute($document, 'head meta[property="og:url"]', 'content'));
        $this->assertSame('de', $this->attribute($document, 'head meta[property="og:locale"]', 'content'));
        $this->assertNull($document->querySelector('head meta[name="robots"]'));
        $this->assertSame(
            [...self::HOW_IT_WORKS_URLS, 'x-default' => 'http://localhost/fr/comment-ca-marche'],
            $this->hreflangAlternates($document),
        );
    }

    /**
     * @return array<string, array{string}>
     */
    public static function sensitivePages(): array
    {
        return [
            'admin' => ['/fr/admin'],
            'superadmin' => ['/en/superadmin'],
            'secret reading page' => ['/s/'.str_repeat('a', 32)],
        ];
    }

    /** Vérifie que les pages sensibles sont en noindex, nofollow et n'exposent ni canonical, ni hreflang, ni Open Graph. */
    #[DataProvider('sensitivePages')]
    public function testSensitivePageIsNoindexWithoutCanonicalNorAlternates(string $uri): void
    {
        $response = $this->get($uri);

        $response->assertOk();
        $document = $this->document($response->getContent());
        $this->assertSame('noindex, nofollow', $this->attribute($document, 'head meta[name="robots"]', 'content'));
        $this->assertNull($document->querySelector('link[rel="canonical"]'));
        $this->assertSame([], $this->hreflangAlternates($document));
        $this->assertNull($document->querySelector('meta[property="og:url"]'));
    }

    /** Vérifie que hreflang_tags() ne produit rien sur une route qui n'est ni l'accueil ni une page traduisible. */
    public function testHreflangTagsHelperIsEmptyOutsideHomeAndTranslatablePages(): void
    {
        $this->get('/fr/admin');

        $this->assertSame('', hreflang_tags());
    }

    /** Vérifie qu'un slug inconnu répond 404 sans alternates hreflang. */
    public function testUnknownSlugPageHasNoHreflangAlternates(): void
    {
        $response = $this->get('/fr/nonexistent-page');

        $response->assertNotFound();
        $this->assertSame([], $this->hreflangAlternates($this->document($response->getContent())));
    }

    /** Vérifie que le sélecteur de langue de l'accueil pointe vers l'accueil de chaque locale. */
    public function testLanguageSwitcherOnHomePointsToEveryLocalizedHome(): void
    {
        $document = $this->document($this->get('/fr')->getContent());

        $this->assertSame(self::HOME_URLS, $this->languageSwitcherLinks($document));
    }

    /** Vérifie que le sélecteur de langue d'une page traduite pointe vers la même page dans chaque locale. */
    public function testLanguageSwitcherOnLocalizedPagePointsToTranslatedPages(): void
    {
        $document = $this->document($this->get('/fr/comment-ca-marche')->getContent());

        $this->assertSame(self::HOW_IT_WORKS_URLS, $this->languageSwitcherLinks($document));
    }

    /** Vérifie que le sélecteur de langue d'une route à paramètre locale garde la même route dans chaque locale. */
    public function testLanguageSwitcherOnAdminKeepsTheAdminRoute(): void
    {
        $document = $this->document($this->get('/fr/admin')->getContent());

        $this->assertSame([
            'en' => 'http://localhost/en/admin',
            'fr' => 'http://localhost/fr/admin',
            'de' => 'http://localhost/de/admin',
            'es' => 'http://localhost/es/admin',
            'it' => 'http://localhost/it/admin',
            'pt' => 'http://localhost/pt/admin',
            'nl' => 'http://localhost/nl/admin',
            'pl' => 'http://localhost/pl/admin',
            'ja' => 'http://localhost/ja/admin',
            'ko' => 'http://localhost/ko/admin',
            'ar' => 'http://localhost/ar/admin',
        ], $this->languageSwitcherLinks($document));
    }

    private function document(string $html): HTMLDocument
    {
        return HTMLDocument::createFromString($html, LIBXML_NOERROR);
    }

    private function attribute(HTMLDocument $document, string $selector, string $attribute): ?string
    {
        return $document->querySelector($selector)?->getAttribute($attribute);
    }

    /**
     * Alternate links of the <head>, keyed by hreflang.
     *
     * @return array<string, string>
     */
    private function hreflangAlternates(HTMLDocument $document): array
    {
        $alternates = [];

        foreach ($document->querySelectorAll('head link[rel="alternate"][hreflang]') as $link) {
            $alternates[(string) $link->getAttribute('hreflang')] = (string) $link->getAttribute('href');
        }

        return $alternates;
    }

    /**
     * Links of the first language switcher of the page, keyed by locale.
     *
     * The palette sits in an Alpine teleport <template>, whose content the DOM parser keeps out of the tree.
     *
     * @return array<string, string>
     */
    private function languageSwitcherLinks(HTMLDocument $document): array
    {
        $palette = $document->querySelector('[x-data="languageSwitcher"] template');
        $this->assertInstanceOf(Element::class, $palette);
        $links = [];

        foreach ($this->document("<!DOCTYPE html><body>{$palette->innerHTML}")->querySelectorAll('a[data-locale]') as $link) {
            $links[(string) $link->getAttribute('data-locale')] = (string) $link->getAttribute('href');
        }

        return $links;
    }
}
