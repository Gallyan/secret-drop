<?php

namespace Tests\Unit;

use App\Models\MagicLink;
use Illuminate\Support\Facades\Config;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class MagicLinkModelTest extends TestCase
{
    /** Vérifie que isExpired retourne true quand le lien est expiré. */
    public function testIsExpiredReturnsTrueWhenExpired(): void
    {
        $magicLink = MagicLink::factory()->expired()->make();

        $this->assertTrue($magicLink->isExpired());
    }

    /** Vérifie que isExpired retourne false quand le lien est encore valide. */
    public function testIsExpiredReturnsFalseWhenNotExpired(): void
    {
        $magicLink = MagicLink::factory()->valid()->make();

        $this->assertFalse($magicLink->isExpired());
    }

    /** Vérifie que isUsed retourne true quand le lien a été utilisé. */
    public function testIsUsedReturnsTrueWhenUsed(): void
    {
        $magicLink = MagicLink::factory()->used()->make();

        $this->assertTrue($magicLink->isUsed());
    }

    /** Vérifie que isUsed retourne false quand le lien n'a pas été utilisé. */
    public function testIsUsedReturnsFalseWhenNotUsed(): void
    {
        $magicLink = MagicLink::factory()->valid()->make();

        $this->assertFalse($magicLink->isUsed());
    }

    /** Vérifie que isValid retourne true pour un lien frais. */
    public function testIsValidReturnsTrueForFreshLink(): void
    {
        $magicLink = MagicLink::factory()->valid()->make();

        $this->assertTrue($magicLink->isValid());
    }

    /** Vérifie que isValid retourne false quand le lien est expiré. */
    public function testIsValidReturnsFalseWhenExpired(): void
    {
        $magicLink = MagicLink::factory()->expired()->make();

        $this->assertFalse($magicLink->isValid());
    }

    /** Vérifie que isValid retourne false quand le lien a été utilisé. */
    public function testIsValidReturnsFalseWhenUsed(): void
    {
        $magicLink = MagicLink::factory()->used()->make();

        $this->assertFalse($magicLink->isValid());
    }

    /** Vérifie que markAsUsed consomme un lien valide et enregistre l'instant de consommation. */
    public function testMarkAsUsedConsumesValidLink(): void
    {
        $this->travelTo('2026-09-15 10:00:00');
        $magicLink = MagicLink::factory()->valid()->create();

        $consumed = $magicLink->markAsUsed();

        $this->assertTrue($consumed);
        $this->assertSame('2026-09-15 10:00:00', $magicLink->used_at?->toDateTimeString());
        $this->assertSame('2026-09-15 10:00:00', $magicLink->refresh()->used_at?->toDateTimeString());
    }

    /** Vérifie que, de deux instances chargées avant consommation, seule la première consomme le lien. */
    public function testMarkAsUsedFailsForSecondInstanceLoadedBeforeConsumption(): void
    {
        $this->freezeTime();
        $link = MagicLink::factory()->valid()->create();
        $firstRequestCopy = MagicLink::findOrFail($link->id);
        $secondRequestCopy = MagicLink::findOrFail($link->id);
        $firstRequestCopy->markAsUsed();
        $usedAt = $link->refresh()->used_at?->toDateTimeString();
        $this->travel(1)->minute();

        $consumed = $secondRequestCopy->markAsUsed();

        $this->assertFalse($consumed);
        $this->assertNull($secondRequestCopy->used_at);
        $this->assertSame($usedAt, $link->refresh()->used_at?->toDateTimeString());
    }

    /** Vérifie que markAsUsed refuse un lien expiré entre sa lecture et sa consommation. */
    public function testMarkAsUsedFailsWhenLinkExpiredSinceLoading(): void
    {
        $this->travelTo('2026-09-15 10:00:00');
        $magicLink = MagicLink::factory()->create(['expire_at' => '2026-09-15 10:05:00']);
        $this->travelTo('2026-09-15 10:05:01');

        $consumed = $magicLink->markAsUsed();

        $this->assertFalse($consumed);
        $this->assertNull($magicLink->refresh()->used_at);
    }

    /** Vérifie que findByToken retrouve le lien correspondant au jeton en clair. */
    public function testFindByTokenReturnsMatchingLink(): void
    {
        $magicLink = MagicLink::factory()->withToken('plain-token')->create();
        MagicLink::factory()->withToken('other-token')->create();

        $found = MagicLink::findByToken('plain-token');

        $this->assertSame($magicLink->id, $found?->id);
    }

    /** Vérifie que findByToken retourne null pour un jeton inconnu. */
    public function testFindByTokenReturnsNullForUnknownToken(): void
    {
        MagicLink::factory()->withToken('plain-token')->create();

        $found = MagicLink::findByToken('unknown-token');

        $this->assertNull($found);
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function spellingsOfTheSameEmail(): array
    {
        return [
            'minuscules' => ['owner@example.com'],
            'casse mixte' => ['Owner@Example.com'],
            'majuscules et espaces' => ['  OWNER@EXAMPLE.COM  '],
        ];
    }

    /** Vérifie que hashEmail produit le HMAC-SHA256 attendu, insensible à la casse et aux espaces. */
    #[DataProvider('spellingsOfTheSameEmail')]
    public function testHashEmailProducesKnownHmacForNormalizedEmail(string $email): void
    {
        Config::set('secrets.email_hash_pepper', 'test-pepper');

        $hash = MagicLink::hashEmail($email);

        $this->assertSame('898f59c8b41347886be2d4c52cf0d1b45907ad3d5729452a2e1f865bf046819e', $hash);
    }

    /** Vérifie que deux emails différents produisent des hash différents. */
    public function testHashEmailDiffersForDifferentEmails(): void
    {
        Config::set('secrets.email_hash_pepper', 'test-pepper');

        $this->assertNotSame(MagicLink::hashEmail('user1@example.com'), MagicLink::hashEmail('user2@example.com'));
    }
}
