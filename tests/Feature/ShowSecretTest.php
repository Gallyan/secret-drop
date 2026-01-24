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

    public function testShowPageReturns200WithToken(): void
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
        $response->assertSee($secret->token);

        $secret->delete();
    }

    public function testShowPageReturns200EvenForNonExistentToken(): void
    {
        $response = $this->get('/s/nonexistenttoken12345678901');

        $response->assertStatus(200);
    }

    public function testApiFetchReturnsSecretData(): void
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

        $response = $this->getJson("/api/secrets/{$secret->token}");

        $response->assertStatus(200);
        $response->assertJson([
            'type' => 'text',
            'ciphertext' => 'encryptedcontent',
            'cipher_meta' => ['alg' => 'AES-256-GCM', 'iv' => 'testiv', 'version' => 1],
            'will_be_destroyed' => false,
        ]);

        $secret->refresh();
        $this->assertEquals(1, $secret->read_count);
        $this->assertNotNull($secret->first_read_at);

        $secret->delete();
    }

    public function testApiFetchReturns404ForNonExistentSecret(): void
    {
        $response = $this->getJson('/api/secrets/nonexistenttoken12345678901');

        $response->assertStatus(404);
        $response->assertJson(['error' => 'not_found']);
    }

    public function testApiFetchReturns410ForExpiredSecret(): void
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

        $response = $this->getJson("/api/secrets/{$secret->token}");

        $response->assertStatus(410);
        $response->assertJson([
            'error' => 'unavailable',
            'reason' => 'expired',
        ]);

        $secret->delete();
    }

    public function testApiFetchReturns410ForRevokedSecret(): void
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

        $response = $this->getJson("/api/secrets/{$secret->token}");

        $response->assertStatus(410);
        $response->assertJson([
            'error' => 'unavailable',
            'reason' => 'revoked',
        ]);

        $secret->delete();
    }

    public function testApiFetchReturns410WhenMaxViewsReached(): void
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

        $response = $this->getJson("/api/secrets/{$secret->token}");

        $response->assertStatus(410);
        $response->assertJson([
            'error' => 'unavailable',
            'reason' => 'max_views',
        ]);

        $secret->delete();
    }

    public function testApiFetchIncrementsReadCount(): void
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

        $this->getJson("/api/secrets/{$secret->token}");
        $secret->refresh();
        $this->assertEquals(1, $secret->read_count);

        $this->getJson("/api/secrets/{$secret->token}");
        $secret->refresh();
        $this->assertEquals(2, $secret->read_count);

        $secret->delete();
    }

    public function testApiSingleUseSecretBecomesInaccessibleAfterFirstRead(): void
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

        $response = $this->getJson("/api/secrets/{$secret->token}");
        $response->assertStatus(200);

        $response = $this->getJson("/api/secrets/{$secret->token}");
        $response->assertStatus(410);

        $secret->delete();
    }

    public function testApiFetchReturnsFileMetadata(): void
    {
        $secret = Secret::create([
            'token' => $this->tokenService->generatePublicToken(),
            'admin_token' => $this->tokenService->generateAdminToken(),
            'type' => 'file',
            'cipher_meta' => ['alg' => 'AES-256-GCM', 'iv' => 'testiv', 'version' => 1],
            'file_path' => 'secrets/test',
            'filename' => 'document.pdf',
            'mime' => 'application/pdf',
            'size' => 12345,
            'usage_unique' => false,
            'expire_at' => now()->addDay(),
        ]);

        $response = $this->getJson("/api/secrets/{$secret->token}");

        $response->assertStatus(200);
        $response->assertJson([
            'type' => 'file',
            'filename' => 'document.pdf',
            'mime' => 'application/pdf',
            'size' => 12345,
            'cipher_meta' => ['alg' => 'AES-256-GCM', 'iv' => 'testiv', 'version' => 1],
        ]);
        $response->assertJsonMissing(['ciphertext']);

        $secret->delete();
    }
}
