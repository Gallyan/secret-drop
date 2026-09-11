<?php

namespace Tests\Feature;

use App\Support\LocaleConfig;
use App\Support\StatsPages;
use Illuminate\Support\Facades\Route;
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
}
