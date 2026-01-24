<?php

namespace Tests\Feature;

use App\Models\Secret;
use App\Services\SecretStorageService;
use App\Services\TokenService;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class CleanExpiredSecretsCommandTest extends TestCase
{
    private TokenService $tokenService;

    private SecretStorageService $storage;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tokenService = app(TokenService::class);
        $this->storage = app(SecretStorageService::class);
    }

    public function testDeletesExpiredSecrets(): void
    {
        $expiredSecret = Secret::create([
            'token' => $this->tokenService->generatePublicToken(),
            'admin_token' => $this->tokenService->generateAdminToken(),
            'type' => 'text',
            'cipher_meta' => ['alg' => 'AES-256-GCM', 'iv' => 'testiv', 'version' => 1],
            'ciphertext' => 'expired',
            'usage_unique' => false,
            'expire_at' => now()->subHour(),
        ]);

        $validSecret = Secret::create([
            'token' => $this->tokenService->generatePublicToken(),
            'admin_token' => $this->tokenService->generateAdminToken(),
            'type' => 'text',
            'cipher_meta' => ['alg' => 'AES-256-GCM', 'iv' => 'testiv', 'version' => 1],
            'ciphertext' => 'valid',
            'usage_unique' => false,
            'expire_at' => now()->addDay(),
        ]);

        $this->artisan('secrets:clean')
            ->assertSuccessful();

        $this->assertNull(Secret::find($expiredSecret->id));
        $this->assertNotNull(Secret::find($validSecret->id));

        $validSecret->delete();
    }

    public function testDeletesRevokedSecrets(): void
    {
        $revokedSecret = Secret::create([
            'token' => $this->tokenService->generatePublicToken(),
            'admin_token' => $this->tokenService->generateAdminToken(),
            'type' => 'text',
            'cipher_meta' => ['alg' => 'AES-256-GCM', 'iv' => 'testiv', 'version' => 1],
            'ciphertext' => 'revoked',
            'usage_unique' => false,
            'expire_at' => now()->addDay(),
            'revoked_at' => now(),
        ]);

        $this->artisan('secrets:clean')
            ->assertSuccessful();

        $this->assertNull(Secret::find($revokedSecret->id));
    }

    public function testDeletesMaxViewsReachedSecrets(): void
    {
        $maxViewsSecret = Secret::create([
            'token' => $this->tokenService->generatePublicToken(),
            'admin_token' => $this->tokenService->generateAdminToken(),
            'type' => 'text',
            'cipher_meta' => ['alg' => 'AES-256-GCM', 'iv' => 'testiv', 'version' => 1],
            'ciphertext' => 'maxviews',
            'usage_unique' => false,
            'max_views' => 3,
            'read_count' => 3,
            'expire_at' => now()->addDay(),
        ]);

        $this->artisan('secrets:clean')
            ->assertSuccessful();

        $this->assertNull(Secret::find($maxViewsSecret->id));
    }

    public function testDeletesSingleUseSecretsAlreadyRead(): void
    {
        $singleUseSecret = Secret::create([
            'token' => $this->tokenService->generatePublicToken(),
            'admin_token' => $this->tokenService->generateAdminToken(),
            'type' => 'text',
            'cipher_meta' => ['alg' => 'AES-256-GCM', 'iv' => 'testiv', 'version' => 1],
            'ciphertext' => 'singleuse',
            'usage_unique' => true,
            'read_count' => 1,
            'expire_at' => now()->addDay(),
        ]);

        $this->artisan('secrets:clean')
            ->assertSuccessful();

        $this->assertNull(Secret::find($singleUseSecret->id));
    }

    public function testDeletesFileFromStorage(): void
    {
        $token = $this->tokenService->generatePublicToken();
        $file = UploadedFile::fake()->create('encrypted', 256);
        $this->storage->store($token, $file);

        $fileSecret = Secret::create([
            'token' => $token,
            'admin_token' => $this->tokenService->generateAdminToken(),
            'type' => 'file',
            'cipher_meta' => ['alg' => 'AES-256-GCM', 'iv' => 'testiv', 'version' => 1],
            'file_path' => $token,
            'filename' => 'expired.pdf',
            'mime' => 'application/pdf',
            'size' => 256,
            'usage_unique' => false,
            'expire_at' => now()->subHour(),
        ]);

        $this->assertTrue($this->storage->exists($token));

        $this->artisan('secrets:clean')
            ->assertSuccessful();

        $this->assertNull(Secret::find($fileSecret->id));
        $this->assertFalse($this->storage->exists($token));
    }

    public function testDryRunDoesNotDelete(): void
    {
        $expiredSecret = Secret::create([
            'token' => $this->tokenService->generatePublicToken(),
            'admin_token' => $this->tokenService->generateAdminToken(),
            'type' => 'text',
            'cipher_meta' => ['alg' => 'AES-256-GCM', 'iv' => 'testiv', 'version' => 1],
            'ciphertext' => 'dryrun',
            'usage_unique' => false,
            'expire_at' => now()->subHour(),
        ]);

        $this->artisan('secrets:clean --dry-run')
            ->assertSuccessful();

        $this->assertNotNull(Secret::find($expiredSecret->id));

        $expiredSecret->delete();
    }

    public function testOutputsNothingToCleanMessage(): void
    {
        // Delete any existing expired secrets first
        Secret::where('expire_at', '<', now())->delete();
        Secret::whereNotNull('revoked_at')->delete();

        $this->artisan('secrets:clean')
            ->expectsOutput('No expired secrets to clean.')
            ->assertSuccessful();
    }
}
