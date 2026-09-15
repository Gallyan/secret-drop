<?php

namespace Tests\Feature;

use App\Models\Secret;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CipherValidationTest extends TestCase
{
    private const VALID_IV = 'YWFhYWFhYWFhYWFh'; // 12 octets

    private const VALID_SALT = 'YmJiYmJiYmJiYmJiYmJiYg'; // 16 octets

    private const VALID_IV2 = 'Y2NjY2NjY2NjY2Nj'; // 12 octets

    private const VALID_CIPHERTEXT = 'ZGRkZGRkZGRkZGRkZGRkZGRkZGRkZGRkZGRkZGRkZGQ'; // 32 octets

    private const BAD_IV = 'ZmZmZmZmZmZmZg'; // 10 octets au lieu de 12

    private const BAD_SALT = 'Z2dnZ2dnZ2dnZ2dn'; // 12 octets au lieu de 16

    private const SHORT_CIPHERTEXT = 'ZWVlZWVlZWVlZWVl'; // 12 octets, sous les 16 du tag GCM

    /** Vérifie le rejet d'un IV dont la taille décodée n'est pas 12 octets. */
    public function testRejectsIvWithWrongByteLength(): void
    {
        $response = $this->postJson('/api/secrets', $this->validPayload([
            'cipher_meta' => ['iv' => self::BAD_IV],
        ]));

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['cipher_meta.iv' => 'Invalid byte length: expected 12 bytes, got 10.']);
        $this->assertDatabaseCount('secrets', 0);
    }

    /** Vérifie le rejet d'un salt dont la taille décodée n'est pas 16 octets. */
    public function testRejectsSaltWithWrongByteLength(): void
    {
        $response = $this->postJson('/api/secrets', $this->validPayload([
            'cipher_meta' => ['salt' => self::BAD_SALT, 'iv2' => self::VALID_IV2],
        ]));

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['cipher_meta.salt' => 'Invalid byte length: expected 16 bytes, got 12.']);
    }

    /** Vérifie le rejet d'un iv2 dont la taille décodée n'est pas 12 octets. */
    public function testRejectsIv2WithWrongByteLength(): void
    {
        $response = $this->postJson('/api/secrets', $this->validPayload([
            'cipher_meta' => ['salt' => self::VALID_SALT, 'iv2' => self::BAD_IV],
        ]));

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['cipher_meta.iv2' => 'Invalid byte length: expected 12 bytes, got 10.']);
    }

    /** Vérifie le rejet d'un chiffré plus court que le tag GCM de 16 octets. */
    public function testRejectsCiphertextShorterThanGcmTag(): void
    {
        $response = $this->postJson('/api/secrets', $this->validPayload([
            'ciphertext' => self::SHORT_CIPHERTEXT,
        ]));

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['ciphertext' => 'Minimum byte length is 16, got 12.']);
    }

    /** Vérifie que salt sans iv2 est refusé. */
    public function testRejectsSaltWithoutIv2(): void
    {
        $response = $this->postJson('/api/secrets', $this->validPayload([
            'cipher_meta' => ['salt' => self::VALID_SALT],
        ]));

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['cipher_meta.salt' => 'Salt and IV2 must both be present or both absent.']);
    }

    /** Vérifie que iv2 sans salt est refusé. */
    public function testRejectsIv2WithoutSalt(): void
    {
        $response = $this->postJson('/api/secrets', $this->validPayload([
            'cipher_meta' => ['iv2' => self::VALID_IV2],
        ]));

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['cipher_meta.salt' => 'Salt and IV2 must both be present or both absent.']);
    }

    /** Vérifie que has_passphrase=true sans salt ni iv2 est refusé. */
    public function testRejectsPassphraseFlagWithoutSaltAndIv2(): void
    {
        $response = $this->postJson('/api/secrets', $this->validPayload([
            'cipher_meta' => ['has_passphrase' => true],
        ]));

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['cipher_meta.has_passphrase' => 'Passphrase flag requires salt and IV2 to be present.']);
    }

    /** Vérifie que salt et iv2 sans has_passphrase sont acceptés et persistés tels quels. */
    public function testAcceptsSaltAndIv2WithoutPassphraseFlagAndPersistsThem(): void
    {
        $cipherMeta = [
            'alg' => 'AES-256-GCM',
            'iv' => self::VALID_IV,
            'version' => 1,
            'salt' => self::VALID_SALT,
            'iv2' => self::VALID_IV2,
            'kdf' => 'PBKDF2-SHA256-600k',
        ];

        $response = $this->postJson('/api/secrets', $this->validPayload(['cipher_meta' => $cipherMeta]));

        $response->assertCreated();

        $secret = Secret::where('token', $response->json('token'))->firstOrFail();
        $this->assertSame($cipherMeta, $secret->cipher_meta);
    }

    /** Vérifie que seul l'algorithme AES-256-GCM est accepté. */
    public function testRejectsUnsupportedAlgorithm(): void
    {
        $response = $this->postJson('/api/secrets', $this->validPayload([
            'cipher_meta' => ['alg' => 'AES-128-CBC'],
        ]));

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['cipher_meta.alg' => 'The selected cipher meta.alg is invalid.']);
        $this->assertDatabaseCount('secrets', 0);
    }

    /** Vérifie que la version de chiffrement doit être un entier. */
    public function testRejectsNonIntegerVersion(): void
    {
        $response = $this->postJson('/api/secrets', $this->validPayload([
            'cipher_meta' => ['version' => 'v1'],
        ]));

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['cipher_meta.version' => 'The cipher meta.version field must be an integer.']);
    }

    /** Vérifie que la version de chiffrement doit valoir au moins 1. */
    public function testRejectsVersionBelow1(): void
    {
        $response = $this->postJson('/api/secrets', $this->validPayload([
            'cipher_meta' => ['version' => 0],
        ]));

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['cipher_meta.version' => 'The cipher meta.version field must be at least 1.']);
    }

    /** Vérifie que le kdf doit être une chaîne. */
    public function testRejectsNonStringKdf(): void
    {
        $response = $this->postJson('/api/secrets', $this->validPayload([
            'cipher_meta' => ['salt' => self::VALID_SALT, 'iv2' => self::VALID_IV2, 'kdf' => 600000],
        ]));

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['cipher_meta.kdf' => 'The cipher meta.kdf field must be a string.']);
    }

    /** Vérifie que cipher_meta envoyé en chaîne JSON invalide (FormData) est traité comme absent. */
    public function testRejectsCipherMetaSentAsInvalidJsonString(): void
    {
        $response = $this->postJson('/api/secrets', $this->validPayload([
            'cipher_meta' => '{not json',
        ]));

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['cipher_meta' => 'Encryption metadata is required.']);
    }

    /** Vérifie que cipher_meta envoyé en chaîne JSON valide (FormData) est décodé et persisté. */
    public function testAcceptsCipherMetaSentAsJsonString(): void
    {
        $response = $this->postJson('/api/secrets', $this->validPayload([
            'cipher_meta' => '{"alg":"AES-256-GCM","iv":"'.self::VALID_IV.'","version":1}',
        ]));

        $response->assertCreated();

        $secret = Secret::where('token', $response->json('token'))->firstOrFail();
        $this->assertSame(['alg' => 'AES-256-GCM', 'iv' => self::VALID_IV, 'version' => 1], $secret->cipher_meta);
    }

    /** Vérifie qu'un cipher_meta scalaire est refusé avec le message de la règle array. */
    public function testRejectsNonArrayCipherMetaWithArrayMessage(): void
    {
        $response = $this->postJson('/api/secrets', $this->validPayload([
            'cipher_meta' => 5,
        ]));

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['cipher_meta' => 'The cipher meta field must be an array.']);
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function requiredCipherMetaFields(): array
    {
        return [
            'algorithme absent' => ['alg', 'The encryption algorithm is required.'],
            'IV absent' => ['iv', 'The initialization vector is required.'],
            'version absente' => ['version', 'The encryption version is required.'],
        ];
    }

    /** Vérifie qu'un champ obligatoire de cipher_meta absent est refusé avec son message traduit. */
    #[DataProvider('requiredCipherMetaFields')]
    public function testRejectsMissingRequiredCipherMetaField(string $field, string $expectedMessage): void
    {
        $payload = $this->validPayload();
        unset($payload['cipher_meta'][$field]);

        $response = $this->postJson('/api/secrets', $payload);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(["cipher_meta.{$field}" => $expectedMessage]);
        $this->assertDatabaseCount('secrets', 0);
    }

    /** Vérifie que has_passphrase doit être un booléen. */
    public function testRejectsNonBooleanPassphraseFlag(): void
    {
        $response = $this->postJson('/api/secrets', $this->validPayload([
            'cipher_meta' => ['salt' => self::VALID_SALT, 'iv2' => self::VALID_IV2, 'has_passphrase' => 'x'],
        ]));

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors([
            'cipher_meta.has_passphrase' => 'The cipher meta.has passphrase field must be true or false.',
        ]);
    }

    /**
     * @return array<string, array{0: string, 1: array<string, mixed>, 2: string}>
     */
    public static function nonStringPassphraseMaterial(): array
    {
        return [
            'salt entier' => [
                'salt',
                ['salt' => 123, 'iv2' => self::VALID_IV2],
                'The cipher meta.salt field must be a string.',
            ],
            'iv2 entier' => [
                'iv2',
                ['salt' => self::VALID_SALT, 'iv2' => 123],
                'The cipher meta.iv2 field must be a string.',
            ],
        ];
    }

    /**
     * Vérifie que salt et iv2 doivent être des chaînes.
     *
     * @param  array<string, mixed>  $cipherMeta
     */
    #[DataProvider('nonStringPassphraseMaterial')]
    public function testRejectsNonStringPassphraseMaterial(string $field, array $cipherMeta, string $expectedMessage): void
    {
        $response = $this->postJson('/api/secrets', $this->validPayload(['cipher_meta' => $cipherMeta]));

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(["cipher_meta.{$field}" => $expectedMessage]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        $data = [
            'type' => 'text',
            'ciphertext' => self::VALID_CIPHERTEXT,
            'cipher_meta' => [
                'alg' => 'AES-256-GCM',
                'iv' => self::VALID_IV,
                'version' => 1,
            ],
            'expiration' => '7d',
        ];

        if (array_key_exists('cipher_meta', $overrides) && ! is_array($overrides['cipher_meta'])) {
            $data['cipher_meta'] = $overrides['cipher_meta'];
            unset($overrides['cipher_meta']);
        }

        return array_replace_recursive($data, $overrides);
    }
}
