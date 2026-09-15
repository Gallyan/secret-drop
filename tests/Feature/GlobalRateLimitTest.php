<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class GlobalRateLimitTest extends TestCase
{
    /** Vérifie que le limiteur global des routes web autorise 120 requêtes par minute, décomptées par IP client. */
    public function testGlobalRateLimitIs120PerMinutePerClientIp(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.1'])->get('/fr');
        $secondFromSameIp = $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.1'])->get('/fr');
        $firstFromOtherIp = $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.2'])->get('/fr');

        $secondFromSameIp->assertHeader('X-RateLimit-Limit', '120');
        $secondFromSameIp->assertHeader('X-RateLimit-Remaining', '118');
        $firstFromOtherIp->assertHeader('X-RateLimit-Remaining', '119');
    }

    /** Vérifie que le limiteur global s'applique aussi au groupe API. */
    public function testGlobalRateLimitAppliesToApiGroup(): void
    {
        Route::middleware('api')->get('/api/test-rate-limited', fn () => 'ok');

        $response = $this->get('/api/test-rate-limited');

        $response->assertOk();
        $response->assertHeader('X-RateLimit-Limit', '120');
    }
}
