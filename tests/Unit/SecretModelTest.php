<?php

namespace Tests\Unit;

use App\Enums\SecretType;
use App\Models\Secret;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SecretModelTest extends TestCase
{
    /**
     * @return array<string, array{0: int, 1: int, 2: bool}>
     */
    public static function maxViewsStates(): array
    {
        return [
            'limite de vues atteinte' => [3, 3, true],
            'limite de vues non atteinte' => [5, 2, false],
        ];
    }

    /** Vérifie que isExpired retourne true quand la date d'expiration est passée. */
    public function testIsExpiredReturnsTrueWhenExpireAtIsPast(): void
    {
        $this->freezeTime();
        $secret = Secret::factory()->expired()->create();

        $this->assertTrue($secret->isExpired());
    }

    /** Vérifie que isExpired retourne false quand la date d'expiration est future. */
    public function testIsExpiredReturnsFalseWhenExpireAtIsFuture(): void
    {
        $this->freezeTime();
        $secret = Secret::factory()->expiresIn(24)->create();

        $this->assertFalse($secret->isExpired());
    }

    /** Vérifie que isExpired retourne false pour un secret sans date d'expiration. */
    public function testIsExpiredReturnsFalseWithoutExpiry(): void
    {
        $secret = Secret::factory()->withoutExpiry()->create();

        $this->assertFalse($secret->isExpired());
    }

    /** Vérifie que isRevoked retourne true quand le secret est révoqué. */
    public function testIsRevokedReturnsTrueWhenRevoked(): void
    {
        $secret = Secret::factory()->revoked()->create();

        $this->assertTrue($secret->isRevoked());
    }

    /** Vérifie que isRevoked retourne false quand le secret n'est pas révoqué. */
    public function testIsRevokedReturnsFalseWhenNotRevoked(): void
    {
        $secret = Secret::factory()->create();

        $this->assertFalse($secret->isRevoked());
    }

    /** Vérifie que hasReachedMaxViews retourne true quand les lectures dépassent la limite. */
    public function testHasReachedMaxViewsReturnsTrueWhenReadCountExceedsLimit(): void
    {
        $secret = Secret::factory()->withMaxViews(3)->read(5)->create();

        $this->assertTrue($secret->hasReachedMaxViews());
    }

    /** Vérifie que hasReachedMaxViews retourne false quand les lectures restent sous la limite. */
    public function testHasReachedMaxViewsReturnsFalseWhenUnderLimit(): void
    {
        $secret = Secret::factory()->withMaxViews(5)->read(2)->create();

        $this->assertFalse($secret->hasReachedMaxViews());
    }

    /** Vérifie que hasReachedMaxViews retourne false sans limite de vues. */
    public function testHasReachedMaxViewsReturnsFalseWithoutLimit(): void
    {
        $secret = Secret::factory()->read(100)->create();

        $this->assertFalse($secret->hasReachedMaxViews());
    }

    /** Vérifie que shouldBeDestroyed suit l'atteinte de la limite de vues. */
    #[DataProvider('maxViewsStates')]
    public function testShouldBeDestroyedFollowsMaxViewsLimit(int $maxViews, int $readCount, bool $expected): void
    {
        $secret = Secret::factory()->withMaxViews($maxViews)->read($readCount)->create();

        $this->assertSame($expected, $secret->shouldBeDestroyed());
    }

    /** Vérifie que isAccessible retourne true pour un secret valide. */
    public function testIsAccessibleReturnsTrueForValidSecret(): void
    {
        $this->freezeTime();
        $secret = Secret::factory()->create();

        $this->assertTrue($secret->isAccessible());
    }

    /** Vérifie que isAccessible retourne false quand le secret est expiré. */
    public function testIsAccessibleReturnsFalseWhenExpired(): void
    {
        $this->freezeTime();
        $secret = Secret::factory()->expired()->create();

        $this->assertFalse($secret->isAccessible());
    }

    /** Vérifie que isAccessible retourne false quand le secret est révoqué. */
    public function testIsAccessibleReturnsFalseWhenRevoked(): void
    {
        $this->freezeTime();
        $secret = Secret::factory()->revoked()->create();

        $this->assertFalse($secret->isAccessible());
    }

    /** Vérifie que isAccessible retourne false dès que les lectures atteignent la limite de vues. */
    public function testIsAccessibleReturnsFalseWhenMaxViewsReached(): void
    {
        $this->freezeTime();
        $secret = Secret::factory()->singleUse()->read()->create();

        $this->assertFalse($secret->isAccessible());
    }

    /** Vérifie que isAccessible retourne true quand les lectures restent sous la limite de vues. */
    public function testIsAccessibleReturnsTrueWhenUnderMaxViews(): void
    {
        $this->freezeTime();
        $secret = Secret::factory()->withMaxViews(3)->read(2)->create();

        $this->assertTrue($secret->isAccessible());
    }

    /** Vérifie que incrementReadCount persiste le compteur incrémenté à chaque lecture. */
    public function testIncrementReadCountPersistsIncrementedCounter(): void
    {
        $this->freezeTime();
        $secret = Secret::factory()->create();

        $secret->incrementReadCount();
        $secret->incrementReadCount();

        $this->assertSame(2, $secret->fresh()?->read_count);
    }

    /** Vérifie que la première lecture date first_read_at et last_read_at à l'instant de la lecture. */
    public function testFirstReadSetsFirstAndLastReadAtToNow(): void
    {
        $this->travelTo('2026-09-15 10:00:00');
        $secret = Secret::factory()->create();

        $secret->incrementReadCount();

        $secret->refresh();
        $this->assertSame('2026-09-15 10:00:00', $secret->first_read_at?->toDateTimeString());
        $this->assertSame('2026-09-15 10:00:00', $secret->last_read_at?->toDateTimeString());
    }

    /** Vérifie qu'une lecture ultérieure ne modifie pas first_read_at. */
    public function testLaterReadKeepsFirstReadAt(): void
    {
        $this->travelTo('2026-09-15 10:00:00');
        $secret = Secret::factory()->create();
        $secret->incrementReadCount();
        $this->travel(5)->minutes();

        $secret->incrementReadCount();

        $this->assertSame('2026-09-15 10:00:00', $secret->fresh()?->first_read_at?->toDateTimeString());
    }

    /** Vérifie qu'une lecture ultérieure avance last_read_at à l'instant de cette lecture. */
    public function testLaterReadMovesLastReadAtToNow(): void
    {
        $this->travelTo('2026-09-15 10:00:00');
        $secret = Secret::factory()->create();
        $secret->incrementReadCount();
        $this->travel(5)->minutes();

        $secret->incrementReadCount();

        $this->assertSame('2026-09-15 10:05:00', $secret->fresh()?->last_read_at?->toDateTimeString());
    }

    /** Vérifie que recordFetch incrémente fetch_count sans modifier updated_at. */
    public function testRecordFetchIncrementsFetchCountWithoutTouchingUpdatedAt(): void
    {
        $this->travelTo('2026-09-15 10:00:00');
        $secret = Secret::factory()->create();
        $this->travel(1)->hour();

        $secret->recordFetch();

        $secret->refresh();
        $this->assertSame(1, $secret->fetch_count);
        $this->assertSame('2026-09-15 10:00:00', $secret->updated_at->toDateTimeString());
    }

    /** Vérifie que destroyContent efface le ciphertext d'un secret texte. */
    public function testDestroyContentClearsCiphertextOfTextSecret(): void
    {
        $secret = Secret::factory()->text()->create();

        $secret->destroyContent();

        $this->assertNull($secret->fresh()?->ciphertext);
    }

    /** Vérifie que destroyContent efface le chemin du fichier d'un secret fichier. */
    public function testDestroyContentClearsFilePathOfFileSecret(): void
    {
        $secret = Secret::factory()->file()->create();

        $secret->destroyContent();

        $this->assertNull($secret->fresh()?->file_path);
    }

    /** Vérifie que hasCreatorEmail retourne true quand l'email du créateur est enregistré. */
    public function testHasCreatorEmailReturnsTrueWhenSet(): void
    {
        $secret = Secret::factory()->withCreatorEmail('test@example.com')->create();

        $this->assertTrue($secret->hasCreatorEmail());
    }

    /** Vérifie que hasCreatorEmail retourne false sans email du créateur. */
    public function testHasCreatorEmailReturnsFalseWhenNotSet(): void
    {
        $secret = Secret::factory()->create();

        $this->assertFalse($secret->hasCreatorEmail());
    }

    /** Vérifie que verifyCreatorEmail accepte l'email du créateur quelles que soient la casse et les espaces. */
    public function testVerifyCreatorEmailReturnsTrueForCorrectEmail(): void
    {
        $secret = Secret::factory()->withCreatorEmail('test@example.com')->create();

        $this->assertTrue($secret->verifyCreatorEmail('test@example.com'));
        $this->assertTrue($secret->verifyCreatorEmail('TEST@EXAMPLE.COM'));
        $this->assertTrue($secret->verifyCreatorEmail('  test@example.com  '));
    }

    /** Vérifie que verifyCreatorEmail refuse un autre email. */
    public function testVerifyCreatorEmailReturnsFalseForWrongEmail(): void
    {
        $secret = Secret::factory()->withCreatorEmail('correct@example.com')->create();

        $this->assertFalse($secret->verifyCreatorEmail('wrong@example.com'));
    }

    /** Vérifie que verifyCreatorEmail retourne false sans email du créateur. */
    public function testVerifyCreatorEmailReturnsFalseWhenNoEmailSet(): void
    {
        $secret = Secret::factory()->create();

        $this->assertFalse($secret->verifyCreatorEmail('any@example.com'));
    }

    /** Vérifie que le type relu depuis la base est l'enum SecretType. */
    public function testTypeIsCastToEnum(): void
    {
        $secret = Secret::factory()->file()->create();

        $this->assertSame(SecretType::File, $secret->fresh()?->type);
    }

    /** Vérifie que le type enum est persisté sous sa valeur scalaire. */
    public function testTypeIsStoredAsScalarValue(): void
    {
        $secret = Secret::factory()->text()->create();

        $this->assertSame('text', DB::table('secrets')->where('id', $secret->id)->value('type'));
    }
}
