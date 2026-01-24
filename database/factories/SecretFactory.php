<?php

namespace Database\Factories;

use App\Models\Secret;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Secret>
 */
class SecretFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'token' => Str::random(32),
            'admin_token' => Str::random(32),
            'type' => 'text',
            'cipher_meta' => [
                'v' => 1,
                'iv' => base64_encode(random_bytes(12)),
                'salt' => base64_encode(random_bytes(16)),
            ],
            'ciphertext' => base64_encode(random_bytes(64)),
            'usage_unique' => false,
            'max_views' => null,
            'read_count' => 0,
            'expire_at' => now()->addDays(7),
        ];
    }

    public function text(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'text',
            'ciphertext' => base64_encode(random_bytes(64)),
            'file_path' => null,
            'filename' => null,
            'mime' => null,
            'size' => null,
        ]);
    }

    public function file(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'file',
            'ciphertext' => null,
            'file_path' => 'secrets/' . Str::random(40) . '.enc',
            'filename' => fake()->word() . '.pdf',
            'mime' => 'application/pdf',
            'size' => fake()->numberBetween(1024, 1024 * 1024),
        ]);
    }

    public function singleUse(): static
    {
        return $this->state(fn (array $attributes) => [
            'usage_unique' => true,
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
                'v' => 1,
                'iv' => base64_encode(random_bytes(12)),
                'salt' => base64_encode(random_bytes(16)),
                'passphrase' => true,
            ],
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expire_at' => now()->subHour(),
        ]);
    }

    public function revoked(): static
    {
        return $this->state(fn (array $attributes) => [
            'revoked_at' => now(),
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

    public function withCreatorEmail(string $email): static
    {
        return $this->state(fn (array $attributes) => [
            'creator_email_hash' => hash('sha256', strtolower(trim($email))),
        ]);
    }

    public function expiresIn(int $hours): static
    {
        return $this->state(fn (array $attributes) => [
            'expire_at' => now()->addHours($hours),
        ]);
    }
}
