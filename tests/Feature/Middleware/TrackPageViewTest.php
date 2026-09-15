<?php

namespace Tests\Feature\Middleware;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TrackPageViewTest extends TestCase
{
    private const HUMAN_UA = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120';

    /** Vérifie qu'une page rendue enregistre une vue avec sa route, son heure, son pays et sa locale d'URL. */
    public function testRecordsPageviewForRenderedPage(): void
    {
        $this->travelTo('2026-09-15 10:45:00');

        $this->withHeaders(['User-Agent' => self::HUMAN_UA, 'Accept-Language' => 'pt-BR,pt;q=0.9'])
            ->get('/de')
            ->assertOk();

        $this->assertDatabaseCount('stats_pageviews', 1);
        $this->assertDatabaseHas('stats_pageviews', [
            'date' => '2026-09-15',
            'page' => 'home',
            'is_bot' => false,
            'hour' => 10,
            'country' => 'BR',
            'locale' => 'de',
            'count' => 1,
        ]);
    }

    /** Vérifie qu'une page de contenu localisée est enregistrée sous la page traduisible, pas sous son slug. */
    public function testRecordsContentPageUnderItsPageIdentifier(): void
    {
        $this->withHeader('User-Agent', self::HUMAN_UA)->get('/fr/faq')->assertOk();

        $this->assertDatabaseHas('stats_pageviews', ['page' => 'faq', 'locale' => 'fr']);
    }

    /** Vérifie que la locale vient de la locale active quand la route n'a pas de paramètre locale. */
    public function testFallsBackToActiveLocaleWhenRouteHasNoLocale(): void
    {
        Route::middleware('web')->get('/test-no-locale', fn () => 'ok')->name('test.noLocale');

        $this->withHeaders(['User-Agent' => self::HUMAN_UA, 'Accept-Language' => 'it-IT'])
            ->get('/test-no-locale')
            ->assertOk();

        $this->assertDatabaseHas('stats_pageviews', ['page' => 'test.noLocale', 'locale' => 'it']);
    }

    /** @return array<string, array{string, int}> */
    public static function tzOffsetCookies(): array
    {
        return [
            'UTC+2' => ['-120', 12],
            'valeur non numérique' => ['abc', 10],
        ];
    }

    /** Vérifie que le cookie tz_offset non chiffré détermine l'heure locale enregistrée. */
    #[DataProvider('tzOffsetCookies')]
    public function testUsesTimezoneCookieForLocalHour(string $cookieValue, int $expectedLocalHour): void
    {
        $this->travelTo('2026-09-15 10:45:00');

        $this->withUnencryptedCookie('tz_offset', $cookieValue)
            ->withHeader('User-Agent', self::HUMAN_UA)
            ->get('/fr')
            ->assertOk();

        $this->assertDatabaseCount('stats_local_hours', 1);
        $this->assertDatabaseHas('stats_local_hours', ['local_hour' => $expectedLocalHour]);
    }

    /** Vérifie que le domaine de l'en-tête Referer est transmis au suivi des référents. */
    public function testPassesRefererDomain(): void
    {
        $this->withHeaders(['User-Agent' => self::HUMAN_UA, 'Referer' => 'https://www.example.org/some/article'])
            ->get('/fr')
            ->assertOk();

        $this->assertDatabaseHas('stats_referrers', ['referrer_domain' => 'example.org', 'is_bot' => false]);
    }

    /** Vérifie que les bots sont tracés comme vues marquées is_bot, avec leur nom. */
    public function testRecordsBotsAsFlaggedPageviews(): void
    {
        $this->withHeader('User-Agent', 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)')
            ->get('/fr')
            ->assertOk();

        $this->assertDatabaseHas('stats_pageviews', ['page' => 'home', 'is_bot' => true, 'count' => 1]);
        $this->assertDatabaseHas('stats_bots', ['bot_name' => 'Googlebot', 'count' => 1]);
    }

    /** Vérifie qu'une écriture réussie (POST 200) n'est pas une vue de page. */
    public function testIgnoresNonGetRequests(): void
    {
        Route::middleware('web')->post('/test-post', fn () => 'ok')->name('test.post');

        $this->withHeader('User-Agent', self::HUMAN_UA)->post('/test-post')->assertOk();

        $this->assertDatabaseCount('stats_pageviews', 0);
    }

    /** Vérifie qu'une redirection n'est pas une vue de page. */
    public function testIgnoresNonOkResponses(): void
    {
        $this->withHeader('User-Agent', self::HUMAN_UA)->get('/fr/admin/dashboard')->assertRedirect();

        $this->assertDatabaseCount('stats_pageviews', 0);
    }

    /** Vérifie qu'une requête ajax n'est pas une vue de page. */
    public function testIgnoresAjaxRequests(): void
    {
        $this->withHeaders(['User-Agent' => self::HUMAN_UA, 'X-Requested-With' => 'XMLHttpRequest'])
            ->get('/fr')
            ->assertOk();

        $this->assertDatabaseCount('stats_pageviews', 0);
    }

    /** Vérifie qu'une route sans nom n'est pas tracée, pour ne jamais stocker un chemin. */
    public function testIgnoresUnnamedRoutes(): void
    {
        Route::middleware('web')->get('/test-unnamed', fn () => 'ok');

        $this->withHeader('User-Agent', self::HUMAN_UA)->get('/test-unnamed')->assertOk();

        $this->assertDatabaseCount('stats_pageviews', 0);
    }

    /** Vérifie qu'un échec d'écriture des statistiques est signalé sans casser la page. */
    public function testStatsWriteFailureIsReportedWithoutBreakingThePage(): void
    {
        Exceptions::fake([QueryException::class]);
        Schema::drop('stats_pageviews');

        $this->withHeader('User-Agent', self::HUMAN_UA)->get('/fr')->assertOk();

        Exceptions::assertReported(QueryException::class);
    }
}
