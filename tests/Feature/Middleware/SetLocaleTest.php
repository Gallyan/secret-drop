<?php

namespace Tests\Feature\Middleware;

use App\Http\Middleware\SetLocale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SetLocaleTest extends TestCase
{
    /**
     * @return array<string, array{string, string}>
     */
    public static function acceptLanguageHeaders(): array
    {
        return [
            'english' => ['en', 'en'],
            'french' => ['fr', 'fr'],
            'german' => ['de', 'de'],
            'spanish' => ['es', 'es'],
            'italian' => ['it', 'it'],
            'portuguese' => ['pt', 'pt'],
            'dutch' => ['nl', 'nl'],
            'polish' => ['pl', 'pl'],
            'japanese' => ['ja', 'ja'],
            'korean' => ['ko', 'ko'],
            'arabic' => ['ar', 'ar'],
            'empty header falls back to french' => ['', 'fr'],
            'only unsupported languages fall back to french' => ['zh,ru,th', 'fr'],
            'english regional variant' => ['en-US,en;q=0.9', 'en'],
            'french regional variant' => ['fr-CA,fr;q=0.9', 'fr'],
            'underscore regional variant' => ['pt_BR', 'pt'],
            'highest quality wins over header order' => ['zh;q=0.9,en;q=0.8,fr;q=0.7', 'en'],
            'first listed wins at equal quality' => ['en,fr', 'en'],
            'complex browser header' => ['de-DE,de;q=0.9,en-GB;q=0.8,en;q=0.7,fr;q=0.6', 'de'],
            'uppercase language code' => ['EN-US', 'en'],
            'mixed case language code' => ['De-aT,fr;q=0.5', 'de'],
            'uppercase quality key' => ['en;Q=0.1,it;q=0.5', 'it'],
            'q=0 excludes the only supported language' => ['en;q=0,zh', 'fr'],
            'q=0.000 marks a language as not acceptable' => ['es;q=0.000', 'fr'],
            'empty segments are ignored' => [', ,,nl', 'nl'],
            'wildcard is not a language' => ['*,ko;q=0.5', 'ko'],
            'parameter before quality' => ['en;level=1;q=0.2,it;q=0.5', 'it'],
            'whitespace around parameters' => ['en ; q=0.2 , pl ; q=0.5', 'pl'],
            'repeated code keeps its best quality' => ['en,fr;q=0.5,en;q=0.1', 'en'],
            'repeated code without quality' => ['fr;q=0.1,en;q=0.5,fr', 'fr'],
            'primary subtag must match exactly' => ['arn,es;q=0.5', 'es'],
        ];
    }

    /** Vérifie que la locale est négociée depuis Accept-Language et reprise dans Content-Language. */
    #[DataProvider('acceptLanguageHeaders')]
    public function testResolvesLocaleFromAcceptLanguage(string $header, string $expectedLocale): void
    {
        $request = Request::create('/test', 'GET');
        $request->headers->set('Accept-Language', $header);

        $response = (new SetLocale())->handle($request, fn (Request $req) => response('OK'));

        $this->assertSame($expectedLocale, app()->getLocale());
        $this->assertSame($expectedLocale, $response->headers->get('Content-Language'));
    }

    /** Vérifie que le segment d'URL a priorité sur l'en-tête Accept-Language. */
    public function testUrlSegmentTakesPriorityOverHeader(): void
    {
        $request = Request::create('/de/something', 'GET');
        $request->headers->set('Accept-Language', 'en');

        $response = (new SetLocale())->handle($request, fn (Request $req) => response('OK'));

        $this->assertSame('de', app()->getLocale());
        $this->assertSame('de', $response->headers->get('Content-Language'));
    }

    /**
     * Vérifie le repli sur l'en-tête quand le segment d'URL n'est pas une locale, sans poser Vary.
     *
     * Seules les redirections de RedirectController dépendent de la langue négociée et annoncent Vary.
     */
    public function testFallsBackToHeaderWhenUrlSegmentIsNotLocale(): void
    {
        $request = Request::create('/s/some-token', 'GET');
        $request->headers->set('Accept-Language', 'es');

        $response = (new SetLocale())->handle($request, fn (Request $req) => response('OK'));

        $this->assertSame('es', app()->getLocale());
        $this->assertSame([], $response->getVary());
    }

    /** Vérifie que la locale résolue devient le paramètre par défaut des routes localisées. */
    public function testResolvedLocaleBecomesTheDefaultRouteParameter(): void
    {
        $request = Request::create('/test', 'GET');
        $request->headers->set('Accept-Language', 'nl');

        (new SetLocale())->handle($request, fn (Request $req) => response('OK'));

        $this->assertSame(['locale' => 'nl'], URL::getDefaultParameters());
        $this->assertSame('http://localhost/nl/admin', route('admin.index'));
    }
}
