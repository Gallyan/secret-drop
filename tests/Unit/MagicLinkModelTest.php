<?php

namespace Tests\Unit;

use App\Models\MagicLink;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Sleep;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
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

    /** Vérifie que invalidateOtherPendingLinks brûle les autres liens en attente du destinataire, sans toucher au lien courant. */
    public function testInvalidateOtherPendingLinksBurnsOnlyOtherPendingLinksOfRecipient(): void
    {
        $this->travelTo('2026-09-15 10:00:00');
        $pendingLinks = MagicLink::factory()->count(2)->forEmail('owner@example.com')->valid()->create();
        $usedLink = MagicLink::factory()->forEmail('owner@example.com')->create(['used_at' => '2026-09-15 09:58:00']);
        $expiredLink = MagicLink::factory()->forEmail('owner@example.com')->expired()->create();
        $otherRecipientLink = MagicLink::factory()->forEmail('someone-else@example.com')->valid()->create();
        $latestLink = MagicLink::factory()->forEmail('owner@example.com')->valid()->create();

        $invalidated = $latestLink->invalidateOtherPendingLinks();

        $this->assertSame(2, $invalidated);
        foreach ($pendingLinks as $pendingLink) {
            $this->assertSame('2026-09-15 10:00:00', $pendingLink->refresh()->used_at?->toDateTimeString());
        }
        $this->assertNull($latestLink->refresh()->used_at);
        $this->assertSame('2026-09-15 09:58:00', $usedLink->refresh()->used_at?->toDateTimeString());
        $this->assertNull($expiredLink->refresh()->used_at);
        $this->assertNull($otherRecipientLink->refresh()->used_at);
    }

    /** Vérifie que issueExclusively crée le lien, le livre puis brûle les autres liens en attente du destinataire. */
    public function testIssueExclusivelyDeliversNewLinkThenBurnsPreviousPendingLinks(): void
    {
        $this->travelTo('2026-09-15 10:00:00');
        Config::set('secrets.magic_link_ttl', 10);
        $emailHash = MagicLink::hashEmail('owner@example.com');
        $previousLink = MagicLink::factory()->forEmail('owner@example.com')->valid()->create();
        $pendingDuringDelivery = null;

        $countPendingLinks = function () use (&$pendingDuringDelivery): void {
            $pendingDuringDelivery = MagicLink::whereNull('used_at')->count();
        };

        $issued = MagicLink::issueExclusively($emailHash, 'new-token-hash', $countPendingLinks);

        $this->assertTrue($issued);
        $this->assertSame(2, $pendingDuringDelivery);
        $newLink = MagicLink::where('token_hash', 'new-token-hash')->sole();
        $this->assertSame($emailHash, $newLink->email_hash);
        $this->assertSame('2026-09-15 10:10:00', $newLink->expire_at->toDateTimeString());
        $this->assertNull($newLink->used_at);
        $this->assertSame('2026-09-15 10:00:00', $previousLink->refresh()->used_at?->toDateTimeString());
    }

    /** Vérifie qu'un échec de livraison laisse le lien précédent utilisable et libère le verrou. */
    public function testIssueExclusivelyKeepsPreviousLinkWhenDeliveryFails(): void
    {
        $emailHash = MagicLink::hashEmail('owner@example.com');
        $previousLink = MagicLink::factory()->forEmail('owner@example.com')->valid()->create();

        try {
            MagicLink::issueExclusively($emailHash, 'new-token-hash', function (): void {
                throw new RuntimeException('Mail transport down');
            });
            $this->fail('The delivery failure should propagate.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Mail transport down', $exception->getMessage());
        }

        $this->assertNull($previousLink->refresh()->used_at);
        $this->assertTrue(Cache::lock("magic-link-issue:{$emailHash}", 1)->get());
    }

    /** Vérifie que issueExclusively abandonne sans exception ni lien quand le verrou du destinataire reste pris. */
    public function testIssueExclusivelyGivesUpSilentlyWhileLockIsHeld(): void
    {
        Sleep::fake(syncWithCarbon: true);
        $emailHash = MagicLink::hashEmail('owner@example.com');
        $previousLink = MagicLink::factory()->forEmail('owner@example.com')->valid()->create();
        Cache::lock("magic-link-issue:{$emailHash}", 60)->get();
        $delivered = false;

        $issued = MagicLink::issueExclusively($emailHash, 'new-token-hash', function () use (&$delivered): void {
            $delivered = true;
        });

        $this->assertFalse($issued);
        $this->assertFalse($delivered);
        $this->assertDatabaseCount('magic_links', 1);
        $this->assertNull($previousLink->refresh()->used_at);
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
