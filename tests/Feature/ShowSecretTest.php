<?php

namespace Tests\Feature;

use App\Models\Secret;
use App\Services\TokenService;
use Tests\TestCase;

class ShowSecretTest extends TestCase
{
    private TokenService $tokenService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tokenService = app(TokenService::class);
    }

    public function testCanViewSecret(): void
    {
        $secret = Secret::create([
            'token' => $this->tokenService->generatePublicToken(),
            'admin_token' => $this->tokenService->generateAdminToken(),
            'type' => 'text',
            'cipher_meta' => ['alg' => 'AES-256-GCM', 'iv' => 'testiv', 'version' => 1],
            'ciphertext' => 'encryptedcontent',
            'usage_unique' => false,
            'expire_at' => now()->addDay(),
        ]);

        $response = $this->get("/s/{$secret->token}");

        $response->assertStatus(200);
        $response->assertSee('encryptedcontent');

        $secret->refresh();
        $this->assertEquals(1, $secret->read_count);
        $this->assertNotNull($secret->first_read_at);

        $secret->delete();
    }

    public function testReturns404ForNonExistentSecret(): void
    {
        $response = $this->get('/s/nonexistenttoken12345678901');

        $response->assertStatus(404);
        $response->assertSee('Secret introuvable');
    }

    public function testReturns410ForExpiredSecret(): void
    {
        $secret = Secret::create([
            'token' => $this->tokenService->generatePublicToken(),
            'admin_token' => $this->tokenService->generateAdminToken(),
            'type' => 'text',
            'cipher_meta' => ['alg' => 'AES-256-GCM', 'iv' => 'testiv', 'version' => 1],
            'ciphertext' => 'expired',
            'usage_unique' => false,
            'expire_at' => now()->subHour(),
        ]);

        $response = $this->get("/s/{$secret->token}");

        $response->assertStatus(410);
        $response->assertSee('expiré');

        $secret->delete();
    }

    public function testReturns410ForRevokedSecret(): void
    {
        $secret = Secret::create([
            'token' => $this->tokenService->generatePublicToken(),
            'admin_token' => $this->tokenService->generateAdminToken(),
            'type' => 'text',
            'cipher_meta' => ['alg' => 'AES-256-GCM', 'iv' => 'testiv', 'version' => 1],
            'ciphertext' => 'revoked',
            'usage_unique' => false,
            'expire_at' => now()->addDay(),
            'revoked_at' => now(),
        ]);

        $response = $this->get("/s/{$secret->token}");

        $response->assertStatus(410);
        $response->assertSee('révoqué');

        $secret->delete();
    }

    public function testReturns410WhenMaxViewsReached(): void
    {
        $secret = Secret::create([
            'token' => $this->tokenService->generatePublicToken(),
            'admin_token' => $this->tokenService->generateAdminToken(),
            'type' => 'text',
            'cipher_meta' => ['alg' => 'AES-256-GCM', 'iv' => 'testiv', 'version' => 1],
            'ciphertext' => 'maxviews',
            'usage_unique' => false,
            'max_views' => 1,
            'read_count' => 1,
            'expire_at' => now()->addDay(),
        ]);

        $response = $this->get("/s/{$secret->token}");

        $response->assertStatus(410);
        $response->assertSee('maximum de lectures');

        $secret->delete();
    }

    public function testIncrementsReadCount(): void
    {
        $secret = Secret::create([
            'token' => $this->tokenService->generatePublicToken(),
            'admin_token' => $this->tokenService->generateAdminToken(),
            'type' => 'text',
            'cipher_meta' => ['alg' => 'AES-256-GCM', 'iv' => 'testiv', 'version' => 1],
            'ciphertext' => 'counting',
            'usage_unique' => false,
            'expire_at' => now()->addDay(),
        ]);

        $this->get("/s/{$secret->token}");
        $secret->refresh();
        $this->assertEquals(1, $secret->read_count);

        $this->get("/s/{$secret->token}");
        $secret->refresh();
        $this->assertEquals(2, $secret->read_count);

        $secret->delete();
    }

    public function testSingleUseSecretBecomesInaccessibleAfterFirstRead(): void
    {
        $secret = Secret::create([
            'token' => $this->tokenService->generatePublicToken(),
            'admin_token' => $this->tokenService->generateAdminToken(),
            'type' => 'text',
            'cipher_meta' => ['alg' => 'AES-256-GCM', 'iv' => 'testiv', 'version' => 1],
            'ciphertext' => 'singleuse',
            'usage_unique' => true,
            'max_views' => 1,
            'expire_at' => now()->addDay(),
        ]);

        $response = $this->get("/s/{$secret->token}");
        $response->assertStatus(200);

        $response = $this->get("/s/{$secret->token}");
        $response->assertStatus(410);

        $secret->delete();
    }
}
