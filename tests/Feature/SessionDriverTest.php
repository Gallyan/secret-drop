<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * The suite runs on the array driver, so the driver that actually persists
 * sessions in production is only exercised here.
 */
class SessionDriverTest extends TestCase
{
    /** Vérifie que le driver utilisé en production écrit bien la session. */
    public function testSessionIsPersistedWithTheFileDriver(): void
    {
        $path = storage_path('framework/testing/sessions');
        File::ensureDirectoryExists($path);
        File::cleanDirectory($path);

        config(['session.driver' => 'file', 'session.files' => $path]);

        $response = $this->get('/en');

        $response->assertOk();
        $this->assertCount(1, File::files($path));

        File::deleteDirectory($path);
    }
}
