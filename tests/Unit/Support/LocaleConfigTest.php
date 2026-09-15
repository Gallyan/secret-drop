<?php

namespace Tests\Unit\Support;

use App\Support\LocaleConfig;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class LocaleConfigTest extends TestCase
{
    /** Vérifie que translatedSlug() renvoie le slug de la locale demandée. */
    public function testTranslatedSlugReturnsTheSlugOfTheRequestedLocale(): void
    {
        $this->assertSame('juridische-kennisgeving', LocaleConfig::translatedSlug('legal', 'nl'));
    }

    /** Vérifie que translatedSlug() refuse une page non traduisible au lieu de renvoyer la clé de traduction comme slug. */
    public function testTranslatedSlugRejectsUnknownPage(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown translatable page [pricing].');

        LocaleConfig::translatedSlug('pricing', 'fr');
    }

    /** Vérifie que localized_route() refuse une page non traduisible au lieu de produire un lien 404. */
    public function testLocalizedRouteRejectsUnknownPage(): void
    {
        $this->expectException(InvalidArgumentException::class);

        localized_route('pricing', 'fr');
    }

    /** Vérifie que localized_route() utilise la locale courante quand aucune locale n'est fournie. */
    public function testLocalizedRouteUsesTheCurrentLocaleByDefault(): void
    {
        app()->setLocale('fr');

        $this->assertSame('http://localhost/fr/comment-ca-marche', localized_route('how-it-works'));
    }

    /** Vérifie que localized_route() utilise la locale explicite plutôt que la locale courante. */
    public function testLocalizedRouteUsesTheExplicitLocale(): void
    {
        app()->setLocale('fr');

        $this->assertSame('http://localhost/de/so-funktioniert-es', localized_route('how-it-works', 'de'));
    }

    /** Vérifie que findRouteBySlug() ne reconnaît que le slug de la locale donnée. */
    public function testFindRouteBySlugOnlyMatchesTheSlugOfTheGivenLocale(): void
    {
        $this->assertSame('how-it-works', LocaleConfig::findRouteBySlug('comment-ca-marche', 'fr'));
        $this->assertNull(LocaleConfig::findRouteBySlug('how-it-works', 'fr'));
    }

    /**
     * @return array<string, array{string, ?string}>
     */
    public static function slugsOfAnyLocale(): array
    {
        return [
            'german legal slug' => ['impressum', 'legal'],
            'slug shared by spanish and portuguese' => ['como-funciona', 'how-it-works'],
            'portuguese faq slug' => ['perguntas-frequentes', 'faq'],
            'unknown slug' => ['pricing', null],
        ];
    }

    /** Vérifie que findRouteBySlugAnyLocale() retrouve la page d'un slug quelle que soit sa locale. */
    #[DataProvider('slugsOfAnyLocale')]
    public function testFindRouteBySlugAnyLocaleResolvesTheSlugOfAnyLocale(string $slug, ?string $expectedPage): void
    {
        $this->assertSame($expectedPage, LocaleConfig::findRouteBySlugAnyLocale($slug));
    }
}
