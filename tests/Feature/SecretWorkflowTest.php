<?php

namespace Tests\Feature;

use App\Models\Secret;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SecretWorkflowTest extends TestCase
{
    private const VALID_IV = 'YWFhYWFhYWFhYWFh'; // 12 octets

    private const VALID_CIPHERTEXT = 'ZGRkZGRkZGRkZGRkZGRkZGRkZGRkZGRkZGRkZGRkZGQ'; // 32 octets

    /** Vérifie qu'un secret texte créé par l'API se relit à l'identique puis devient introuvable après la lecture unique. */
    public function testTextSecretCreatedThroughApiIsReadOnceThenGone(): void
    {
        $token = $this->postJson('/api/secrets', [
            'type' => 'text',
            'ciphertext' => self::VALID_CIPHERTEXT,
            'cipher_meta' => ['alg' => 'AES-256-GCM', 'iv' => self::VALID_IV, 'version' => 1],
            'expiration' => '7d',
            'max_views' => 1,
        ])->assertCreated()->json('token');

        $this->get("/s/{$token}")->assertOk();
        $this->getJson("/api/secrets/{$token}")
            ->assertOk()
            ->assertExactJson([
                'type' => 'text',
                'cipher_meta' => ['alg' => 'AES-256-GCM', 'iv' => self::VALID_IV, 'version' => 1],
                'will_be_destroyed' => true,
                'single_use' => true,
                'previous_fetches' => 0,
                'ciphertext' => self::VALID_CIPHERTEXT,
            ]);
        $this->postJson("/api/secrets/{$token}/read")->assertOk();

        $this->getJson("/api/secrets/{$token}")
            ->assertNotFound()
            ->assertExactJson(['error' => 'not_found']);
    }

    /** Vérifie qu'un fichier créé par l'API se télécharge à l'identique puis que son blob est supprimé après la lecture unique. */
    public function testFileSecretCreatedThroughApiIsDownloadedOnceThenBlobDeleted(): void
    {
        Storage::fake('secrets');
        $file = UploadedFile::fake()->createWithContent('encrypted.bin', 'encrypted-file-bytes');

        $token = $this->postJson('/api/secrets', [
            'type' => 'file',
            'encrypted_file' => $file,
            'cipher_meta' => json_encode(['alg' => 'AES-256-GCM', 'iv' => self::VALID_IV, 'version' => 1]),
            'expiration' => '1d',
            'max_views' => 1,
        ])->assertCreated()->json('token');
        $filePath = (string) Secret::where('token', $token)->value('file_path');

        $this->getJson("/api/secrets/{$token}")
            ->assertOk()
            ->assertJsonPath('type', 'file')
            ->assertJsonPath('will_be_destroyed', true);
        $download = $this->get("/s/{$token}/download");
        $download->assertOk();
        $this->assertSame('encrypted-file-bytes', $download->streamedContent());
        $this->postJson("/api/secrets/{$token}/read")->assertOk();

        Storage::disk('secrets')->assertMissing($filePath);
        $this->assertNull(Secret::where('token', $token)->value('file_path'));
        $this->getJson("/api/secrets/{$token}")->assertNotFound();
        $this->get("/s/{$token}/download")->assertNotFound();
    }
}
