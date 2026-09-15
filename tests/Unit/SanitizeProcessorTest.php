<?php

namespace Tests\Unit;

use App\Logging\SanitizeProcessor;
use DateTimeImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Log\Logger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use LogicException;
use Monolog\Handler\TestHandler;
use Monolog\Level;
use Monolog\Logger as MonologLogger;
use Monolog\LogRecord;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;
use Throwable;

class SanitizeProcessorTest extends TestCase
{
    private const SECRET_TOKEN = '0123456789abcdef0123456789abcdef';

    private const MAGIC_LINK_TOKEN = 'fedcba9876543210fedcba9876543210fedcba9876543210fedcba9876543210';

    /**
     * @param  array<array-key, mixed>  $context
     * @param  array<array-key, mixed>  $extra
     */
    private function process(string $message = 'test', array $context = [], array $extra = []): LogRecord
    {
        $record = new LogRecord(
            datetime: new DateTimeImmutable(),
            channel: 'test',
            level: Level::Info,
            message: $message,
            context: $context,
            extra: $extra,
        );

        return (new SanitizeProcessor())($record);
    }

    /** @return array<string, array{string}> */
    public static function sensitiveKeys(): array
    {
        return [
            'password' => ['password'],
            'password_confirmation' => ['password_confirmation'],
            'secret' => ['secret'],
            'token' => ['token'],
            'admin_token' => ['admin_token'],
            'api_key' => ['api_key'],
            'apikey' => ['apikey'],
            'authorization' => ['authorization'],
            'Authorization en casse mixte' => ['Authorization'],
            'credit_card' => ['credit_card'],
            'card_number' => ['card_number'],
            'cvv' => ['cvv'],
            'ssn' => ['ssn'],
            'private_key' => ['private_key'],
            'ciphertext' => ['ciphertext'],
            'cipher_meta' => ['cipher_meta'],
            'passphrase' => ['passphrase'],
            'key' => ['key'],
            'fragment' => ['fragment'],
        ];
    }

    /** Vérifie que la valeur d'une clé de contexte sensible est entièrement masquée, même si c'est un tableau. */
    #[DataProvider('sensitiveKeys')]
    public function testRedactsSensitiveContextKey(string $key): void
    {
        $record = $this->process(context: [
            $key => 'value-to-hide',
            "nested_{$key}" => ['iv' => 'value-to-hide'],
        ]);

        $this->assertSame(
            [$key => '[REDACTED]', "nested_{$key}" => '[REDACTED]'],
            $record->context
        );
    }

    /** Vérifie que les données non sensibles du contexte sont préservées avec leur type. */
    public function testPreservesNonSensitiveContext(): void
    {
        $record = $this->process(context: ['user_id' => 42, 'action' => 'login', 'ratio' => 0.5, 'ok' => true]);

        $this->assertSame(['user_id' => 42, 'action' => 'login', 'ratio' => 0.5, 'ok' => true], $record->context);
    }

    /** Vérifie que la sanitisation descend dans les tableaux imbriqués et les listes. */
    public function testSanitizesNestedArraysRecursively(): void
    {
        $record = $this->process(context: [
            'request' => [
                'headers' => ['authorization' => 'Bearer abc.def'],
                'paths' => ['/s/'.self::SECRET_TOKEN, '/fr/faq'],
            ],
        ]);

        $this->assertSame([
            'request' => [
                'headers' => ['authorization' => '[REDACTED]'],
                'paths' => ['/s/[TOKEN]', '/fr/faq'],
            ],
        ], $record->context);
    }

    /** @return array<string, array{string, string}> */
    public static function urlsWithTokens(): array
    {
        return [
            'magic link admin localisé' => [
                'https://secret.test/fr/admin/verify/'.self::MAGIC_LINK_TOKEN,
                'https://secret.test/fr/admin/verify/[TOKEN]',
            ],
            'magic link superadmin' => [
                'https://secret.test/de/superadmin/verify/'.self::MAGIC_LINK_TOKEN,
                'https://secret.test/de/superadmin/verify/[TOKEN]',
            ],
            'page de lecture du secret' => [
                'https://secret.test/s/'.self::SECRET_TOKEN,
                'https://secret.test/s/[TOKEN]',
            ],
            'téléchargement du secret' => [
                'https://secret.test/s/'.self::SECRET_TOKEN.'/download',
                'https://secret.test/s/[TOKEN]/download',
            ],
            'API de lecture' => [
                'https://secret.test/api/secrets/'.self::SECRET_TOKEN.'/read',
                'https://secret.test/api/secrets/[TOKEN]/read',
            ],
            'chemin relatif de l\'API' => [
                '/api/secrets/'.self::SECRET_TOKEN,
                '/api/secrets/[TOKEN]',
            ],
            'URL avec fragment de clé' => [
                'https://secret.test/s/abc#key-material',
                'https://secret.test/s/abc#[REDACTED]',
            ],
        ];
    }

    /** Vérifie que les tokens d'URL sont masqués dans le message, les chaînes du contexte (url, uri) et extra. */
    #[DataProvider('urlsWithTokens')]
    public function testRedactsTokensInUrlsEverywhere(string $url, string $expected): void
    {
        $record = $this->process(
            message: "GET {$url} failed",
            context: ['url' => $url, 'uri' => $url],
            extra: ['url' => $url],
        );

        $this->assertSame("GET {$expected} failed", $record->message);
        $this->assertSame(['url' => $expected, 'uri' => $expected], $record->context);
        $this->assertSame(['url' => $expected], $record->extra);
    }

    /** Vérifie que la valeur d'une clé JSON sensible est masquée dans un message. */
    #[DataProvider('sensitiveKeys')]
    public function testRedactsSensitiveJsonKeyInMessage(string $key): void
    {
        $record = $this->process(message: "Payload {\"{$key}\": \"value-to-hide\", \"type\": \"text\"}");

        $this->assertSame("Payload {\"{$key}\": \"[REDACTED]\", \"type\": \"text\"}", $record->message);
    }

    /** Vérifie que le jeton d'un en-tête Bearer est masqué. */
    public function testRedactsBearerToken(): void
    {
        $record = $this->process(message: 'Authorization: Bearer eyJhbGciOi.payload.sig rejected');

        $this->assertSame('Authorization: Bearer [REDACTED] rejected', $record->message);
    }

    /** Vérifie que les longues chaînes base64 sont masquées. */
    public function testRedactsLongBase64Strings(): void
    {
        $record = $this->process(message: 'Data: '.str_repeat('A', 200).' end');

        $this->assertSame('Data: [REDACTED_DATA] end', $record->message);
    }

    /** Vérifie que les chaînes courtes ne sont pas masquées. */
    public function testPreservesShortStrings(): void
    {
        $message = 'Data: '.str_repeat('A', 50);

        $this->assertSame($message, $this->process(message: $message)->message);
    }

    /** Vérifie qu'une exception du contexte, formatée par le canal de log, ne laisse fuiter ni token ni fragment. */
    public function testExceptionInContextDoesNotLeakTokensOnceFormatted(): void
    {
        $url = 'https://secret.test/s/'.self::SECRET_TOKEN.'#key-material';
        $exception = new RuntimeException("Échec sur {$url}", 0, new LogicException('/fr/admin/verify/'.self::MAGIC_LINK_TOKEN));
        $formatted = $this->formatThroughSanitizedChannel($exception);

        $this->assertStringNotContainsString(self::SECRET_TOKEN, $formatted);
        $this->assertStringNotContainsString(self::MAGIC_LINK_TOKEN, $formatted);
        $this->assertStringNotContainsString('key-material', $formatted);
        $this->assertStringContainsString('RuntimeException', $formatted);
        $this->assertStringContainsString('LogicException', $formatted);
        $this->assertStringContainsString('https://secret.test/s/[TOKEN]#[REDACTED]', $formatted);
        $this->assertStringContainsString('[stacktrace]', $formatted);
    }

    /** Vérifie qu'une QueryException formatée par le canal de log ne contient pas le token lié à la requête SQL. */
    public function testQueryExceptionDoesNotLeakBoundTokenOnceFormatted(): void
    {
        try {
            DB::table('secrets')->where('token', self::SECRET_TOKEN)->whereRaw('missing_column = 1')->first();
            $this->fail('La requête aurait dû échouer.');
        } catch (QueryException $exception) {
            $formatted = $this->formatThroughSanitizedChannel($exception);
        }

        $this->assertStringNotContainsString(self::SECRET_TOKEN, $formatted);
        $this->assertStringContainsString('where "token" = ?', $formatted);
    }

    /** Vérifie que chaque connexion de base masque les valeurs liées dans les messages de QueryException. */
    public function testEveryDatabaseConnectionMasksBindingsInExceptionMessages(): void
    {
        $connections = config('database.connections');
        $this->assertIsArray($connections);

        $unmasked = collect($connections)
            ->reject(fn (mixed $connection): bool => is_array($connection)
                && ($connection['mask_bindings_in_exception_messages'] ?? false) === true)
            ->keys()
            ->all();

        $this->assertSame([], $unmasked);
    }

    /** Vérifie que chaque canal qui écrit des logs applique le processeur de sanitisation. */
    public function testEveryWritingChannelAppliesTheProcessor(): void
    {
        $channels = config('logging.channels');
        $this->assertIsArray($channels);

        $unsanitized = collect($channels)
            ->except(['stack', 'null', 'emergency'])
            ->reject(fn (mixed $channel): bool => is_array($channel)
                && in_array(SanitizeProcessor::class, (array) ($channel['processors'] ?? []), true))
            ->keys()
            ->all();

        $this->assertSame([], $unsanitized);
    }

    private function formatThroughSanitizedChannel(Throwable $exception): string
    {
        $logger = Log::build([
            'driver' => 'monolog',
            'handler' => TestHandler::class,
            'processors' => [SanitizeProcessor::class],
        ]);

        $logger->error($exception->getMessage(), ['exception' => $exception]);

        $this->assertInstanceOf(Logger::class, $logger);
        $monolog = $logger->getLogger();
        $this->assertInstanceOf(MonologLogger::class, $monolog);
        $handler = $monolog->getHandlers()[0];
        $this->assertInstanceOf(TestHandler::class, $handler);
        $formatted = $handler->getRecords()[0]->formatted;
        $this->assertIsString($formatted);

        return $formatted;
    }
}
