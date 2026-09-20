<?php

namespace Tests\Unit;

use App\Models\Secret;
use App\Services\SecretStorageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SecretStorageServiceTest extends TestCase
{
    private const TOKEN = 'ab34567890abcdef1234567890abcdef';

    private const SIBLING_TOKEN = 'abfedcba0987654321fedcba09876543';

    private SecretStorageService $storage;

    protected function setUp(): void
    {
        parent::setUp();
        $this->storage = app(SecretStorageService::class);
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function sizeCacheKeys(): array
    {
        return [
            'taille totale utilisée pour le quota' => ['secrets:total_file_size'],
            'usage disque affiché au superadmin' => ['disk_usage_secrets'],
        ];
    }

    /**
     * @return array<string, array{0: int, 1: bool}>
     */
    public static function quotaBoundaries(): array
    {
        return [
            'un octet sous le quota' => [1048575, false],
            'quota atteint exactement' => [1048576, true],
        ];
    }

    /**
     * @return array<string, array{0: int, 1: int, 2: bool}>
     */
    public static function mixedQuotaBoundaries(): array
    {
        return [
            'texte seul sous le quota' => [0, 1048575, false],
            'texte seul atteignant le quota' => [0, 1048576, true],
            'blob et texte cumulés sous le quota' => [1048000, 575, false],
            'blob et texte cumulés atteignant le quota' => [1048000, 576, true],
        ];
    }

    /** Vérifie que store écrit le contenu envoyé dans le sous-répertoire des deux premiers caractères du token. */
    public function testStoreWritesUploadedContentUnderTokenPrefixDirectory(): void
    {
        Storage::fake('secrets');
        $file = UploadedFile::fake()->createWithContent('test.bin', 'encrypted-bytes');

        $path = $this->storage->store(self::TOKEN, $file);

        $this->assertSame('ab/ab34567890abcdef1234567890abcdef', $path);
        $this->assertTrue($this->storage->exists($path));
        $this->assertSame('encrypted-bytes', $this->storage->disk()->get($path));
    }

    /** Vérifie que exists retourne false pour un fichier absent du disque. */
    public function testExistsReturnsFalseForMissingFile(): void
    {
        Storage::fake('secrets');

        $this->assertFalse($this->storage->exists('ab/missing'));
    }

    /** Vérifie que size retourne la taille du fichier en octets. */
    public function testSizeReturnsFileSizeInBytes(): void
    {
        Storage::fake('secrets');
        $this->storage->disk()->put('ab/blob', str_repeat('x', 1024));

        $this->assertSame(1024, $this->storage->size('ab/blob'));
    }

    /** Vérifie que delete supprime le fichier sans élaguer son répertoire de partition. */
    public function testDeleteRemovesFileButKeepsPartitionDirectory(): void
    {
        Storage::fake('secrets');
        $path = $this->storage->store(self::TOKEN, UploadedFile::fake()->createWithContent('test.bin', 'blob'));

        $result = $this->storage->delete($path);

        $this->assertTrue($result);
        $this->assertFalse($this->storage->exists($path));
        $this->assertTrue($this->storage->disk()->directoryExists('ab'));
    }

    /** Vérifie que delete conserve le répertoire quand il contient encore un autre fichier. */
    public function testDeleteKeepsDirectoryThatStillHoldsAnotherFile(): void
    {
        Storage::fake('secrets');
        $path = $this->storage->store(self::TOKEN, UploadedFile::fake()->createWithContent('a.bin', 'blob'));
        $siblingPath = $this->storage->store(self::SIBLING_TOKEN, UploadedFile::fake()->createWithContent('b.bin', 'sibling'));

        $this->storage->delete($path);

        $this->assertFalse($this->storage->exists($path));
        $this->assertSame('sibling', $this->storage->disk()->get($siblingPath));
        $this->assertTrue($this->storage->disk()->exists('ab'));
    }

    /** Vérifie que delete retourne false pour un fichier absent du disque. */
    public function testDeleteReturnsFalseForMissingFile(): void
    {
        Storage::fake('secrets');

        $this->assertFalse($this->storage->delete('ab/missing'));
    }

    /** Vérifie que store invalide les tailles mises en cache. */
    #[DataProvider('sizeCacheKeys')]
    public function testStoreInvalidatesCachedSize(string $cacheKey): void
    {
        Storage::fake('secrets');
        Cache::put($cacheKey, 123, 3600);

        $this->storage->store(self::TOKEN, UploadedFile::fake()->createWithContent('test.bin', 'blob'));

        $this->assertFalse(Cache::has($cacheKey));
    }

    /** Vérifie que delete invalide les tailles mises en cache. */
    #[DataProvider('sizeCacheKeys')]
    public function testDeleteInvalidatesCachedSize(string $cacheKey): void
    {
        Storage::fake('secrets');
        $this->storage->disk()->put('ab/blob', 'blob');
        Cache::put($cacheKey, 123, 3600);

        $this->storage->delete('ab/blob');

        $this->assertFalse(Cache::has($cacheKey));
    }

    /** Vérifie que totalSize additionne les fichiers du disque puis sert la valeur mise en cache. */
    public function testTotalSizeSumsFilesThenServesCachedValue(): void
    {
        Storage::fake('secrets');
        $this->storage->disk()->put('ab/first', str_repeat('x', 100));
        $this->storage->disk()->put('cd/second', str_repeat('x', 50));

        $firstTotal = $this->storage->totalSize();
        $this->storage->disk()->put('ef/third', str_repeat('x', 25));
        $secondTotal = $this->storage->totalSize();

        $this->assertSame(150, $firstTotal);
        $this->assertSame(150, $secondTotal);
    }

    /** Vérifie que le quota est considéré dépassé dès que la taille totale l'atteint. */
    #[DataProvider('quotaBoundaries')]
    public function testQuotaIsExceededFromTheExactQuotaSize(int $storedBytes, bool $expected): void
    {
        Storage::fake('secrets');
        Config::set('secrets.file_storage_quota_mb', 1);
        $this->storage->disk()->put('ab/blob', str_repeat('x', $storedBytes));

        $this->assertSame($expected, $this->storage->isQuotaExceeded());
    }

    /** Vérifie que totalStoredBytes additionne les blobs du disque et les chiffrés texte de la base. */
    public function testTotalStoredBytesSumsDiskBlobsAndTextCiphertexts(): void
    {
        Storage::fake('secrets');
        $this->storage->disk()->put('ab/first', str_repeat('x', 100));
        Secret::factory()->create(['ciphertext' => str_repeat('a', 40)]);
        Secret::factory()->create(['ciphertext' => str_repeat('a', 10)]);
        Secret::factory()->file()->create();

        $this->assertSame(50, $this->storage->totalTextSize());
        $this->assertSame(150, $this->storage->totalStoredBytes());
    }

    /** Vérifie que le total des chiffrés texte est servi depuis le cache après le premier calcul. */
    public function testTotalTextSizeServesCachedValue(): void
    {
        Secret::factory()->create(['ciphertext' => str_repeat('a', 30)]);

        $firstTotal = $this->storage->totalTextSize();
        Secret::factory()->create(['ciphertext' => str_repeat('a', 70)]);
        $secondTotal = $this->storage->totalTextSize();

        $this->assertSame(30, $firstTotal);
        $this->assertSame(30, $secondTotal);
    }

    /** Vérifie que le quota est dépassé aussi bien par les chiffrés texte que par le cumul texte + blobs. */
    #[DataProvider('mixedQuotaBoundaries')]
    public function testQuotaCountsBlobsAndTextCiphertextsTogether(int $blobBytes, int $textBytes, bool $expected): void
    {
        Storage::fake('secrets');
        Config::set('secrets.file_storage_quota_mb', 1);

        if ($blobBytes > 0) {
            $this->storage->disk()->put('ab/blob', str_repeat('x', $blobBytes));
        }

        Secret::factory()->create(['ciphertext' => str_repeat('a', $textBytes)]);

        $this->assertSame($expected, $this->storage->isQuotaExceeded());
    }

    /** Vérifie que usageRatio rapporte le total stocké au quota converti en octets. */
    public function testUsageRatioIsTheShareOfTheQuotaAlreadyUsed(): void
    {
        Storage::fake('secrets');
        Config::set('secrets.file_storage_quota_mb', 1);
        $this->storage->disk()->put('ab/blob', str_repeat('x', 262144));
        Secret::factory()->create(['ciphertext' => str_repeat('a', 262144)]);

        $this->assertSame(1048576, $this->storage->quotaBytes());
        $this->assertSame(0.5, $this->storage->usageRatio());
    }

    /** Vérifie qu'un quota à 0 vaut illimité : aucun octet de quota, aucun ratio, aucun dépassement. */
    public function testZeroQuotaMeansUnlimited(): void
    {
        Storage::fake('secrets');
        Config::set('secrets.file_storage_quota_mb', 0);
        $this->storage->disk()->put('ab/blob', str_repeat('x', 2048));
        Secret::factory()->create(['ciphertext' => str_repeat('a', 2048)]);

        $this->assertSame(0, $this->storage->quotaBytes());
        $this->assertSame(0.0, $this->storage->usageRatio());
        $this->assertFalse($this->storage->isQuotaExceeded());
    }

    /** Vérifie que download renvoie le contenu chiffré avec les en-têtes de téléchargement sécurisés. */
    public function testDownloadStreamsEncryptedContentWithSecurityHeaders(): void
    {
        Storage::fake('secrets');
        $this->storage->disk()->put('ab/blob', 'encrypted-bytes');

        $response = TestResponse::fromBaseResponse($this->storage->download('ab/blob'));

        $response->assertHeader('Content-Type', 'application/octet-stream');
        $response->assertHeader('Content-Disposition', 'attachment; filename="encrypted"');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('Cache-Control', 'max-age=0, must-revalidate, no-cache, no-store, private');
        $response->assertHeader('Pragma', 'no-cache');
        $response->assertHeader('X-Download-Options', 'noopen');
        $this->assertSame('encrypted-bytes', $response->streamedContent());
    }

    /** Vérifie que readStream donne accès au contenu du fichier. */
    public function testReadStreamReturnsFileContents(): void
    {
        Storage::fake('secrets');
        $this->storage->disk()->put('ab/blob', 'encrypted-bytes');

        $stream = $this->storage->readStream('ab/blob');

        $this->assertIsResource($stream);
        $this->assertSame('encrypted-bytes', stream_get_contents($stream));
        fclose($stream);
    }
}
