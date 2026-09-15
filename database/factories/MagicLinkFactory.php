<?php

namespace Database\Factories;

use App\Models\MagicLink;
use App\Services\TokenService;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Config;

/**
 * Builds magic links shaped like the ones AdminController::requestAccess and
 * SuperAdminController::requestAccess persist.
 *
 * @extends Factory<MagicLink>
 */
class MagicLinkFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'email_hash' => MagicLink::hashEmail(fake()->unique()->safeEmail()),
            'token_hash' => app(TokenService::class)->generateMagicLinkToken()['hash'],
            'expire_at' => now()->addMinutes(Config::integer('secrets.magic_link_ttl')),
        ];
    }

    public function forEmail(#[\SensitiveParameter] string $email): static
    {
        return $this->state(fn (array $attributes) => [
            'email_hash' => MagicLink::hashEmail($email),
        ]);
    }

    public function superAdmin(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_hash' => MagicLink::SUPER_ADMIN_EMAIL_HASH,
        ]);
    }

    /**
     * Stores the hash MagicLink::findByToken() looks up for this plain token.
     */
    public function withToken(#[\SensitiveParameter] string $token): static
    {
        return $this->state(fn (array $attributes) => [
            'token_hash' => app(TokenService::class)->hashToken($token),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expire_at' => now()->subMinute(),
        ]);
    }

    public function used(): static
    {
        return $this->state(fn (array $attributes) => [
            'used_at' => now(),
        ]);
    }

    public function valid(): static
    {
        return $this->state(fn (array $attributes) => [
            'expire_at' => now()->addMinutes(Config::integer('secrets.magic_link_ttl')),
            'used_at' => null,
        ]);
    }
}
