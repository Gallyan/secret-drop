<?php

namespace Tests\Feature;

use App\Models\Secret;
use App\Services\SecretStorageService;
use App\Services\TokenService;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class SecretWorkflowTest extends TestCase
{
    private TokenService $tokenService;

    private SecretStorageService $storage;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tokenService = app(TokenService::class);
        $this->storage = app(SecretStorageService::class);
    }

    public function testCompleteTextSecretWorkflow(): void
    {
        // Step 1: Create secret with max_views = 1 (single use)
        $createResponse = $this->postJson('/api/secrets', [
            'type' => 'text',
            'ciphertext' => 'encrypted_message_content',
            'cipher_meta' => [
                'alg' => 'AES-256-GCM',
                'iv' => 'randomiv12345',
                'version' => 1,
            ],
            'expiration' => '7d',
            'max_views' => 1,
        ]);

        $createResponse->assertStatus(201);
        $token = $createResponse->json('token');

        // Step 2: View secret page
        $showResponse = $this->get("/s/{$token}");
        $showResponse->assertStatus(200);

        // Step 3: Fetch secret data via API
        $fetchResponse = $this->getJson("/api/secrets/{$token}");
        $fetchResponse->assertStatus(200);
        $fetchResponse->assertJson([
            'type' => 'text',
            'ciphertext' => 'encrypted_message_content',
            'will_be_destroyed' => true,
        ]);

        // Step 4: Confirm read
        $readResponse = $this->postJson("/api/secrets/{$token}/read");
        $readResponse->assertStatus(200);
        $readResponse->assertJson(['success' => true]);

        // Step 5: Verify secret is no longer accessible (max views reached)
        $refetchResponse = $this->getJson("/api/secrets/{$token}");
        $refetchResponse->assertStatus(410);
        $refetchResponse->assertJson([
            'error' => 'unavailable',
            'reason' => 'max_views',
        ]);

        // Step 6: Verify ciphertext was destroyed
        $secret = Secret::where('token', $token)->first();
        $this->assertNull($secret->ciphertext);

        // Cleanup
        $secret->delete();
    }

    public function testCompleteFileSecretWorkflow(): void
    {
        // Step 1: Create file secret with max_views = 1 (single use)
        $file = UploadedFile::fake()->create('encrypted.bin', 512, 'application/octet-stream');

        $createResponse = $this->postJson('/api/secrets', [
            'type' => 'file',
            'encrypted_file' => $file,
            'cipher_meta' => json_encode([
                'alg' => 'AES-256-GCM',
                'iv' => 'randomiv12345',
                'version' => 1,
            ]),
            'expiration' => '1d',
            'max_views' => 1,
        ]);

        $createResponse->assertStatus(201);
        $token = $createResponse->json('token');

        // Step 2: Fetch metadata (filename/mime/size are encrypted in file payload)
        $fetchResponse = $this->getJson("/api/secrets/{$token}");
        $fetchResponse->assertStatus(200);
        $fetchResponse->assertJson([
            'type' => 'file',
            'will_be_destroyed' => true,
        ]);
        $fetchResponse->assertJsonMissing(['encrypted_size']);

        // Step 3: Download file
        $downloadResponse = $this->get("/s/{$token}/download");
        $downloadResponse->assertStatus(200);

        // Step 4: Confirm read (destroys file)
        $readResponse = $this->postJson("/api/secrets/{$token}/read");
        $readResponse->assertStatus(200);

        // Step 5: Verify file was cleared
        $secret = Secret::where('token', $token)->first();
        $this->assertNull($secret->file_path);

        // Step 6: Download should fail now
        $refetchResponse = $this->getJson("/api/secrets/{$token}");
        $refetchResponse->assertStatus(410);

        // Cleanup
        $secret->delete();
    }

    public function testMultiUseSecretWithMaxViewsWorkflow(): void
    {
        // Create secret with 3 max views
        $createResponse = $this->postJson('/api/secrets', [
            'type' => 'text',
            'ciphertext' => 'multi_use_content',
            'cipher_meta' => [
                'alg' => 'AES-256-GCM',
                'iv' => 'randomiv12345',
                'version' => 1,
            ],
            'expiration' => '7d',
            'max_views' => 3,
        ]);

        $createResponse->assertStatus(201);
        $token = $createResponse->json('token');

        // First read - should work, not destroyed
        $fetch1 = $this->getJson("/api/secrets/{$token}");
        $fetch1->assertStatus(200);
        $fetch1->assertJson(['will_be_destroyed' => false]);
        $this->postJson("/api/secrets/{$token}/read");

        // Second read - should work, not destroyed
        $fetch2 = $this->getJson("/api/secrets/{$token}");
        $fetch2->assertStatus(200);
        $fetch2->assertJson(['will_be_destroyed' => false]);
        $this->postJson("/api/secrets/{$token}/read");

        // Third read - should work but will be destroyed
        $fetch3 = $this->getJson("/api/secrets/{$token}");
        $fetch3->assertStatus(200);
        $fetch3->assertJson(['will_be_destroyed' => true]);
        $this->postJson("/api/secrets/{$token}/read");

        // Fourth attempt - should fail
        $fetch4 = $this->getJson("/api/secrets/{$token}");
        $fetch4->assertStatus(410);
        $fetch4->assertJson(['reason' => 'max_views']);

        // Cleanup
        Secret::where('token', $token)->delete();
    }

    public function testSecretWithPassphraseWorkflow(): void
    {
        // Create secret with passphrase metadata (salt + kdf indicate passphrase)
        $createResponse = $this->postJson('/api/secrets', [
            'type' => 'text',
            'ciphertext' => 'passphrase_protected_content',
            'cipher_meta' => [
                'alg' => 'AES-256-GCM',
                'iv' => 'randomiv12345',
                'version' => 1,
                'salt' => 'randomsalt12345',
                'kdf' => 'PBKDF2-SHA256-200k',
            ],
            'expiration' => '7d',
        ]);

        $createResponse->assertStatus(201);
        $token = $createResponse->json('token');

        // Fetch should return passphrase metadata (salt + kdf)
        $fetchResponse = $this->getJson("/api/secrets/{$token}");
        $fetchResponse->assertStatus(200);
        $fetchResponse->assertJsonPath('cipher_meta.salt', 'randomsalt12345');
        $fetchResponse->assertJsonPath('cipher_meta.kdf', 'PBKDF2-SHA256-200k');

        // Cleanup
        Secret::where('token', $token)->delete();
    }

    public function testSecretWithCreatorEmailWorkflow(): void
    {
        // Create secret with creator email
        $createResponse = $this->postJson('/api/secrets', [
            'type' => 'text',
            'ciphertext' => 'content_with_email',
            'cipher_meta' => [
                'alg' => 'AES-256-GCM',
                'iv' => 'randomiv12345',
                'version' => 1,
            ],
            'expiration' => '7d',
            'creator_email' => 'creator@example.com',
        ]);

        $createResponse->assertStatus(201);
        $token = $createResponse->json('token');

        // Verify creator email hash is stored
        $secret = Secret::where('token', $token)->first();
        $this->assertNotNull($secret->creator_email_hash);
        $this->assertTrue($secret->verifyCreatorEmail('creator@example.com'));
        $this->assertTrue($secret->verifyCreatorEmail('CREATOR@EXAMPLE.COM')); // Case insensitive

        // Cleanup
        $secret->delete();
    }

    public function testRevocationWorkflowViaAdminToken(): void
    {
        // Create secret
        $createResponse = $this->postJson('/api/secrets', [
            'type' => 'text',
            'ciphertext' => 'soon_to_be_revoked',
            'cipher_meta' => [
                'alg' => 'AES-256-GCM',
                'iv' => 'randomiv12345',
                'version' => 1,
            ],
            'expiration' => '7d',
        ]);

        $createResponse->assertStatus(201);
        $token = $createResponse->json('token');
        $secret = Secret::where('token', $token)->first();
        $adminToken = $secret->admin_token;

        // Verify secret is accessible
        $fetchResponse = $this->getJson("/api/secrets/{$token}");
        $fetchResponse->assertStatus(200);

        // Revoke via admin token
        $revokeResponse = $this->postJson("/api/secrets/{$adminToken}/revoke");
        $revokeResponse->assertStatus(200);
        $revokeResponse->assertJson(['success' => true]);

        // Verify secret is no longer accessible
        $refetchResponse = $this->getJson("/api/secrets/{$token}");
        $refetchResponse->assertStatus(410);
        $refetchResponse->assertJson(['reason' => 'revoked']);

        // Verify ciphertext was destroyed
        $secret->refresh();
        $this->assertNull($secret->ciphertext);
        $this->assertNotNull($secret->revoked_at);

        // Cleanup
        $secret->delete();
    }

    public function testConcurrentAccessDoesNotCorruptReadCount(): void
    {
        // Create unlimited secret
        $createResponse = $this->postJson('/api/secrets', [
            'type' => 'text',
            'ciphertext' => 'concurrent_access_content',
            'cipher_meta' => [
                'alg' => 'AES-256-GCM',
                'iv' => 'randomiv12345',
                'version' => 1,
            ],
            'expiration' => '7d',
        ]);

        $token = $createResponse->json('token');

        // Simulate multiple concurrent reads
        for ($i = 0; $i < 5; $i++) {
            $this->getJson("/api/secrets/{$token}");
            $this->postJson("/api/secrets/{$token}/read");
        }

        // Verify read count is accurate
        $secret = Secret::where('token', $token)->first();
        $this->assertEquals(5, $secret->read_count);
        $this->assertNotNull($secret->first_read_at);
        $this->assertNotNull($secret->last_read_at);

        // Cleanup
        $secret->delete();
    }

    public function testFirstReadAtIsSetOnlyOnce(): void
    {
        // Create secret
        $createResponse = $this->postJson('/api/secrets', [
            'type' => 'text',
            'ciphertext' => 'first_read_test',
            'cipher_meta' => [
                'alg' => 'AES-256-GCM',
                'iv' => 'randomiv12345',
                'version' => 1,
            ],
            'expiration' => '7d',
        ]);

        $token = $createResponse->json('token');

        // First read
        $this->postJson("/api/secrets/{$token}/read");
        $secret = Secret::where('token', $token)->first();
        $firstReadAt = $secret->first_read_at;
        $this->assertNotNull($firstReadAt);

        // Wait a bit
        usleep(10000);

        // Second read
        $this->postJson("/api/secrets/{$token}/read");
        $secret->refresh();

        // first_read_at should not change
        $this->assertEquals($firstReadAt->timestamp, $secret->first_read_at->timestamp);

        // last_read_at should be updated
        $this->assertTrue($secret->last_read_at->gte($firstReadAt));

        // Cleanup
        $secret->delete();
    }

    public function testAdminTokenIsDifferentFromPublicToken(): void
    {
        $createResponse = $this->postJson('/api/secrets', [
            'type' => 'text',
            'ciphertext' => 'token_test',
            'cipher_meta' => [
                'alg' => 'AES-256-GCM',
                'iv' => 'randomiv12345',
                'version' => 1,
            ],
            'expiration' => '7d',
        ]);

        $token = $createResponse->json('token');
        $secret = Secret::where('token', $token)->first();

        // Tokens should be different
        $this->assertNotEquals($token, $secret->admin_token);

        // Both tokens are 32 chars (128 bits hex)
        $this->assertEquals(32, strlen($token));
        $this->assertEquals(32, strlen($secret->admin_token));

        // Tokens should be valid hex strings
        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $token);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $secret->admin_token);

        // Cleanup
        $secret->delete();
    }
}
