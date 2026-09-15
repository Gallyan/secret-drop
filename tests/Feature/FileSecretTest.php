<?php

namespace Tests\Feature;

use App\Enums\SecretType;
use App\Models\Secret;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FileSecretTest extends TestCase
{
    private const VALID_IV = 'YWFhYWFhYWFhYWFh'; // 12 octets

    /** Limite de la règle encrypted_file max:14336 (kilo-octets). */
    private const MAX_FILE_KILOBYTES = 14336;

    /** Vérifie qu'un secret fichier est créé en 201, blob stocké à l'identique sous le chemin partitionné, vues illimitées par défaut. */
    public function testCreatesFileSecretAndStoresEncryptedBlob(): void
    {
        Storage::fake('secrets');
        $file = UploadedFile::fake()->createWithContent('encrypted', 'encrypted-payload-bytes');

        $response = $this->postJson('/api/secrets', $this->filePayload($file));

        $response->assertCreated();
        $token = $response->json('token');
        $this->assertIsString($token);

        $secret = Secret::where('token', $token)->firstOrFail();
        $this->assertSame(SecretType::File, $secret->type);
        $this->assertSame(substr($token, 0, 2)."/{$token}", $secret->file_path);
        $this->assertNull($secret->ciphertext);
        $this->assertNull($secret->max_views);

        Storage::disk('secrets')->assertExists($secret->file_path, 'encrypted-payload-bytes');
    }

    /** Vérifie que le fichier chiffré est requis pour un secret fichier. */
    public function testRejectsFileSecretWithoutEncryptedFile(): void
    {
        Storage::fake('secrets');

        $response = $this->postJson('/api/secrets', $this->filePayload(null));

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['encrypted_file' => 'The encrypted file is required.']);
        $this->assertDatabaseCount('secrets', 0);
    }

    /** Vérifie qu'un encrypted_file envoyé en chaîne est refusé. */
    public function testRejectsEncryptedFileSentAsString(): void
    {
        Storage::fake('secrets');

        $response = $this->postJson('/api/secrets', $this->filePayload('not-a-file'));

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['encrypted_file' => 'The encrypted file field must be a file.']);
        Storage::disk('secrets')->assertDirectoryEmpty('/');
    }

    /** Vérifie qu'un fichier exactement à la limite de taille est accepté. */
    public function testAcceptsFileAtExactSizeLimit(): void
    {
        Storage::fake('secrets');
        $file = UploadedFile::fake()->create('encrypted', self::MAX_FILE_KILOBYTES, 'application/octet-stream');

        $response = $this->postJson('/api/secrets', $this->filePayload($file));

        $response->assertCreated();
        $this->assertDatabaseCount('secrets', 1);
    }

    /** Vérifie qu'un fichier d'un kilo-octet au-delà de la limite est refusé sans rien stocker. */
    public function testRejectsFileOneKilobyteOverSizeLimit(): void
    {
        Storage::fake('secrets');
        $file = UploadedFile::fake()->create('encrypted', self::MAX_FILE_KILOBYTES + 1, 'application/octet-stream');

        $response = $this->postJson('/api/secrets', $this->filePayload($file));

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['encrypted_file' => 'The file must not exceed 10 MB.']);
        $this->assertDatabaseCount('secrets', 0);
        Storage::disk('secrets')->assertDirectoryEmpty('/');
    }

    /**
     * @return array<string, mixed>
     */
    private function filePayload(UploadedFile|string|null $file): array
    {
        $payload = [
            'type' => 'file',
            'cipher_meta' => json_encode([
                'alg' => 'AES-256-GCM',
                'iv' => self::VALID_IV,
                'version' => 1,
            ]),
            'expiration' => '7d',
        ];

        if ($file !== null) {
            $payload['encrypted_file'] = $file;
        }

        return $payload;
    }
}
