<?php

namespace Tests\Feature;

use DOMDocument;
use DOMElement;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    private const VALID_IV = 'YWFhYWFhYWFhYWFh'; // 12 octets

    private const VALID_CIPHERTEXT = 'ZGRkZGRkZGRkZGRkZGRkZGRkZGRkZGRkZGRkZGRkZGQ'; // 32 octets

    private const UNKNOWN_TOKEN = '0123456789abcdef0123456789abcdef';

    private const ONE_MEGABYTE = 1048576;

    // ── Honeypot ────────────────────────────────────────────────────

    /** Vérifie qu'un bot remplissant le honeypot reçoit un faux 201 sans qu'aucun secret ne soit créé. */
    public function testFilledHoneypotReturnsFake201WithoutCreatingSecret(): void
    {
        $response = $this->postJson('/api/secrets', [...$this->textPayload(), 'website' => 'http://spam.example.com']);

        $response->assertCreated();
        $response->assertJsonStructure(['token', 'expire_at']);
        $this->assertDatabaseCount('secrets', 0);
    }

    /** Vérifie qu'un bot remplissant le honeypot avec un fichier ne stocke aucun blob. */
    public function testFilledHoneypotWithFileStoresNoBlob(): void
    {
        Storage::fake('secrets');

        $response = $this->postJson('/api/secrets', [...$this->filePayload(), 'website' => 'http://spam.example.com']);

        $response->assertCreated();
        $this->assertDatabaseCount('secrets', 0);
        Storage::disk('secrets')->assertDirectoryEmpty('/');
    }

    // ── Rate limits ─────────────────────────────────────────────────

    /** Vérifie que la limite quotidienne de l'application renvoie 429 daily_limit_exceeded une fois le seuil configuré atteint. */
    public function testDailyLimitReturns429WithApplicationMessageOnceConfiguredThresholdIsReached(): void
    {
        // Seuil sous celui de throttle.pow:3,1 pour que la limite quotidienne réponde avant la preuve de travail
        config(['secrets.daily_limit_per_ip' => 2]);

        $this->postJson('/api/secrets', $this->textPayload())->assertCreated();
        $this->postJson('/api/secrets', $this->textPayload())->assertCreated();
        $response = $this->postJson('/api/secrets', $this->textPayload());

        $response->assertTooManyRequests();
        $response->assertExactJson([
            'error' => 'daily_limit_exceeded',
            'message' => 'Daily limit reached. Please try again tomorrow.',
        ]);
        $this->assertDatabaseCount('secrets', 2);
    }

    /** Vérifie que la page de consultation est limitée à 30 requêtes par minute. */
    public function testShowPageReturns429AfterThirtyRequestsPerMinute(): void
    {
        for ($attempt = 1; $attempt <= 30; $attempt++) {
            $this->get('/s/'.self::UNKNOWN_TOKEN)->assertOk();
        }

        $response = $this->get('/s/'.self::UNKNOWN_TOKEN);

        $response->assertTooManyRequests();
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function secretApiRoutes(): array
    {
        return [
            'fetch GET /api/secrets/{token}' => ['GET', '/api/secrets/'.self::UNKNOWN_TOKEN],
            'confirmation POST /api/secrets/{token}/read' => ['POST', '/api/secrets/'.self::UNKNOWN_TOKEN.'/read'],
        ];
    }

    /** Vérifie que les routes API de lecture sont limitées à 20 requêtes par minute. */
    #[DataProvider('secretApiRoutes')]
    public function testSecretApiReturns429AfterTwentyRequestsPerMinute(string $method, string $uri): void
    {
        for ($attempt = 1; $attempt <= 20; $attempt++) {
            $this->json($method, $uri)->assertNotFound();
        }

        $response = $this->json($method, $uri);

        $response->assertTooManyRequests();
    }

    /** Vérifie que les consultations de la page d'un secret n'entament pas la limite des routes API de lecture. */
    public function testSecretPageRequestsDoNotConsumeSecretApiLimit(): void
    {
        for ($attempt = 1; $attempt <= 20; $attempt++) {
            $this->get('/s/'.self::UNKNOWN_TOKEN)->assertOk();
        }

        $response = $this->getJson('/api/secrets/'.self::UNKNOWN_TOKEN);

        $response->assertNotFound();
    }

    /** Vérifie que les consultations de la page d'un secret n'entament pas la limite de vérification du magic link admin. */
    public function testSecretPageRequestsDoNotConsumeAdminVerifyLimit(): void
    {
        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->get('/s/'.self::UNKNOWN_TOKEN)->assertOk();
        }

        $response = $this->get('/fr/admin/verify/'.self::UNKNOWN_TOKEN);

        $response->assertViewIs('admin.invalid-link');
    }

    /** Vérifie que les tentatives de vérification admin n'entament pas la limite de vérification superadmin. */
    public function testAdminVerifyAttemptsDoNotConsumeSuperAdminVerifyLimit(): void
    {
        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->get('/fr/admin/verify/'.self::UNKNOWN_TOKEN)->assertViewIs('admin.invalid-link');
        }

        $response = $this->get('/fr/superadmin/verify/'.self::UNKNOWN_TOKEN);

        $response->assertViewIs('superadmin.invalid-link');
    }

    // ── File Storage Quota ──────────────────────────────────────────

    /** Vérifie qu'un upload est refusé en 503 avec le message traduit dès que le stockage atteint exactement le quota. */
    public function testFileUploadReturns503WhenStoredSizeReachesQuota(): void
    {
        Storage::fake('secrets');
        config(['secrets.file_storage_quota_mb' => 1]);
        Storage::disk('secrets')->put('zz/existing', str_repeat('x', self::ONE_MEGABYTE));

        $response = $this->postJson('/api/secrets', $this->filePayload());

        $response->assertServiceUnavailable();
        $response->assertExactJson([
            'error' => 'service_unavailable',
            'message' => 'File sharing service is temporarily unavailable. Please try again later.',
        ]);
        $this->assertDatabaseCount('secrets', 0);
        $this->assertSame(['zz/existing'], Storage::disk('secrets')->allFiles());
    }

    /** Vérifie qu'un secret texte reste créé quand le quota de fichiers est atteint. */
    public function testTextSecretIsCreatedWhenFileQuotaIsReached(): void
    {
        Storage::fake('secrets');
        config(['secrets.file_storage_quota_mb' => 1]);
        Storage::disk('secrets')->put('zz/existing', str_repeat('x', self::ONE_MEGABYTE));

        $response = $this->postJson('/api/secrets', $this->textPayload());

        $response->assertCreated();
        $this->assertDatabaseCount('secrets', 1);
    }

    /** Vérifie qu'un quota à 0 laisse passer l'upload quel que soit l'espace occupé. */
    public function testFileUploadIsAcceptedWhenQuotaIsZero(): void
    {
        Storage::fake('secrets');
        config(['secrets.file_storage_quota_mb' => 0]);
        Storage::disk('secrets')->put('zz/existing', str_repeat('x', self::ONE_MEGABYTE));

        $response = $this->postJson('/api/secrets', $this->filePayload());

        $response->assertCreated();
        $this->assertCount(2, Storage::disk('secrets')->allFiles());
    }

    // ── Daily Upload Budget ─────────────────────────────────────────

    /** Vérifie qu'un upload dépassant le budget quotidien de l'IP est refusé en 429 daily_limit_exceeded sans stocker de blob. */
    public function testFileUploadReturns429OnceDailyUploadBudgetOfIpIsExceeded(): void
    {
        Storage::fake('secrets');
        config(['secrets.daily_upload_mb_per_ip' => 1]);

        $this->postJson('/api/secrets', $this->filePayload($this->fakeFileOfKilobytes(600)))->assertCreated();
        $response = $this->postJson('/api/secrets', $this->filePayload($this->fakeFileOfKilobytes(600)));

        $response->assertTooManyRequests();
        $response->assertExactJson([
            'error' => 'daily_limit_exceeded',
            'message' => 'Daily limit reached. Please try again tomorrow.',
        ]);
        $this->assertDatabaseCount('secrets', 1);
        $this->assertCount(1, Storage::disk('secrets')->allFiles());
    }

    /** Vérifie qu'un upload remplissant exactement le budget quotidien est accepté. */
    public function testFileUploadFillingExactlyTheDailyUploadBudgetIsAccepted(): void
    {
        Storage::fake('secrets');
        config(['secrets.daily_upload_mb_per_ip' => 1]);

        $response = $this->postJson('/api/secrets', $this->filePayload($this->fakeFileOfKilobytes(1024)));

        $response->assertCreated();
    }

    /** Vérifie qu'une IP ayant épuisé son budget n'empêche pas une autre IP d'envoyer un fichier. */
    public function testExhaustedUploadBudgetOfOneIpDoesNotBlockAnotherIp(): void
    {
        Storage::fake('secrets');
        config(['secrets.daily_upload_mb_per_ip' => 1]);

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.10'])
            ->postJson('/api/secrets', $this->filePayload($this->fakeFileOfKilobytes(1024)))
            ->assertCreated();
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.10'])
            ->postJson('/api/secrets', $this->filePayload())
            ->assertTooManyRequests();

        $response = $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.20'])
            ->postJson('/api/secrets', $this->filePayload($this->fakeFileOfKilobytes(1024)));

        $response->assertCreated();
    }

    /** Vérifie qu'un upload refusé par le budget n'en consomme pas. */
    public function testRejectedUploadDoesNotConsumeBudget(): void
    {
        Storage::fake('secrets');
        config(['secrets.daily_upload_mb_per_ip' => 1]);

        $this->postJson('/api/secrets', $this->filePayload($this->fakeFileOfKilobytes(2048)))->assertTooManyRequests();
        $this->postJson('/api/secrets', $this->filePayload($this->fakeFileOfKilobytes(1024)))->assertCreated();
    }

    /** Vérifie que le chiffré d'un secret texte pèse sur le budget quotidien de l'IP, au même titre qu'un fichier. */
    public function testTextCiphertextConsumesTheDailyUploadBudgetOfTheIp(): void
    {
        Storage::fake('secrets');
        config(['secrets.daily_upload_mb_per_ip' => 1]);

        // Le mégaoctet suffirait au fichier seul : c'est le texte qui fait déborder le budget
        $this->postJson('/api/secrets', $this->textPayload(str_repeat('A', 200000)))->assertCreated();

        $response = $this->postJson('/api/secrets', $this->filePayload($this->fakeFileOfKilobytes(1024)));

        $response->assertTooManyRequests();
        $response->assertExactJson([
            'error' => 'daily_limit_exceeded',
            'message' => 'Daily limit reached. Please try again tomorrow.',
        ]);
        $this->assertDatabaseCount('secrets', 1);
        Storage::disk('secrets')->assertDirectoryEmpty('/');
    }

    /** Vérifie qu'un secret texte est refusé une fois le budget quotidien consommé par un fichier. */
    public function testTextSecretIsRejectedOnceTheDailyUploadBudgetIsExhaustedByAFile(): void
    {
        Storage::fake('secrets');
        config(['secrets.daily_upload_mb_per_ip' => 1]);

        $this->postJson('/api/secrets', $this->filePayload($this->fakeFileOfKilobytes(1024)))->assertCreated();

        $response = $this->postJson('/api/secrets', $this->textPayload());

        $response->assertTooManyRequests();
        $this->assertDatabaseCount('secrets', 1);
    }

    /** Vérifie qu'un budget à 0 laisse passer les secrets texte, même après un fichier qui aurait épuisé le budget. */
    public function testTextSecretIsAcceptedWhenDailyUploadBudgetIsZero(): void
    {
        Storage::fake('secrets');
        config(['secrets.daily_upload_mb_per_ip' => 0]);

        $this->postJson('/api/secrets', $this->filePayload($this->fakeFileOfKilobytes(1024)))->assertCreated();

        $this->postJson('/api/secrets', $this->textPayload(str_repeat('A', 200000)))->assertCreated();

        $this->assertDatabaseCount('secrets', 2);
    }

    /** Vérifie qu'un budget à 0 ne limite pas le volume envoyé. */
    public function testFileUploadIsAcceptedWhenDailyUploadBudgetIsZero(): void
    {
        Storage::fake('secrets');
        config(['secrets.daily_upload_mb_per_ip' => 0]);

        $this->postJson('/api/secrets', $this->filePayload($this->fakeFileOfKilobytes(2048)))->assertCreated();
    }

    /** Vérifie qu'un honeypot rempli ne consomme pas le budget d'upload de l'IP. */
    public function testFilledHoneypotDoesNotConsumeUploadBudget(): void
    {
        Storage::fake('secrets');
        config(['secrets.daily_upload_mb_per_ip' => 1]);

        $this->postJson('/api/secrets', [
            ...$this->filePayload($this->fakeFileOfKilobytes(1024)),
            'website' => 'http://spam.example.com',
        ])->assertCreated();

        $this->postJson('/api/secrets', $this->filePayload($this->fakeFileOfKilobytes(1024)))->assertCreated();
        $this->assertDatabaseCount('secrets', 1);
    }

    // ── CSP ─────────────────────────────────────────────────────────

    /** Vérifie que le script de résolution du PoW porte le nonce de la CSP, sans quoi la connexion admin serait bloquée en production. */
    public function testPowScriptOnAdminLoginCarriesTheCspNonce(): void
    {
        $response = $this->withSession([
            'pow_required' => true,
            'pow_token' => str_repeat('a', 32),
            'pow_challenge' => str_repeat('b', 32),
            'pow_difficulty' => 8,
        ])->get('/fr/admin');

        $response->assertOk();

        preg_match("/'nonce-([^']+)'/", (string) $response->headers->get('Content-Security-Policy'), $csp);
        $this->assertNotEmpty($csp[1] ?? null);

        $document = new DOMDocument();
        @$document->loadHTML((string) $response->getContent());

        $powScripts = collect(iterator_to_array($document->getElementsByTagName('script')))
            ->filter(fn (DOMElement $script): bool => str_contains($script->textContent, 'solvePow'));

        $this->assertCount(1, $powScripts);
        $this->assertSame($csp[1], $powScripts->first()->getAttribute('nonce'));
    }

    // ── CORS ────────────────────────────────────────────────────────

    /** Vérifie qu'un preflight depuis une origine étrangère ne reçoit que l'origine de l'application. */
    public function testCorsPreflightFromForeignOriginOnlyAdvertisesApplicationOrigin(): void
    {
        $appUrl = config()->string('app.url');

        $response = $this->options('/api/secrets', [], [
            'HTTP_ORIGIN' => 'https://evil.example.com',
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'POST',
        ]);

        $response->assertHeader('Access-Control-Allow-Origin', $appUrl);
    }

    /** Vérifie qu'un preflight depuis l'origine de l'application est autorisé pour GET et POST. */
    public function testCorsPreflightFromApplicationOriginIsAllowed(): void
    {
        $appUrl = config()->string('app.url');

        $response = $this->options('/api/secrets', [], [
            'HTTP_ORIGIN' => $appUrl,
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'POST',
        ]);

        $response->assertHeader('Access-Control-Allow-Origin', $appUrl);
        $response->assertHeader('Access-Control-Allow-Methods', 'GET, POST');
    }

    /**
     * @return array<string, mixed>
     */
    private function textPayload(?string $ciphertext = null): array
    {
        return [
            'type' => 'text',
            'ciphertext' => $ciphertext ?? self::VALID_CIPHERTEXT,
            'cipher_meta' => [
                'alg' => 'AES-256-GCM',
                'iv' => self::VALID_IV,
                'version' => 1,
            ],
            'expiration' => '7d',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function filePayload(?UploadedFile $file = null): array
    {
        return [
            'type' => 'file',
            'encrypted_file' => $file ?? UploadedFile::fake()->createWithContent('encrypted', 'encrypted-bytes'),
            'cipher_meta' => json_encode([
                'alg' => 'AES-256-GCM',
                'iv' => self::VALID_IV,
                'version' => 1,
            ]),
            'expiration' => '7d',
        ];
    }

    private function fakeFileOfKilobytes(int $kilobytes): UploadedFile
    {
        return UploadedFile::fake()->create('encrypted', $kilobytes, 'application/octet-stream');
    }
}
