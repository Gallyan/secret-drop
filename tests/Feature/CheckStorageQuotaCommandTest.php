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

    /** Vérifie qu'une alerte critique part à partir de 95 %. */
    public function testSendsCriticalMailAtTheCriticalThreshold(): void
    {
        $this->fakeStorage(950000);

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

        $this->fakeStorage(960000);
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
        $this->assertSame(
            StorageQuotaAlertMail::CRITICAL_EMOJI.' '.__('messages.email_storage_quota_subject', [
                'percent' => '95',
                'app' => config('app.name'),
            ]),
            (string) $mail->envelope()->subject,
        );
    }

    /** Vérifie que les deux niveaux partagent l'identité ambre du super-admin : ligne d'accent, en-tête, logo et lien de pied de page. */
    public function testBothLevelsShareTheAmberSuperAdminIdentity(): void
    {
        $critical = $this->renderedAlert(CheckStorageQuotaCommand::LEVEL_CRITICAL);
        $warning = $this->renderedAlert(CheckStorageQuotaCommand::LEVEL_WARNING);

        $shared = [
            '.accent-line' => 'background',
            '.header' => 'background',
            '.footer-brand a' => 'color',
        ];

        foreach ($shared as $selector => $property) {
            $criticalValues = $this->styleValues($critical, $selector, $property);

            $this->assertNotEmpty($criticalValues, "No `{$property}` declaration found for `{$selector}`.");
            $this->assertSame(
                $this->styleValues($warning, $selector, $property),
                $criticalValues,
                "`{$selector}` must keep the same amber identity on both levels.",
            );
        }

        $this->assertSame(['#d97706'], $this->styleValues($critical, '.footer-brand a', 'color'));
        $this->assertStringContainsString('#d97706', $this->styleValues($critical, '.accent-line', 'background')[0]);
        $this->assertStringContainsString('rgba(217, 119, 6, 0.06)', $this->styleValues($critical, '.header', 'background')[0]);
        $this->assertStringContainsString('rgba(217, 119, 6, 0.12)', $this->styleValues($critical, '.header', 'background')[1]);

        foreach ([$warning, $critical] as $rendered) {
            $this->assertStringContainsString('icon-192-amber.png', $rendered);
        }
    }

    /** Vérifie que seules la pastille de niveau et la jauge portent la couleur de gravité, le rouge restant lisible en mode sombre. */
    public function testOnlyTheLevelPillAndTheGaugeCarryTheSeverityColour(): void
    {
        $critical = $this->renderedAlert(CheckStorageQuotaCommand::LEVEL_CRITICAL);
        $warning = $this->renderedAlert(CheckStorageQuotaCommand::LEVEL_WARNING);

        $this->assertSame(
            ['linear-gradient(135deg, #d97706, #ea580c)'],
            $this->styleValues($warning, '.badge', 'background'),
        );
        $this->assertSame(
            ['linear-gradient(135deg, #b91c1c, #7f1d1d)'],
            $this->styleValues($critical, '.badge', 'background'),
        );

        $this->assertSame(['#ea580c', '#ea580c'], $this->styleValues($warning, '.gauge-value', 'color'));
        $this->assertSame(['#b91c1c', '#f87171'], $this->styleValues($critical, '.gauge-value', 'color'));
    }

    /** Vérifie que la phrase d'introduction qui doublait la jauge a bien disparu des deux versions de l'email. */
    public function testAlertMailNoLongerRepeatsThePercentageInAnIntroSentence(): void
    {
        $mail = new StorageQuotaAlertMail(CheckStorageQuotaCommand::LEVEL_CRITICAL, 970000, self::QUOTA);

        $this->assertStringContainsString('97%', $mail->render());
        $this->assertSame(1, substr_count($mail->render(), '97%'));

        foreach (glob(lang_path('*/messages.php')) ?: [] as $file) {
            /** @var array<string, string> $messages */
            $messages = require $file;

            $this->assertArrayNotHasKey('email_storage_quota_intro', $messages, "Stale intro key in {$file}.");
        }
    }

    /** Vérifie que --preview=warning envoie une alerte d'avertissement avec des chiffres simulés annoncés comme tels. */
    public function testPreviewSendsAWarningAlertWithSimulatedFigures(): void
    {
        $this->fakeStorage(1000);

        $this->artisan('storage:check', ['--preview' => CheckStorageQuotaCommand::LEVEL_WARNING])
            ->expectsOutputToContain('PREVIEW: sent the warning storage alert to '.self::OPERATOR_EMAIL)
            ->expectsOutputToContain(sprintf(
                'Simulated figures, not the real usage: %s / %s (85%%).',
                Number::fileSize(850000, 2),
                Number::fileSize(self::QUOTA, 2),
            ))
            ->expectsOutputToContain('cache was not read, written or cleared')
            ->assertSuccessful();

        $this->assertAlertSent(CheckStorageQuotaCommand::LEVEL_WARNING);
        Mail::assertSentCount(1);
    }

    /** Vérifie que --preview=critical envoie une alerte critique quel que soit l'usage réel. */
    public function testPreviewSendsACriticalAlertWhateverTheRealUsageIs(): void
    {
        $this->fakeStorage(1000);

        $this->artisan('storage:check', ['--preview' => CheckStorageQuotaCommand::LEVEL_CRITICAL])
            ->expectsOutputToContain('PREVIEW: sent the critical storage alert')
            ->expectsOutputToContain(sprintf(
                'Simulated figures, not the real usage: %s / %s (97%%).',
                Number::fileSize(970000, 2),
                Number::fileSize(self::QUOTA, 2),
            ))
            ->assertSuccessful();

        $this->assertAlertSent(CheckStorageQuotaCommand::LEVEL_CRITICAL);
    }

    /** Vérifie qu'un aperçu reste possible et plausible même quand le quota est illimité. */
    public function testPreviewWorksWhenTheQuotaIsUnlimited(): void
    {
        $this->fakeStorage(1000, 0);

        $this->artisan('storage:check', ['--preview' => CheckStorageQuotaCommand::LEVEL_WARNING])
            ->expectsOutputToContain('PREVIEW')
            ->assertSuccessful();

        $this->assertAlertSent(CheckStorageQuotaCommand::LEVEL_WARNING);
    }

    /** Vérifie qu'un aperçu n'écrit aucune clé de déduplication et n'empêche donc pas une vraie alerte ensuite. */
    public function testPreviewDoesNotWriteTheDeduplicationCache(): void
    {
        $this->fakeStorage(850000);

        $this->artisan('storage:check', ['--preview' => CheckStorageQuotaCommand::LEVEL_WARNING])->assertSuccessful();

        $this->assertFalse(Cache::has($this->alertCacheKey(CheckStorageQuotaCommand::LEVEL_WARNING)));

        $this->artisan('storage:check')->assertSuccessful();

        Mail::assertSentCount(2);
        $this->assertTrue(Cache::has($this->alertCacheKey(CheckStorageQuotaCommand::LEVEL_WARNING)));
    }

    /** Vérifie qu'un aperçu n'efface pas la clé d'une alerte réelle déjà partie. */
    public function testPreviewDoesNotClearTheDeduplicationCache(): void
    {
        $this->fakeStorage(850000);
        $this->artisan('storage:check')->assertSuccessful();

        $this->artisan('storage:check', ['--preview' => CheckStorageQuotaCommand::LEVEL_WARNING])->assertSuccessful();

        $this->assertTrue(Cache::has($this->alertCacheKey(CheckStorageQuotaCommand::LEVEL_WARNING)));

        $this->artisan('storage:check')
            ->expectsOutputToContain('already sent in the last 24 hours')
            ->assertSuccessful();

        Mail::assertSentCount(2);
    }

    /** Vérifie qu'une valeur inconnue de --preview échoue avec un message explicite et sans email. */
    public function testPreviewRejectsAnUnknownLevel(): void
    {
        $this->fakeStorage(850000);

        $this->artisan('storage:check', ['--preview' => 'bogus'])
            ->expectsOutputToContain('Unknown --preview value "bogus"')
            ->assertFailed();

        Mail::assertNothingSent();
        $this->assertFalse(Cache::has($this->alertCacheKey(CheckStorageQuotaCommand::LEVEL_WARNING)));
    }

    /** Vérifie que --preview sans valeur échoue au lieu de lancer une vérification normale. */
    public function testPreviewWithoutAValueFails(): void
    {
        $this->fakeStorage(850000);

        $this->artisan('storage:check', ['--preview' => null])
            ->expectsOutputToContain('The --preview option needs a value')
            ->assertFailed();

        Mail::assertNothingSent();
        $this->assertFalse(Cache::has($this->alertCacheKey(CheckStorageQuotaCommand::LEVEL_WARNING)));
    }

    /** Vérifie qu'un aperçu échoue explicitement quand aucune adresse opérateur n'est configurée. */
    public function testPreviewFailsWhenNoOperatorEmailIsConfigured(): void
    {
        Config::set('app.super_admin_email', '');
        $this->fakeStorage(850000);

        $this->artisan('storage:check', ['--preview' => CheckStorageQuotaCommand::LEVEL_CRITICAL])
            ->expectsOutputToContain('No operator email configured')
            ->assertFailed();

        Mail::assertNothingSent();
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

    private function renderedAlert(string $level): string
    {
        $used = $level === CheckStorageQuotaCommand::LEVEL_CRITICAL ? 970000 : 850000;

        return (new StorageQuotaAlertMail($level, $used, self::QUOTA))->render();
    }

    /**
     * Collects a CSS declaration from the rendered email, in source order, one entry per rule block.
     *
     * @return list<string>
     */
    private function styleValues(string $rendered, string $selector, string $property): array
    {
        preg_match_all('/(?<![\w-])'.preg_quote($selector, '/').'\s*\{([^}]*)\}/', $rendered, $blocks);

        $values = [];

        foreach ($blocks[1] as $block) {
            if (preg_match('/(?<![\w-])'.preg_quote($property, '/').'\s*:\s*([^;]+);/', $block, $declaration) === 1) {
                $values[] = trim($declaration[1]);
            }
        }

        return $values;
    }

    private function alertCacheKey(string $level): string
    {
        return CheckStorageQuotaCommand::CACHE_KEY_PREFIX.$level;
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
