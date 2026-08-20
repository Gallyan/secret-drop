<?php

namespace Tests\Feature;

use Illuminate\Http\Middleware\TrustProxies;
use ReflectionProperty;
use Tests\TestCase;

class TrustedProxiesTest extends TestCase
{
    /** Vérifie que la liste TRUSTED_PROXIES est découpée et nettoyée. */
    public function testConfigParsesCommaSeparatedList(): void
    {
        $this->withEnv('10.0.0.1, 192.168.0.0/24 , ', function (array $config): void {
            $this->assertSame(['10.0.0.1', '192.168.0.0/24'], $config['trusted_proxies']);
        });
    }

    /** Vérifie qu'aucun proxy n'est approuvé quand la variable est absente. */
    public function testConfigIsEmptyWhenUnset(): void
    {
        $this->withEnv('', function (array $config): void {
            $this->assertSame([], $config['trusted_proxies']);
        });
    }

    /** Vérifie que la config est bien poussée dans le middleware au boot. */
    public function testConfigReachesTheMiddleware(): void
    {
        $proxies = new ReflectionProperty(TrustProxies::class, 'alwaysTrustProxies');

        $this->assertSame(config('app.trusted_proxies'), $proxies->getValue());
    }

    /**
     * @param  callable(array<string, mixed>): void  $assertions
     */
    private function withEnv(string $value, callable $assertions): void
    {
        putenv("TRUSTED_PROXIES={$value}");
        $_ENV['TRUSTED_PROXIES'] = $value;

        try {
            $assertions(require base_path('config/app.php'));
        } finally {
            putenv('TRUSTED_PROXIES');
            unset($_ENV['TRUSTED_PROXIES']);
        }
    }
}
