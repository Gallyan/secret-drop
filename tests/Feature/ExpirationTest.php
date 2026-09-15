<?php

namespace Tests\Feature;

use App\Models\Secret;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ExpirationTest extends TestCase
{
    /**
     * @return array<string, array{0: string, 1: string, 2: string}>
     */
    public static function expirationChoices(): array
    {
        return [
            '1 heure' => ['1h', '2026-03-10T15:00:00+00:00', '2026-03-10 15:00:00'],
            '1 jour' => ['1d', '2026-03-11T14:00:00+00:00', '2026-03-11 14:00:00'],
            '7 jours' => ['7d', '2026-03-17T14:00:00+00:00', '2026-03-17 14:00:00'],
            '30 jours' => ['30d', '2026-04-09T14:00:00+00:00', '2026-04-09 14:00:00'],
            '90 jours' => ['90d', '2026-06-08T14:00:00+00:00', '2026-06-08 14:00:00'],
        ];
    }

    /** Vérifie que chaque durée proposée fixe expire_at exactement, dans la réponse comme en base. */
    #[DataProvider('expirationChoices')]
    public function testExpirationChoiceSetsExactExpireAt(string $expiration, string $expectedIso, string $expectedStored): void
    {
        $this->travelTo('2026-03-10 14:00:00');

        $response = $this->postJson('/api/secrets', $this->validPayload($expiration));

        $response->assertCreated();
        $response->assertJsonPath('expire_at', $expectedIso);

        $secret = Secret::where('token', $response->json('token'))->firstOrFail();
        $this->assertSame($expectedStored, $secret->expire_at?->toDateTimeString());
    }

    /** Vérifie qu'un secret reste lisible à l'instant exact de son expiration. */
    public function testSecretIsAccessibleAtExactExpirationInstant(): void
    {
        $this->travelTo('2026-03-10 14:00:00');
        $secret = Secret::factory()->expiresIn(1)->create();
        $this->travelTo('2026-03-10 15:00:00');

        $response = $this->getJson("/api/secrets/{$secret->token}");

        $response->assertOk();
    }

    /** Vérifie qu'un secret renvoie 404 une seconde après son expiration. */
    public function testSecretReturns404OneSecondAfterExpiration(): void
    {
        $this->travelTo('2026-03-10 14:00:00');
        $secret = Secret::factory()->expiresIn(1)->create();
        $this->travelTo('2026-03-10 15:00:01');

        $response = $this->getJson("/api/secrets/{$secret->token}");

        $response->assertNotFound();
        $response->assertExactJson(['error' => 'not_found']);
    }

    /**
     * @return array<string, mixed>
     */
    private function validPayload(string $expiration): array
    {
        return [
            'type' => 'text',
            'ciphertext' => 'ZGRkZGRkZGRkZGRkZGRkZGRkZGRkZGRkZGRkZGRkZGQ', // 32 octets
            'cipher_meta' => [
                'alg' => 'AES-256-GCM',
                'iv' => 'YWFhYWFhYWFhYWFh', // 12 octets
                'version' => 1,
            ],
            'expiration' => $expiration,
        ];
    }
}
