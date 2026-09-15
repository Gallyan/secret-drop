<?php

namespace Tests\Unit;

use Tests\TestCase;

class SessionConfigTest extends TestCase
{
    private const DRIVER_VARIABLE = 'SESSION_DRIVER';

    /** Vérifie que le driver de session retombe sur file, sans table en base, quand SESSION_DRIVER n'est pas défini. */
    public function testSessionDriverDefaultsToFileWithoutEnvironmentVariable(): void
    {
        $config = $this->loadSessionConfigWithoutDriverVariable();

        $this->assertSame('file', $config['driver'] ?? null);
    }

    /**
     * @return array<array-key, mixed>
     */
    private function loadSessionConfigWithoutDriverVariable(): array
    {
        $serverValue = $_SERVER[self::DRIVER_VARIABLE] ?? null;
        $envValue = $_ENV[self::DRIVER_VARIABLE] ?? null;
        $putenvValue = getenv(self::DRIVER_VARIABLE);

        unset($_SERVER[self::DRIVER_VARIABLE], $_ENV[self::DRIVER_VARIABLE]);
        putenv(self::DRIVER_VARIABLE);

        try {
            $config = require config_path('session.php');

            return is_array($config) ? $config : [];
        } finally {
            if ($serverValue !== null) {
                $_SERVER[self::DRIVER_VARIABLE] = $serverValue;
            }

            if ($envValue !== null) {
                $_ENV[self::DRIVER_VARIABLE] = $envValue;
            }

            if ($putenvValue !== false) {
                putenv(self::DRIVER_VARIABLE."={$putenvValue}");
            }
        }
    }
}
