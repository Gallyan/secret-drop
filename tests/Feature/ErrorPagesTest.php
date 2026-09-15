<?php

namespace Tests\Feature;

use Illuminate\Contracts\Foundation\MaintenanceMode;
use Illuminate\Foundation\CacheBasedMaintenanceMode;
use Tests\TestCase;

class ErrorPagesTest extends TestCase
{
    /** Vérifie que la page HTML 503 levée avant la résolution de la locale s'affiche avec ses liens vers l'accueil et l'admin de la locale par défaut. */
    public function testMaintenancePageRaisedBeforeLocaleResolutionRendersLocalizedLinks(): void
    {
        $maintenance = new CacheBasedMaintenanceMode(app('cache'), 'array', 'test:down');
        $maintenance->activate([]);
        $this->app->instance(MaintenanceMode::class, $maintenance);

        $response = $this->get('/s/'.str_repeat('b', 32));

        $response->assertServiceUnavailable();
        $response->assertSee('href="http://localhost/fr"', false);
        $response->assertSee('href="http://localhost/fr/admin"', false);
    }
}
