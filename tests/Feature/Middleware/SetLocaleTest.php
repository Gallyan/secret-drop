<?php

namespace Tests\Feature\Middleware;

use App\Http\Middleware\SetLocale;
use Illuminate\Http\Request;
use Tests\TestCase;

class SetLocaleTest extends TestCase
{
    private SetLocale $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        app()->setLocale(config('app.locale'));
        $this->middleware = new SetLocale();
    }

    public function testDefaultsToAppLocaleWithoutHeader(): void
    {
        $request = Request::create('/test', 'GET');
        $request->headers->remove('Accept-Language');
        $expectedLocale = config('app.locale');

        $response = $this->middleware->handle($request, fn ($req) => response('OK'));

        $this->assertEquals($expectedLocale, app()->getLocale());
        $this->assertEquals($expectedLocale, $response->headers->get('Content-Language'));
    }

    public function testDefaultsToAppLocaleWithEmptyHeader(): void
    {
        $request = Request::create('/test', 'GET');
        $request->headers->set('Accept-Language', '');
        $expectedLocale = config('app.locale');

        $response = $this->middleware->handle($request, fn ($req) => response('OK'));

        $this->assertEquals($expectedLocale, app()->getLocale());
        $this->assertEquals($expectedLocale, $response->headers->get('Content-Language'));
    }

    public function testDetectsEnglishFromHeader(): void
    {
        $request = Request::create('/test', 'GET');
        $request->headers->set('Accept-Language', 'en');

        $response = $this->middleware->handle($request, fn ($req) => response('OK'));

        $this->assertEquals('en', app()->getLocale());
        $this->assertEquals('en', $response->headers->get('Content-Language'));
    }

    public function testDetectsFrenchFromHeader(): void
    {
        $request = Request::create('/test', 'GET');
        $request->headers->set('Accept-Language', 'fr');

        $response = $this->middleware->handle($request, fn ($req) => response('OK'));

        $this->assertEquals('fr', app()->getLocale());
        $this->assertEquals('fr', $response->headers->get('Content-Language'));
    }

    public function testHandlesRegionalVariants(): void
    {
        $request = Request::create('/test', 'GET');
        $request->headers->set('Accept-Language', 'en-US,en;q=0.9');

        $response = $this->middleware->handle($request, fn ($req) => response('OK'));

        $this->assertEquals('en', app()->getLocale());
        $this->assertEquals('en', $response->headers->get('Content-Language'));
    }

    public function testHandlesFrenchRegionalVariants(): void
    {
        $request = Request::create('/test', 'GET');
        $request->headers->set('Accept-Language', 'fr-CA,fr;q=0.9');

        $response = $this->middleware->handle($request, fn ($req) => response('OK'));

        $this->assertEquals('fr', app()->getLocale());
        $this->assertEquals('fr', $response->headers->get('Content-Language'));
    }

    public function testRespectsQualityValues(): void
    {
        $request = Request::create('/test', 'GET');
        $request->headers->set('Accept-Language', 'de;q=0.9,en;q=0.8,fr;q=0.7');

        $response = $this->middleware->handle($request, fn ($req) => response('OK'));

        $this->assertEquals('en', app()->getLocale());
        $this->assertEquals('en', $response->headers->get('Content-Language'));
    }

    public function testFallsBackToAppLocaleForUnsupportedLanguage(): void
    {
        $request = Request::create('/test', 'GET');
        $request->headers->set('Accept-Language', 'de,es,it');
        $expectedLocale = config('app.locale');

        $response = $this->middleware->handle($request, fn ($req) => response('OK'));

        $this->assertEquals($expectedLocale, app()->getLocale());
        $this->assertEquals($expectedLocale, $response->headers->get('Content-Language'));
    }

    public function testPrefersFrenchOverEnglishWhenHigherQuality(): void
    {
        $request = Request::create('/test', 'GET');
        $request->headers->set('Accept-Language', 'en;q=0.7,fr;q=0.9');

        $response = $this->middleware->handle($request, fn ($req) => response('OK'));

        $this->assertEquals('fr', app()->getLocale());
        $this->assertEquals('fr', $response->headers->get('Content-Language'));
    }

    public function testPrefersEnglishWhenFirstWithoutQuality(): void
    {
        $request = Request::create('/test', 'GET');
        $request->headers->set('Accept-Language', 'en,fr');

        $response = $this->middleware->handle($request, fn ($req) => response('OK'));

        $this->assertEquals('en', app()->getLocale());
        $this->assertEquals('en', $response->headers->get('Content-Language'));
    }

    public function testHandlesComplexAcceptLanguageHeader(): void
    {
        $request = Request::create('/test', 'GET');
        $request->headers->set('Accept-Language', 'de-DE,de;q=0.9,en-GB;q=0.8,en;q=0.7,fr;q=0.6');

        $response = $this->middleware->handle($request, fn ($req) => response('OK'));

        $this->assertEquals('en', app()->getLocale());
        $this->assertEquals('en', $response->headers->get('Content-Language'));
    }

    public function testSetsContentLanguageHeader(): void
    {
        $request = Request::create('/test', 'GET');
        $request->headers->set('Accept-Language', 'en');

        $response = $this->middleware->handle($request, fn ($req) => response('OK'));

        $this->assertTrue($response->headers->has('Content-Language'));
        $this->assertEquals('en', $response->headers->get('Content-Language'));
    }
}
