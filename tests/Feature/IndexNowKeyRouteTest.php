<?php

namespace Tests\Feature;

use App\Http\Controllers\SeoController;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class IndexNowKeyRouteTest extends TestCase
{
    private const KEY = 'test-indexnow-key-0123456789abcdef';

    /**
     * @return array<string, array{string}>
     */
    public static function boundaryLengthKeys(): array
    {
        return [
            '8 characters' => ['abcd1234'],
            '128 characters' => [str_repeat('a', 128)],
        ];
    }

    /** Vérifie que le fichier de vérification retourne la clé en texte brut, aux deux bornes de longueur. */
    #[DataProvider('boundaryLengthKeys')]
    public function testKeyFileReturnsTheConfiguredKey(string $key): void
    {
        config(['services.indexnow.key' => $key]);

        $response = $this->get("/{$key}.txt");

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
        $this->assertSame($key, $response->getContent());
    }

    /**
     * @return array<string, array{string}>
     */
    public static function keysOutsideTheRouteFormat(): array
    {
        return [
            '7 characters' => ['abcd123'],
            '129 characters' => [str_repeat('a', 129)],
            'underscore' => ['abcd_efgh'],
            'dot' => ['abcd.efgh'],
            'tilde' => ['abcd~efgh'],
            'non ascii letter' => ['clé-indexnow'],
        ];
    }

    /** Vérifie qu'une clé hors format répond 404 parce que la route ne la capture pas, même si elle est configurée. */
    #[DataProvider('keysOutsideTheRouteFormat')]
    public function testKeyOutsideTheFormatIsNotFoundBecauseTheRouteDoesNotMatch(string $key): void
    {
        config(['services.indexnow.key' => $key]);

        $response = $this->get('/'.rawurlencode($key).'.txt');

        $response->assertNotFound();
        $this->assertFalse($this->keyRoute()->matches(Request::create('/'.rawurlencode($key).'.txt')));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function keysRejectedByTheController(): array
    {
        return [
            'different key' => ['wrong-indexnow-key-000000000'],
            'same key in uppercase' => [strtoupper(self::KEY)],
        ];
    }

    /** Vérifie qu'une clé au bon format mais différente de la clé configurée atteint la route et répond 404. */
    #[DataProvider('keysRejectedByTheController')]
    public function testWellFormedKeyDifferentFromTheConfiguredOneIsNotFound(string $requestedKey): void
    {
        config(['services.indexnow.key' => self::KEY]);

        $response = $this->get("/{$requestedKey}.txt");

        $response->assertNotFound();
        $this->assertTrue($this->keyRoute()->matches(Request::create("/{$requestedKey}.txt")));
    }

    /**
     * @return array<string, array{?string}>
     */
    public static function unconfiguredKeys(): array
    {
        return [
            'null key' => [null],
            'empty key' => [''],
        ];
    }

    /** Vérifie qu'en l'absence de clé configurée la route répond 404 sans erreur PHP. */
    #[DataProvider('unconfiguredKeys')]
    public function testMissingConfiguredKeyReturnsNotFound(?string $configuredKey): void
    {
        config(['services.indexnow.key' => $configuredKey]);

        $response = $this->get('/'.self::KEY.'.txt');

        $response->assertNotFound();
    }

    /**
     * @return array<string, array{string}>
     */
    public static function staticTxtFilesTooShortForTheKeyFormat(): array
    {
        return [
            'robots.txt' => ['/robots.txt'],
            'llms.txt' => ['/llms.txt'],
        ];
    }

    /** Vérifie que robots.txt et llms.txt sont protégés par le format de clé lui-même : la route de clé ne peut pas les capturer. */
    #[DataProvider('staticTxtFilesTooShortForTheKeyFormat')]
    public function testShortStaticTxtFilesCannotMatchTheKeyRoute(string $uri): void
    {
        $this->assertFalse($this->keyRoute()->matches(Request::create($uri)));
    }

    /** Vérifie que llms-full.txt, qui respecte le format de clé, reste servi par sa propre route déclarée avant la route de clé. */
    public function testLlmsFullTxtMatchesTheKeyFormatButIsServedByItsOwnRoute(): void
    {
        config(['services.indexnow.key' => 'llms-full']);

        $response = $this->get('/llms-full.txt');

        $this->assertTrue($this->keyRoute()->matches(Request::create('/llms-full.txt')));
        $response->assertOk();
        $this->assertStringStartsWith('# Secret Drop -- Full Documentation', $response->getContent());
    }

    private function keyRoute(): Route
    {
        $route = app('router')->getRoutes()->getByAction(SeoController::class.'@indexNowKey');

        $this->assertInstanceOf(Route::class, $route);

        return $route;
    }
}
