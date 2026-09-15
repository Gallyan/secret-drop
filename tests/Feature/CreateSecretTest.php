<?php

namespace Tests\Feature;

use App\Enums\SecretType;
use App\Http\Requests\StoreSecretRequest;
use App\Models\Secret;
use App\Services\StatsService;
use App\Support\LocaleConfig;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CreateSecretTest extends TestCase
{
    private const VALID_IV = 'YWFhYWFhYWFhYWFh'; // 12 octets

    private const VALID_SALT = 'YmJiYmJiYmJiYmJiYmJiYg'; // 16 octets

    private const VALID_IV2 = 'Y2NjY2NjY2NjY2Nj'; // 12 octets

    private const VALID_CIPHERTEXT = 'ZGRkZGRkZGRkZGRkZGRkZGRkZGRkZGRkZGRkZGRkZGQ'; // 32 octets

    /** Vérifie que la page de création s'affiche. */
    public function testCreatePageRendersApplicationName(): void
    {
        $response = $this->get('/fr');

        $response->assertOk();
        $response->assertSee('Secret Drop');
    }

    /** Vérifie qu'un secret texte valide est créé en 201 avec le chiffré et les métadonnées attendus, sans renvoyer d'admin token. */
    public function testCreatesTextSecretAndReturns201(): void
    {
        $response = $this->postJson('/api/secrets', $this->validPayload(['max_views' => 1]));

        $response->assertCreated();
        $response->assertJsonMissingPath('admin_token');
        $token = $response->json('token');
        $this->assertIsString($token);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $token);

        $secret = Secret::where('token', $token)->firstOrFail();
        $this->assertSame(SecretType::Text, $secret->type);
        $this->assertSame(self::VALID_CIPHERTEXT, $secret->ciphertext);
        $this->assertSame(['alg' => 'AES-256-GCM', 'iv' => self::VALID_IV, 'version' => 1], $secret->cipher_meta);
        $this->assertSame(1, $secret->max_views);
        $this->assertSame(0, $secret->read_count);
        $this->assertNull($secret->file_path);
        $this->assertNull($secret->creator_email_hash);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $secret->admin_token_hash);
        $this->assertNotSame($token, $secret->admin_token_hash);
    }

    /** Vérifie qu'un secret avec passphrase, email et mode séparé persiste ces options et incrémente leurs stats. */
    public function testCreatesSecretWithPassphraseEmailAndSplitModeAndTracksStats(): void
    {
        $cipherMeta = [
            'alg' => 'AES-256-GCM',
            'iv' => self::VALID_IV,
            'version' => 1,
            'salt' => self::VALID_SALT,
            'iv2' => self::VALID_IV2,
            'kdf' => 'PBKDF2-SHA256-600k',
            'has_passphrase' => true,
        ];

        $response = $this->postJson('/api/secrets', $this->validPayload([
            'cipher_meta' => $cipherMeta,
            'max_views' => 5,
            'creator_email' => 'creator@example.com',
            'split_mode' => true,
        ]));

        $response->assertCreated();

        $secret = Secret::where('token', $response->json('token'))->firstOrFail();
        $this->assertSame($cipherMeta, $secret->cipher_meta);
        $this->assertSame(5, $secret->max_views);
        $this->assertTrue($secret->verifyCreatorEmail('creator@example.com'));
        $this->assertTrue($secret->verifyCreatorEmail('CREATOR@EXAMPLE.COM'));

        $this->assertStatCount(StatsService::SECRETS_WITH_PASSPHRASE, 1);
        $this->assertStatCount(StatsService::SECRETS_SPLIT_MODE, 1);
        $this->assertStatCount(StatsService::SECRETS_WITH_MAX_VIEWS, 1);
    }

    /** Vérifie qu'un secret sans passphrase ni mode séparé n'incrémente pas ces stats. */
    public function testPlainSecretDoesNotTrackPassphraseOrSplitModeStats(): void
    {
        $response = $this->postJson('/api/secrets', $this->validPayload());

        $response->assertCreated();
        $this->assertDatabaseMissing('stats_daily', ['metric' => StatsService::SECRETS_WITH_PASSPHRASE]);
        $this->assertDatabaseMissing('stats_daily', ['metric' => StatsService::SECRETS_SPLIT_MODE]);
    }

    /**
     * @return array<string, array{0: string, 1: bool}>
     */
    public static function splitModeFormValues(): array
    {
        return [
            '"true"' => ['true', true],
            '"on"' => ['on', true],
            '"false"' => ['false', false],
            '"off"' => ['off', false],
        ];
    }

    /** Vérifie que split_mode envoyé en chaîne (FormData) est converti en booléen avant validation. */
    #[DataProvider('splitModeFormValues')]
    public function testSplitModeStringFromFormDataIsConvertedToBoolean(string $value, bool $expectedSplitMode): void
    {
        $response = $this->postJson('/api/secrets', $this->validPayload(['split_mode' => $value]));

        $response->assertCreated();
        $this->assertSame($expectedSplitMode, $this->statCount(StatsService::SECRETS_SPLIT_MODE) === 1);
    }

    /** Vérifie qu'un split_mode non booléen est refusé avec le message de validation. */
    public function testRejectsNonBooleanSplitMode(): void
    {
        $response = $this->postJson('/api/secrets', $this->validPayload(['split_mode' => 2]));

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['split_mode' => 'The split mode field must be true or false.']);
        $this->assertDatabaseCount('secrets', 0);
    }

    /** Vérifie qu'un creator_email vide est enregistré comme absent. */
    public function testEmptyCreatorEmailIsStoredAsNull(): void
    {
        $response = $this->postJson('/api/secrets', $this->validPayload(['creator_email' => '']));

        $response->assertCreated();

        $secret = Secret::where('token', $response->json('token'))->firstOrFail();
        $this->assertNull($secret->creator_email_hash);
    }

    /** Vérifie que le type est requis. */
    public function testRejectsMissingType(): void
    {
        $payload = $this->validPayload();
        unset($payload['type']);

        $response = $this->postJson('/api/secrets', $payload);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['type' => 'The secret type is required.']);
        $this->assertDatabaseCount('secrets', 0);
    }

    /**
     * @return array<string, array{0: mixed}>
     */
    public static function invalidTypes(): array
    {
        return [
            'type inconnu' => ['archive'],
            'entier proche par coercition' => [0],
        ];
    }

    /** Vérifie le rejet d'un type de secret hors énumération. */
    #[DataProvider('invalidTypes')]
    public function testRejectsTypeOutsideEnum(mixed $type): void
    {
        $response = $this->postJson('/api/secrets', $this->validPayload(['type' => $type]));

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['type' => 'The type must be "text" or "file".']);
    }

    /** Vérifie que le chiffré est requis pour un secret texte. */
    public function testRejectsTextSecretWithoutCiphertext(): void
    {
        $payload = $this->validPayload();
        unset($payload['ciphertext']);

        $response = $this->postJson('/api/secrets', $payload);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['ciphertext' => 'Encrypted text is required for a text secret.']);
    }

    /** Vérifie qu'un chiffré de 70 000 caractères est accepté. */
    public function testAcceptsCiphertextAtMaximumLength(): void
    {
        $ciphertext = str_repeat('A', 70000);

        $response = $this->postJson('/api/secrets', $this->validPayload(['ciphertext' => $ciphertext]));

        $response->assertCreated();
        $this->assertSame($ciphertext, Secret::where('token', $response->json('token'))->value('ciphertext'));
    }

    /** Vérifie qu'un chiffré de 70 001 caractères est refusé avec le message de taille. */
    public function testRejectsCiphertextOverMaximumLength(): void
    {
        $response = $this->postJson('/api/secrets', $this->validPayload(['ciphertext' => str_repeat('A', 70001)]));

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['ciphertext' => 'The text must not exceed 50,000 characters.']);
        $this->assertDatabaseCount('secrets', 0);
    }

    /** Vérifie que cipher_meta est requis. */
    public function testRejectsMissingCipherMeta(): void
    {
        $payload = $this->validPayload();
        unset($payload['cipher_meta']);

        $response = $this->postJson('/api/secrets', $payload);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['cipher_meta' => 'Encryption metadata is required.']);
    }

    /** Vérifie que l'expiration est requise. */
    public function testRejectsMissingExpiration(): void
    {
        $payload = $this->validPayload();
        unset($payload['expiration']);

        $response = $this->postJson('/api/secrets', $payload);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['expiration' => 'The expiration duration is required.']);
        $this->assertDatabaseCount('secrets', 0);
    }

    /** Vérifie le rejet d'une expiration hors configuration. */
    public function testRejectsUnknownExpiration(): void
    {
        $response = $this->postJson('/api/secrets', $this->validPayload(['expiration' => '1y']));

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['expiration' => 'The expiration duration is invalid.']);
        $this->assertDatabaseCount('secrets', 0);
    }

    /** Vérifie le rejet d'un email invalide. */
    public function testRejectsInvalidCreatorEmail(): void
    {
        $response = $this->postJson('/api/secrets', $this->validPayload(['creator_email' => 'not-an-email']));

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['creator_email' => 'The email address is not valid.']);
    }

    /** Vérifie le rejet d'un email de plus de 255 caractères. */
    public function testRejectsCreatorEmailLongerThan255Characters(): void
    {
        $email = str_repeat('a', 64).'@'.str_repeat('b', 63).'.'.str_repeat('c', 63).'.'.str_repeat('d', 59).'.com';

        $response = $this->postJson('/api/secrets', $this->validPayload(['creator_email' => $email]));

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors([
            'creator_email' => 'The email address must not exceed 255 characters.',
        ]);
    }

    /** Vérifie que max_views ne peut pas dépasser 100. */
    public function testRejectsMaxViewsAbove100(): void
    {
        $response = $this->postJson('/api/secrets', $this->validPayload(['max_views' => 101]));

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['max_views' => 'The number of views cannot exceed 100.']);
    }

    /** Vérifie que max_views doit valoir au moins 1. */
    public function testRejectsMaxViewsBelow1(): void
    {
        $response = $this->postJson('/api/secrets', $this->validPayload(['max_views' => 0]));

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['max_views' => 'The number of views must be at least 1.']);
    }

    /** Vérifie que max_views doit être un entier. */
    public function testRejectsNonIntegerMaxViews(): void
    {
        $response = $this->postJson('/api/secrets', $this->validPayload(['max_views' => 2.5]));

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['max_views' => 'The max views field must be an integer.']);
    }

    /**
     * La limite saisie côté client doit garantir que le chiffré passe la règle
     * serveur : base64url coûte 4 caractères pour 3 octets, et chaque couche
     * AES-GCM ajoute 16 octets (deux couches avec passphrase).
     */
    public function testClientTextLimitFitsTheServerCiphertextRule(): void
    {
        $js = (string) file_get_contents(resource_path('js/components/secret-form.js'));
        $this->assertMatchesRegularExpression('/const MAX_TEXT_BYTES = (\d+);/', $js);
        preg_match('/const MAX_TEXT_BYTES = (\d+);/', $js, $matches);
        $maxTextBytes = (int) $matches[1];

        $ciphertextRules = (new StoreSecretRequest())->rules()['ciphertext'];
        $maxRule = collect($ciphertextRules)->first(fn ($rule) => is_string($rule) && str_starts_with($rule, 'max:'));
        $serverMax = (int) str_replace('max:', '', (string) $maxRule);

        $encodedLength = (int) ceil(($maxTextBytes + 32) * 4 / 3);

        $this->assertLessThanOrEqual(
            $serverMax,
            $encodedLength,
            "Un texte de {$maxTextBytes} octets produit {$encodedLength} caractères chiffrés, au-delà de la limite serveur de {$serverMax}."
        );
    }

    /** Vérifie que le message de dépassement existe dans toutes les langues. */
    public function testTextTooLargeMessageIsTranslatedEverywhere(): void
    {
        foreach (LocaleConfig::SUPPORTED_LOCALES as $locale) {
            $this->assertNotSame(
                'messages.text_too_large',
                __('messages.text_too_large', [], $locale),
                "Traduction manquante pour la locale {$locale}."
            );
        }
    }

    private function assertStatCount(string $metric, int $expected): void
    {
        $this->assertSame($expected, $this->statCount($metric), "Compteur {$metric} inattendu.");
    }

    private function statCount(string $metric): int
    {
        return (int) DB::table('stats_daily')->where('metric', $metric)->sum('count');
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return [
            'type' => 'text',
            'ciphertext' => self::VALID_CIPHERTEXT,
            'cipher_meta' => [
                'alg' => 'AES-256-GCM',
                'iv' => self::VALID_IV,
                'version' => 1,
            ],
            'expiration' => '7d',
            ...$overrides,
        ];
    }
}
