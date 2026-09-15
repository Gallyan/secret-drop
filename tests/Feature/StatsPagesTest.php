<?php

namespace Tests\Feature;

use App\Support\LocaleConfig;
use App\Support\StatsPages;
use Illuminate\Http\Request;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use stdClass;
use Tests\TestCase;

class StatsPagesTest extends TestCase
{
    /** Vérifie que chaque route nommée de l'application a un libellé dans les stats. */
    public function testEveryApplicationRouteHasALabel(): void
    {
        $labels = StatsPages::labels();

        foreach (Route::getRoutes()->getRoutes() as $route) {
            $name = $route->getName();

            if (! $name || str_starts_with($name, 'generated::') || str_starts_with($route->getActionName(), 'Laravel\\')) {
                continue;
            }

            $this->assertArrayHasKey($name, $labels, "La route {$name} n'a pas de libellé");
        }
    }

    /** Vérifie que tous les libellés sont traduits dans chaque langue. */
    public function testEveryLabelIsTranslatedInEveryLocale(): void
    {
        foreach (LocaleConfig::SUPPORTED_LOCALES as $locale) {
            app()->setLocale($locale);

            foreach (StatsPages::labels() as $page => $label) {
                $this->assertStringStartsNotWith('messages.', $label, "Libellé {$page} non traduit en {$locale}");
            }
        }
    }

    /** Vérifie que les slugs localisés enregistrés par les anciennes versions gardent leur libellé. */
    public function testLegacyLocalizedSlugsShareThePageLabel(): void
    {
        $labels = StatsPages::labels();

        $this->assertSame($labels['how-it-works'], $labels['comment-ca-marche']);
    }

    /** Vérifie que les routes API sont nommées, pour les stats d'erreurs et de temps de réponse. */
    public function testApiRoutesAreNamed(): void
    {
        $this->assertTrue(Route::has(['secrets.store', 'secrets.fetch', 'secrets.confirmRead', 'secrets.revoke']));
    }

    /** @return array<string, array{string, string, ?string, string|null}> */
    public static function routesToIdentify(): array
    {
        return [
            'route nommée' => ['{locale}/admin', '/fr/admin', 'admin.index', 'admin.index'],
            'route sans nom' => ['test/{token}', '/test/abc', null, null],
            'route générée par le framework' => ['up', '/up', 'generated::a1b2c3', null],
            'page de contenu, slug de la locale' => ['{locale}/{pageSlug}', '/fr/comment-ca-marche', StatsPages::CONTENT_PAGE, 'how-it-works'],
            'page de contenu, slug d\'une autre locale' => ['{locale}/{pageSlug}', '/fr/so-funktioniert-es', StatsPages::CONTENT_PAGE, StatsPages::CONTENT_PAGE],
            'page de contenu, slug inconnu jamais stocké' => ['{locale}/{pageSlug}', '/fr/slug-saisi-par-un-visiteur', StatsPages::CONTENT_PAGE, StatsPages::CONTENT_PAGE],
            'page de contenu sans locale, locale par défaut' => ['{pageSlug}', '/mentions-legales', StatsPages::CONTENT_PAGE, 'legal'],
        ];
    }

    /** Vérifie l'identifiant de page retenu pour une route liée à une requête. */
    #[DataProvider('routesToIdentify')]
    public function testIdentifiesPageOfRoute(string $uri, string $path, ?string $name, ?string $expectedPage): void
    {
        $route = $this->boundRoute($uri, $path, $name);

        $this->assertSame($expectedPage, StatsPages::identify($route));
    }

    /** Vérifie qu'aucune route ne donne aucun identifiant. */
    public function testIdentifiesNothingWithoutRoute(): void
    {
        $this->assertNull(StatsPages::identify(null));
    }

    /** Vérifie qu'un slug ou une locale liés à un objet plutôt qu'à une chaîne retombent sur la page de contenu générique. */
    public function testFallsBackToContentPageForNonStringParameters(): void
    {
        $slugRoute = $this->boundRoute('{locale}/{pageSlug}', '/fr/faq', StatsPages::CONTENT_PAGE);
        $slugRoute->setParameter('pageSlug', new stdClass());
        $localeRoute = $this->boundRoute('{locale}/{pageSlug}', '/fr/faq', StatsPages::CONTENT_PAGE);
        $localeRoute->setParameter('locale', new stdClass());

        $this->assertSame(StatsPages::CONTENT_PAGE, StatsPages::identify($slugRoute));
        $this->assertSame(StatsPages::CONTENT_PAGE, StatsPages::identify($localeRoute));
    }

    private function boundRoute(string $uri, string $path, ?string $name): RoutingRoute
    {
        $route = new RoutingRoute(['GET'], $uri, fn () => 'ok');

        if ($name !== null) {
            $route->name($name);
        }

        return $route->bind(Request::create($path));
    }
}
