<?php

namespace Tests\Feature;

use App\Models\Secret;
use App\Services\StatsService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StatsTrackingTest extends TestCase
{
    private const CIPHERTEXT = 'ZGRkZGRkZGRkZGRkZGRkZGRkZGRkZGRkZGRkZGRkZGQ';

    private const TODAY = '2026-09-15';

    private function metricCount(string $metric): int
    {
        $count = DB::table('stats_daily')
            ->where('metric', $metric)
            ->where('date', self::TODAY)
            ->value('count');

        return is_numeric($count) ? (int) $count : 0;
    }

    /**
     * @return array<string, mixed>
     */
    private function textSecretPayload(): array
    {
        return [
            'type' => 'text',
            'ciphertext' => self::CIPHERTEXT,
            'cipher_meta' => [
                'alg' => 'AES-256-GCM',
                'iv' => 'YWFhYWFhYWFhYWFh',
                'version' => 1,
            ],
            'expiration' => '7d',
        ];
    }

    /** Vérifie que la création d'un secret texte incrémente le compteur et la taille cumulée. */
    public function testTextSecretCreationIncrementsStats(): void
    {
        $this->travelTo(self::TODAY.' 12:00:00');

        $this->postJson('/api/secrets', $this->textSecretPayload())->assertCreated();

        $this->assertSame(1, $this->metricCount(StatsService::SECRETS_CREATED_TEXT));
        $this->assertSame(43, $this->metricCount(StatsService::TOTAL_TEXT_SIZE_BYTES));
    }

    /** Vérifie que la création d'un secret fichier incrémente le compteur et la taille du fichier chiffré. */
    public function testFileSecretCreationIncrementsStats(): void
    {
        $this->travelTo(self::TODAY.' 12:00:00');
        Storage::fake('secrets');

        $this->postJson('/api/secrets', [
            'type' => 'file',
            'encrypted_file' => UploadedFile::fake()->create('test.bin', 256),
            'cipher_meta' => json_encode([
                'alg' => 'AES-256-GCM',
                'iv' => 'YWFhYWFhYWFhYWFh',
                'version' => 1,
            ]),
            'expiration' => '7d',
        ])->assertCreated();

        $this->assertSame(1, $this->metricCount(StatsService::SECRETS_CREATED_FILE));
        $this->assertSame(256 * 1024, $this->metricCount(StatsService::TOTAL_FILE_SIZE_BYTES));
    }

    /** Vérifie que la création incrémente la heatmap au jour et à l'heure de création. */
    public function testHeatmapCreatedIsIncremented(): void
    {
        $this->travelTo(self::TODAY.' 14:20:00');

        $this->postJson('/api/secrets', $this->textSecretPayload())->assertCreated();

        $this->assertSame(1, app(StatsService::class)->getHeatmap(StatsService::HEATMAP_SECRETS_CREATED)[2][14]);
    }

    /** Vérifie que confirm-read incrémente les lectures et la heatmap de lecture au jour et à l'heure de lecture. */
    public function testConfirmReadIncrementsReadStatsAndHeatmap(): void
    {
        $this->travelTo(self::TODAY.' 14:20:00');
        $secret = Secret::factory()->create();

        $this->postJson("/api/secrets/{$secret->token}/read")->assertOk();

        $this->assertSame(1, $this->metricCount(StatsService::SECRETS_READ));
        $this->assertSame(1, app(StatsService::class)->getHeatmap(StatsService::HEATMAP_SECRETS_READ)[2][14]);
    }

    /** Vérifie que l'atteinte du max_views incrémente les stats. */
    public function testMaxViewsReachedIncrementsStats(): void
    {
        $this->travelTo(self::TODAY.' 12:00:00');
        $secret = Secret::factory()->singleUse()->create();

        $this->postJson("/api/secrets/{$secret->token}/read")->assertOk();

        $this->assertSame(1, $this->metricCount(StatsService::SECRETS_MAX_VIEWS_REACHED));
    }

    /** Vérifie que la première lecture enregistre le délai écoulé depuis la création. */
    public function testFirstReadDelayIsTracked(): void
    {
        $this->travelTo(self::TODAY.' 11:58:30');
        $secret = Secret::factory()->create();
        $this->travelTo(self::TODAY.' 12:00:00');

        $this->postJson("/api/secrets/{$secret->token}/read")->assertOk();

        $this->assertSame(90, $this->metricCount(StatsService::FIRST_READ_DELAY_TOTAL));
        $this->assertSame(1, $this->metricCount(StatsService::FIRST_READ_DELAY_COUNT));
    }

    /** Vérifie que le délai n'est pas suivi sur les lectures suivantes. */
    public function testFirstReadDelayNotTrackedOnSubsequentReads(): void
    {
        $this->travelTo(self::TODAY.' 12:00:00');
        $secret = Secret::factory()->create();

        $this->postJson("/api/secrets/{$secret->token}/read")->assertOk();
        $this->postJson("/api/secrets/{$secret->token}/read")->assertOk();

        $this->assertSame(1, $this->metricCount(StatsService::FIRST_READ_DELAY_COUNT));
        $this->assertSame(2, $this->metricCount(StatsService::SECRETS_READ));
    }
}
