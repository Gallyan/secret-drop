<?php

namespace Tests\Feature;

use App\Providers\AppServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class TrustedProxiesTest extends TestCase
{
    /** Vérifie que la liste TRUSTED_PROXIES est découpée et nettoyée. */
    public function testConfigParsesCommaSeparatedList(): void
    {
        $this->withEnv('10.0.0.1, 192.168.0.0/24 , ', function (): void {
            $this->assertSame(['10.0.0.1', '192.168.0.0/24'], $this->freshAppConfig()['trusted_proxies']);
        });
    }

    /** Vérifie qu'aucun proxy n'est approuvé quand la variable est vide. */
    public function testConfigIsEmptyWhenBlank(): void
    {
        $this->withEnv('', function (): void {
            $this->assertSame([], $this->freshAppConfig()['trusted_proxies']);
        });
    }

    /** Vérifie qu'aucun proxy n'est approuvé quand la variable est absente. */
    public function testConfigIsEmptyWhenUnset(): void
    {
        $this->withEnv(null, function (): void {
            $this->assertSame([], $this->freshAppConfig()['trusted_proxies']);
        });
    }

    /** Vérifie qu'au démarrage un proxy configuré est approuvé : l'IP client vient de X-Forwarded-For pour lui seul. */
    public function testConfiguredProxyForwardsTheClientIp(): void
    {
        config(['app.trusted_proxies' => ['10.0.0.1']]);
        $provider = $this->app->getProvider(AppServiceProvider::class);
        $this->assertInstanceOf(AppServiceProvider::class, $provider);
        $provider->boot();
        Route::get('/test-client-ip', fn (Request $request) => $request->ip());

        $viaTrustedProxy = $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.1'])
            ->withHeader('X-Forwarded-For', '203.0.113.9')
            ->get('/test-client-ip');
        $viaUntrustedHost = $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.2'])
            ->withHeader('X-Forwarded-For', '203.0.113.9')
            ->get('/test-client-ip');

        $this->assertSame('203.0.113.9', $viaTrustedProxy->getContent());
        $this->assertSame('10.0.0.2', $viaUntrustedHost->getContent());
    }

    /** Vérifie que sans proxy configuré l'en-tête X-Forwarded-For est ignoré. */
    public function testForwardedHeaderIsIgnoredWithoutConfiguredProxy(): void
    {
        Route::get('/test-client-ip', fn (Request $request) => $request->ip());

        $response = $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.1'])
            ->withHeader('X-Forwarded-For', '203.0.113.9')
            ->get('/test-client-ip');

        $this->assertSame('10.0.0.1', $response->getContent());
    }

    /** @return array<string, mixed> */
    private function freshAppConfig(): array
    {
        return require base_path('config/app.php');
    }

    /**
     * Env::get() reads $_SERVER before $_ENV, and dotenv populates both, so a
     * fixture that only sets $_ENV is silently ignored wherever .env defines
     * the variable.
     *
     * @param  callable(): void  $assertions
     */
    private function withEnv(?string $value, callable $assertions): void
    {
        $previous = [
            'env' => array_key_exists('TRUSTED_PROXIES', $_ENV) ? $_ENV['TRUSTED_PROXIES'] : null,
            'server' => array_key_exists('TRUSTED_PROXIES', $_SERVER) ? $_SERVER['TRUSTED_PROXIES'] : null,
            'putenv' => getenv('TRUSTED_PROXIES'),
        ];

        $this->setEnv($value);

        try {
            $assertions();
        } finally {
            $this->setEnv($previous['env'] ?? $previous['server'] ?? ($previous['putenv'] ?: null));
        }
    }

    private function setEnv(mixed $value): void
    {
        if (! is_string($value)) {
            unset($_ENV['TRUSTED_PROXIES'], $_SERVER['TRUSTED_PROXIES']);
            putenv('TRUSTED_PROXIES');

            return;
        }

        $_ENV['TRUSTED_PROXIES'] = $value;
        $_SERVER['TRUSTED_PROXIES'] = $value;
        putenv("TRUSTED_PROXIES={$value}");
    }
}
