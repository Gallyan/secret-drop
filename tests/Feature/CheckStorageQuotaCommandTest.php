<?php

namespace Tests\Feature;

use App\Console\Commands\CheckStorageQuotaCommand;
use App\Mail\StorageQuotaAlertMail;
use App\Services\SecretStorageService;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Number;
use Mockery\MockInterface;
use Tests\TestCase;

class CheckStorageQuotaCommandTest extends TestCase
{
    private const OPERATOR_EMAIL = 'operator@example.com';

    private const QUOTA = 1000000;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        Mail::fake();
        Config::set('app.super_admin_email', self::OPERATOR_EMAIL);
    }

    /** Vérifie qu'aucune alerte n'est envoyée tant que l'usage reste sous le seuil d'avertissement. */
    public function testSendsNothingBelowTheWarningThreshold(): void
    {
        $this->fakeStorage(790000);

        $this->artisan('storage:check')
            ->expectsOutputToContain('79%')
            ->expectsOutputToContain('Below the warning threshold')
            ->assertSuccessful();

        Mail::assertNothingSent();
    }

    /** Vérifie qu'une alerte d'avertissement part exactement au seuil de 80 %. */
    public function testSendsWarningMailAtTheWarningThreshold(): void
    {
        $this->fakeStorage(800000);

        $this->artisan('storage:check')->assertSuccessful();

        $this->assertAlertSent(CheckStorageQuotaCommand::LEVEL_WARNING);
    }

    /** Vérifie qu'une alerte d'avertissement part aussi entre les deux seuils. */
    public function testSendsWarningMailBetweenThresholds(): void
    {
        $this->fakeStorage(850000);

        $this->artisan('storage:check')->assertSuccessful();

        $this->assertAlertSent(CheckStorageQuotaCommand::LEVEL_WARNING);
    }

    /** Vérifie qu'une alerte critique part à partir de 90 %. */
    public function testSendsCriticalMailAtTheCriticalThreshold(): void
    {
        $this->fakeStorage(900000);

        $this->artisan('storage:check')->assertSuccessful();

        $this->assertAlertSent(CheckStorageQuotaCommand::LEVEL_CRITICAL);
    }

    /** Vérifie qu'un même niveau n'envoie qu'un seul email par 24 heures. */
    public function testDoesNotSendTheSameLevelTwiceWithinTwentyFourHours(): void
    {
        $this->fakeStorage(850000);

        $this->artisan('storage:check')->assertSuccessful();

        $this->artisan('storage:check')
            ->expectsOutputToContain('already sent in the last 24 hours')
            ->assertSuccessful();

        Mail::assertSentCount(1);
    }

    /** Vérifie que le niveau critique envoie son email même si l'avertissement est déjà parti le même jour. */
    public function testSendsCriticalMailEvenWhenTheWarningWasAlreadySent(): void
    {
        $this->fakeStorage(850000);
        $this->artisan('storage:check')->assertSuccessful();

        $this->fakeStorage(950000);
        $this->artisan('storage:check')->assertSuccessful();

        Mail::assertSentCount(2);
        $this->assertAlertSent(CheckStorageQuotaCommand::LEVEL_CRITICAL);
    }

    /** Vérifie qu'un niveau se réarme dès que l'usage repasse sous son seuil, sans attendre l'expiration du cache. */
    public function testRearmsALevelOnceUsageDropsBackBelowItsThreshold(): void
    {
        $this->fakeStorage(850000);
        $this->artisan('storage:check')->assertSuccessful();

        $this->fakeStorage(500000);
        $this->artisan('storage:check')->assertSuccessful();

        $this->fakeStorage(850000);
        $this->artisan('storage:check')->assertSuccessful();

        Mail::assertSentCount(2);
    }

    /** Vérifie qu'un quota illimité ne déclenche aucune alerte. */
    public function testSendsNothingWhenTheQuotaIsUnlimited(): void
    {
        $this->fakeStorage(900000, 0);

        $this->artisan('storage:check')
            ->expectsOutputToContain('unlimited')
            ->assertSuccessful();

        Mail::assertNothingSent();
    }

    /** Vérifie qu'aucun email n'est envoyé, avec un message explicite, quand aucune adresse opérateur n'est configurée. */
    public function testSendsNothingWhenNoOperatorEmailIsConfigured(): void
    {
        Config::set('app.super_admin_email', '');
        $this->fakeStorage(950000);

        $this->artisan('storage:check')
            ->expectsOutputToContain('No operator email configured')
            ->assertSuccessful();

        Mail::assertNothingSent();
    }

    /** Vérifie que l'email d'alerte annonce le niveau, le pourcentage, les tailles et la conséquence du quota atteint. */
    public function testAlertMailStatesLevelPercentageSizesAndConsequence(): void
    {
        $mail = new StorageQuotaAlertMail(CheckStorageQuotaCommand::LEVEL_CRITICAL, 950000, self::QUOTA);

        $rendered = $mail->render();

        $this->assertStringContainsString(e(__('messages.email_storage_quota_level_critical')), $rendered);
        $this->assertStringContainsString('95%', $rendered);
        $this->assertStringContainsString(e(__('messages.email_storage_quota_consequence')), $rendered);
        $this->assertStringContainsString(Number::fileSize(950000, 2), $rendered);
        $this->assertStringContainsString(Number::fileSize(self::QUOTA, 2), $rendered);
        $this->assertStringContainsString(
            __('messages.email_storage_quota_subject', [
                'level' => __('messages.email_storage_quota_level_critical'),
                'percent' => '95',
                'app' => config('app.name'),
            ]),
            (string) $mail->envelope()->subject,
        );
    }

    /** Vérifie que la vérification du quota est planifiée toutes les heures sans chevauchement. */
    public function testCommandIsScheduledHourly(): void
    {
        $event = collect(app(Schedule::class)->events())
            ->first(fn (Event $event): bool => str_contains((string) $event->command, 'storage:check'));

        $this->assertInstanceOf(Event::class, $event);
        $this->assertSame('0 * * * *', $event->expression);
        $this->assertTrue($event->withoutOverlapping);
        $this->assertSame(60, $event->expiresAt);
    }

    private function fakeStorage(int $used, int $quota = self::QUOTA): void
    {
        $this->mock(SecretStorageService::class, function (MockInterface $mock) use ($used, $quota): void {
            $mock->shouldReceive('totalStoredBytes')->andReturn($used);
            $mock->shouldReceive('quotaBytes')->andReturn($quota);
            $mock->shouldReceive('usageRatio')->andReturn($quota > 0 ? $used / $quota : 0.0);
        });
    }

    private function assertAlertSent(string $level): void
    {
        Mail::assertSent(StorageQuotaAlertMail::class, function (StorageQuotaAlertMail $mail) use ($level): bool {
            return $mail->hasTo(self::OPERATOR_EMAIL)
                && $mail->level === $level
                && $mail->locale === config('app.fallback_locale');
        });
    }
}
