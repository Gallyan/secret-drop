<?php

namespace Tests;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\ParallelTesting;

abstract class TestCase extends BaseTestCase
{
    use LazilyRefreshDatabase;

    /**
     * Storage::fake() utilise un dossier fixe par disque : deux lancements de tests simultanés
     * (deux terminaux, un autre agent) s'y marcheraient dessus. Le jeton le rend propre à chaque processus.
     */
    protected function setUp(): void
    {
        parent::setUp();

        ParallelTesting::resolveTokenUsing(fn () => $_SERVER['TEST_TOKEN'] ?? 'pid'.getmypid());

        static $cleanupRegistered = false;

        if ($cleanupRegistered) {
            return;
        }

        $cleanupRegistered = true;
        register_shutdown_function(function (): void {
            $pattern = storage_path('framework/testing/disks/*_test_pid'.getmypid());

            foreach (glob($pattern) ?: [] as $directory) {
                (new Filesystem())->deleteDirectory($directory);
            }
        });
    }

    /**
     * Session of a super admin authenticated through the magic link, valid for 15 minutes.
     *
     * @return array{super_admin_verified: true, super_admin_expires_at: int}
     */
    protected function superAdminSession(): array
    {
        return [
            'super_admin_verified' => true,
            'super_admin_expires_at' => now()->addMinutes(15)->timestamp,
        ];
    }
}
