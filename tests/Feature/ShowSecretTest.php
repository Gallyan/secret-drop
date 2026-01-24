<?php

namespace Tests\Feature;

use App\Models\Secret;
use App\Services\TokenService;
use Illuminate\Support\Facades\Storage;
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
        $this->assertEquals(0, $secret->read_count);
        $this->assertNull($secret->first_read_at);

        $secret->delete();
    }

    public function testApiConfirmReadIncrementsReadCount(): void
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

        $response = $this->postJson("/api/secrets/{$secret->token}/read");

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

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

    public function testApiFetchDoesNotIncrementReadCount(): void
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
        $this->assertEquals(0, $secret->read_count);

        $this->getJson("/api/secrets/{$secret->token}");
        $secret->refresh();
        $this->assertEquals(0, $secret->read_count);

        $secret->delete();
    }

    public function testApiConfirmReadIncrementsReadCountMultipleTimes(): void
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

        $this->postJson("/api/secrets/{$secret->token}/read");
        $secret->refresh();
        $this->assertEquals(1, $secret->read_count);

        $this->postJson("/api/secrets/{$secret->token}/read");
        $secret->refresh();
        $this->assertEquals(2, $secret->read_count);

        $secret->delete();
    }

    public function testApiSingleUseSecretBecomesInaccessibleAfterConfirmRead(): void
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
        $response->assertJson(['will_be_destroyed' => true]);

        $this->postJson("/api/secrets/{$secret->token}/read");

        $response = $this->getJson("/api/secrets/{$secret->token}");
        $response->assertStatus(410);

        $secret->delete();
    }

    public function testApiConfirmReadReturns404ForNonExistentSecret(): void
    {
        $response = $this->postJson('/api/secrets/nonexistenttoken12345678901/read');

        $response->assertStatus(404);
        $response->assertJson(['error' => 'not_found']);
    }

    public function testApiConfirmReadReturns410ForExpiredSecret(): void
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

        $response = $this->postJson("/api/secrets/{$secret->token}/read");

        $response->assertStatus(410);
        $response->assertJson([
            'error' => 'unavailable',
            'reason' => 'expired',
        ]);

        $secret->delete();
    }

    public function testSingleUseSecretCiphertextIsDestroyedAfterRead(): void
    {
        $secret = Secret::create([
            'token' => $this->tokenService->generatePublicToken(),
            'admin_token' => $this->tokenService->generateAdminToken(),
            'type' => 'text',
            'cipher_meta' => ['alg' => 'AES-256-GCM', 'iv' => 'testiv', 'version' => 1],
            'ciphertext' => 'topsecretdata',
            'usage_unique' => true,
            'max_views' => 1,
            'expire_at' => now()->addDay(),
        ]);

        $response = $this->getJson("/api/secrets/{$secret->token}");
        $response->assertStatus(200);
        $response->assertJson(['ciphertext' => 'topsecretdata']);

        $this->postJson("/api/secrets/{$secret->token}/read");

        $secret->refresh();
        $this->assertNull($secret->ciphertext);

        $secret->delete();
    }

    public function testMaxViewsSecretCiphertextIsDestroyedAfterLastRead(): void
    {
        $secret = Secret::create([
            'token' => $this->tokenService->generatePublicToken(),
            'admin_token' => $this->tokenService->generateAdminToken(),
            'type' => 'text',
            'cipher_meta' => ['alg' => 'AES-256-GCM', 'iv' => 'testiv', 'version' => 1],
            'ciphertext' => 'limitedviewsdata',
            'usage_unique' => false,
            'max_views' => 2,
            'expire_at' => now()->addDay(),
        ]);

        $this->postJson("/api/secrets/{$secret->token}/read");
        $secret->refresh();
        $this->assertEquals('limitedviewsdata', $secret->ciphertext);

        $this->postJson("/api/secrets/{$secret->token}/read");
        $secret->refresh();
        $this->assertNull($secret->ciphertext);

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

    public function testSingleUseFileSecretIsDeletedAfterRead(): void
    {
        Storage::fake('secrets');

        $token = $this->tokenService->generatePublicToken();
        $filePath = $token;

        Storage::disk('secrets')->put($filePath, 'encrypted-file-content');

        $secret = Secret::create([
            'token' => $token,
            'admin_token' => $this->tokenService->generateAdminToken(),
            'type' => 'file',
            'cipher_meta' => ['alg' => 'AES-256-GCM', 'iv' => 'testiv', 'version' => 1],
            'file_path' => $filePath,
            'filename' => 'secret.pdf',
            'mime' => 'application/pdf',
            'size' => 1234,
            'usage_unique' => true,
            'max_views' => 1,
            'expire_at' => now()->addDay(),
        ]);

        Storage::disk('secrets')->assertExists($filePath);

        $this->postJson("/api/secrets/{$secret->token}/read");

        Storage::disk('secrets')->assertMissing($filePath);

        $secret->delete();
    }

    public function testRevokeSecretDeletesCiphertextAndMarksRevoked(): void
    {
        $secret = Secret::create([
            'token' => $this->tokenService->generatePublicToken(),
            'admin_token' => $this->tokenService->generateAdminToken(),
            'type' => 'text',
            'cipher_meta' => ['alg' => 'AES-256-GCM', 'iv' => 'testiv', 'version' => 1],
            'ciphertext' => 'secretdata',
            'usage_unique' => false,
            'expire_at' => now()->addDay(),
        ]);

        $response = $this->postJson("/api/secrets/{$secret->admin_token}/revoke");

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $secret->refresh();
        $this->assertNotNull($secret->revoked_at);
        $this->assertNull($secret->ciphertext);

        $secret->delete();
    }

    public function testRevokeSecretDeletesFile(): void
    {
        Storage::fake('secrets');

        $token = $this->tokenService->generatePublicToken();
        $filePath = $token;

        Storage::disk('secrets')->put($filePath, 'encrypted-file-content');

        $secret = Secret::create([
            'token' => $token,
            'admin_token' => $this->tokenService->generateAdminToken(),
            'type' => 'file',
            'cipher_meta' => ['alg' => 'AES-256-GCM', 'iv' => 'testiv', 'version' => 1],
            'file_path' => $filePath,
            'filename' => 'secret.pdf',
            'mime' => 'application/pdf',
            'size' => 1234,
            'usage_unique' => false,
            'expire_at' => now()->addDay(),
        ]);

        Storage::disk('secrets')->assertExists($filePath);

        $this->postJson("/api/secrets/{$secret->admin_token}/revoke");

        Storage::disk('secrets')->assertMissing($filePath);

        $secret->refresh();
        $this->assertNotNull($secret->revoked_at);

        $secret->delete();
    }

    public function testRevokeReturns404ForInvalidAdminToken(): void
    {
        $response = $this->postJson('/api/secrets/invalidtoken123/revoke');

        $response->assertStatus(404);
        $response->assertJson(['error' => 'not_found']);
    }

    public function testRevokeReturns409ForAlreadyRevokedSecret(): void
    {
        $secret = Secret::create([
            'token' => $this->tokenService->generatePublicToken(),
            'admin_token' => $this->tokenService->generateAdminToken(),
            'type' => 'text',
            'cipher_meta' => ['alg' => 'AES-256-GCM', 'iv' => 'testiv', 'version' => 1],
            'ciphertext' => 'secretdata',
            'usage_unique' => false,
            'expire_at' => now()->addDay(),
            'revoked_at' => now(),
        ]);

        $response = $this->postJson("/api/secrets/{$secret->admin_token}/revoke");

        $response->assertStatus(409);
        $response->assertJson(['error' => 'already_revoked']);

        $secret->delete();
    }

    public function testRevokedSecretIsInaccessible(): void
    {
        $secret = Secret::create([
            'token' => $this->tokenService->generatePublicToken(),
            'admin_token' => $this->tokenService->generateAdminToken(),
            'type' => 'text',
            'cipher_meta' => ['alg' => 'AES-256-GCM', 'iv' => 'testiv', 'version' => 1],
            'ciphertext' => 'secretdata',
            'usage_unique' => false,
            'expire_at' => now()->addDay(),
        ]);

        $this->postJson("/api/secrets/{$secret->admin_token}/revoke");

        $response = $this->getJson("/api/secrets/{$secret->token}");

        $response->assertStatus(410);
        $response->assertJson([
            'error' => 'unavailable',
            'reason' => 'revoked',
        ]);

        $secret->delete();
    }
}
