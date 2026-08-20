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

    /** Vérifie qu'aucun proxy n'est approuvé quand la variable est vide. */
    public function testConfigIsEmptyWhenBlank(): void
    {
        $this->withEnv('', function (array $config): void {
            $this->assertSame([], $config['trusted_proxies']);
        });
    }

    /** Vérifie qu'aucun proxy n'est approuvé quand la variable est absente. */
    public function testConfigIsEmptyWhenUnset(): void
    {
        $this->withEnv(null, function (array $config): void {
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
     * Env::get() reads $_SERVER before $_ENV, and dotenv populates both, so a
     * fixture that only sets $_ENV is silently ignored wherever .env defines
     * the variable.
     *
     * @param  callable(array<string, mixed>): void  $assertions
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
            $assertions(require base_path('config/app.php'));
        } finally {
            $this->setEnv($previous['env'] ?? $previous['server'] ?? ($previous['putenv'] ?: null));
        }
    }

    private function setEnv(?string $value): void
    {
        if ($value === null) {
            unset($_ENV['TRUSTED_PROXIES'], $_SERVER['TRUSTED_PROXIES']);
            putenv('TRUSTED_PROXIES');

            return;
        }

        $_ENV['TRUSTED_PROXIES'] = $value;
        $_SERVER['TRUSTED_PROXIES'] = $value;
        putenv("TRUSTED_PROXIES={$value}");
    }
}
