<?php

namespace Tests\Feature;

use App\Models\Secret;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class DownloadSecurityTest extends TestCase
{
    /** Vérifie que le téléchargement force une pièce jointe générique non interprétable et passe par le middleware anti-cache. */
    public function testDownloadSendsAttachmentAndSecurityHeaders(): void
    {
        Storage::fake('secrets');
        $secret = Secret::factory()->withStoredBlob()->create();

        $response = $this->get("/s/{$secret->token}/download");

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/octet-stream');
        $response->assertHeader('Content-Disposition', 'attachment; filename="encrypted"');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Download-Options', 'noopen');
        $response->assertHeader('Cache-Control', 'max-age=0, must-revalidate, no-cache, no-store, private');
        $response->assertHeader('Pragma', 'no-cache');
        $response->assertHeader('Expires', 'Sat, 01 Jan 2000 00:00:00 GMT');
    }

    /** Vérifie que le téléchargement incrémente fetch_count sans toucher read_count. */
    public function testDownloadIncrementsFetchCountWithoutCountingRead(): void
    {
        Storage::fake('secrets');
        $secret = Secret::factory()->withStoredBlob()->create();

        $this->get("/s/{$secret->token}/download")->assertOk();

        $secret->refresh();
        $this->assertSame(1, $secret->fetch_count);
        $this->assertSame(0, $secret->read_count);
    }

    /**
     * @return array<string, array{0: 'expired'|'revoked'|'consumed'}>
     */
    public static function inaccessibleStates(): array
    {
        return [
            'expiré' => ['expired'],
            'révoqué' => ['revoked'],
            'max_views atteint' => ['consumed'],
        ];
    }

    /**
     * Vérifie qu'un fichier inaccessible dont le blob existe encore renvoie la page 404 sans incrémenter fetch_count.
     *
     * @param  'expired'|'revoked'|'consumed'  $state
     */
    #[DataProvider('inaccessibleStates')]
    public function testDownloadReturns404WithoutCountingFetchForInaccessibleFile(string $state): void
    {
        Storage::fake('secrets');
        $secret = $this->createInaccessibleFileWithBlob($state);

        $response = $this->get("/s/{$secret->token}/download");

        $response->assertNotFound();
        $response->assertViewIs('secrets.not-found');

        $secret->refresh();
        $this->assertNotNull($secret->file_path);
        $this->assertSame(0, $secret->fetch_count);
    }

    /** Vérifie que le téléchargement d'un secret texte renvoie la page 404. */
    public function testDownloadReturns404ForTextSecret(): void
    {
        Storage::fake('secrets');
        $secret = Secret::factory()->text()->create();

        $response = $this->get("/s/{$secret->token}/download");

        $response->assertNotFound();
        $response->assertViewIs('secrets.not-found');
    }

    /** Vérifie que le téléchargement d'un token inconnu renvoie la page 404. */
    public function testDownloadReturns404ForUnknownToken(): void
    {
        $response = $this->get('/s/nonexistenttoken12345678901/download');

        $response->assertNotFound();
        $response->assertViewIs('secrets.not-found');
    }

    /** Vérifie qu'un fichier accessible dont le blob manque renvoie la page 404 sans incrémenter fetch_count. */
    public function testDownloadReturns404WhenBlobIsMissing(): void
    {
        Storage::fake('secrets');
        $secret = Secret::factory()->file()->create();

        $response = $this->get("/s/{$secret->token}/download");

        $response->assertNotFound();
        $response->assertViewIs('secrets.not-found');

        $secret->refresh();
        $this->assertSame(0, $secret->fetch_count);
    }

    /** Vérifie qu'un fichier accessible sans file_path renvoie la page 404. */
    public function testDownloadReturns404WhenFilePathIsNull(): void
    {
        Storage::fake('secrets');
        $secret = Secret::factory()->file()->create(['file_path' => null]);

        $response = $this->get("/s/{$secret->token}/download");

        $response->assertNotFound();
        $response->assertViewIs('secrets.not-found');
    }

    /**
     * @param  'expired'|'revoked'|'consumed'  $state
     */
    private function createInaccessibleFileWithBlob(string $state): Secret
    {
        $factory = Secret::factory();

        return match ($state) {
            'expired' => $factory->expired()->withStoredBlob()->create(),
            'revoked' => $factory->revoked()->withStoredBlob()->create(),
            'consumed' => $factory->consumed()->withStoredBlob()->create(),
        };
    }
}
