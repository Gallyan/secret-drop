<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DatabaseSessionTest extends TestCase
{
    /**
     * Production stores sessions in the database while the suite defaults to the
     * array driver, so the sessions table schema is only exercised here.
     */
    public function testSessionIsPersistedWithTheDatabaseDriver(): void
    {
        config(['session.driver' => 'database']);

        $response = $this->get('/en');

        $response->assertOk();
        $this->assertSame(1, DB::table('sessions')->count());
    }
}
