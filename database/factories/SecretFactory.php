<?php

namespace Database\Factories;

use App\Enums\SecretType;
use App\Models\MagicLink;
use App\Models\Secret;
use App\Services\SecretStorageService;
use App\Services\TokenService;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Builds secrets shaped like the ones SecretsController::store persists from
 * the browser payload (resources/js/utils.js buildCipherMeta), cipher_meta keys
 * in the order StoreSecretRequest::validated() returns them.
 *
 * @extends Factory<Secret>
 */
class SecretFactory extends Factory
{
    private const ALGORITHM = 'AES-256-GCM';

    private const CRYPTO_VERSION = 1;

    private const KDF = 'PBKDF2-SHA256-600k';

    private const IV_BYTES = 12;

    private const SALT_BYTES = 16;

    private const GCM_TAG_BYTES = 16;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'token' => app(TokenService::class)->generatePublicToken(),
            'type' => SecretType::Text,
            'cipher_meta' => [
                'alg' => self::ALGORITHM,
                'iv' => self::randomBase64Url(self::IV_BYTES),
                'version' => self::CRYPTO_VERSION,
                'has_passphrase' => false,
            ],
            'ciphertext' => self::randomBase64Url(48 + self::GCM_TAG_BYTES),
            'file_path' => null,
            'max_views' => null,
            'read_count' => 0,
            'expire_at' => now()->addDays(7),
        ];
    }

    public function text(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => SecretType::Text,
            'ciphertext' => self::randomBase64Url(48 + self::GCM_TAG_BYTES),
            'file_path' => null,
        ]);
    }

    /**
     * The path follows SecretStorageService::buildPath() and tracks the final
     * token, including one passed to create().
     */
    public function file(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => SecretType::File,
            'ciphertext' => null,
            'file_path' => fn (array $attributes): string => self::storagePath($attributes['token']),
        ]);
    }

    /**
     * File secret whose encrypted blob is written to the secrets disk.
     * Call Storage::fake('secrets') first in tests.
     */
    public function withStoredBlob(string $contents = 'encrypted-blob'): static
    {
        return $this->file()->afterCreating(function (Secret $secret) use ($contents): void {
            if ($secret->file_path === null) {
                return;
            }

            app(SecretStorageService::class)->disk()->put($secret->file_path, $contents);
        });
    }

    public function singleUse(): static
    {
        return $this->state(fn (array $attributes) => [
            'max_views' => 1,
        ]);
    }

    public function withMaxViews(int $maxViews): static
    {
        return $this->state(fn (array $attributes) => [
            'max_views' => $maxViews,
        ]);
    }

    public function withPassphrase(): static
    {
        return $this->state(fn (array $attributes) => [
            'cipher_meta' => [
                'alg' => self::ALGORITHM,
                'iv' => self::randomBase64Url(self::IV_BYTES),
                'version' => self::CRYPTO_VERSION,
                'salt' => self::randomBase64Url(self::SALT_BYTES),
                'iv2' => self::randomBase64Url(self::IV_BYTES),
                'kdf' => self::KDF,
                'has_passphrase' => true,
            ],
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expire_at' => now()->subHour(),
        ]);
    }

    public function withoutExpiry(): static
    {
        return $this->state(fn (array $attributes) => [
            'expire_at' => null,
        ]);
    }

    public function expiresIn(int $hours): static
    {
        return $this->state(fn (array $attributes) => [
            'expire_at' => now()->addHours($hours),
        ]);
    }

    /**
     * Revocation always destroys the content (AdminController::revoke).
     */
    public function revoked(): static
    {
        return $this->state(fn (array $attributes) => [
            'revoked_at' => now(),
            'ciphertext' => null,
            'file_path' => null,
        ]);
    }

    public function read(int $count = 1): static
    {
        return $this->state(fn (array $attributes) => [
            'read_count' => $count,
            'first_read_at' => now()->subMinutes(10),
            'last_read_at' => now(),
        ]);
    }

    /**
     * Max views reached and content destroyed, as after the last confirmRead.
     * Keeps a max_views set by an earlier state, defaults to single use.
     */
    public function consumed(): static
    {
        return $this->state(function (array $attributes) {
            $maxViews = is_int($attributes['max_views'] ?? null) ? $attributes['max_views'] : 1;

            return [
                'max_views' => $maxViews,
                'read_count' => $maxViews,
                'first_read_at' => now()->subMinutes(10),
                'last_read_at' => now(),
                'ciphertext' => null,
                'file_path' => null,
            ];
        });
    }

    public function withCreatorEmail(#[\SensitiveParameter] string $email): static
    {
        return $this->state(fn (array $attributes) => [
            'creator_email_hash' => MagicLink::hashEmail($email),
        ]);
    }

    private static function storagePath(mixed $token): string
    {
        $token = is_string($token) ? $token : '';

        return substr($token, 0, 2).'/'.$token;
    }

    private static function randomBase64Url(int $bytes): string
    {
        return rtrim(strtr(base64_encode(random_bytes(max(1, $bytes))), '+/', '-_'), '=');
    }
}
