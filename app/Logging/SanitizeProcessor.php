<?php

namespace App\Logging;

use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;
use Throwable;

/**
 * Monolog processor to sanitize sensitive data from logs.
 *
 * Zero-knowledge principle: tokens, URLs with fragments, ciphertext,
 * and other sensitive data must never appear in logs.
 */
class SanitizeProcessor implements ProcessorInterface
{
    private const SENSITIVE_KEYS = [
        'password',
        'password_confirmation',
        'secret',
        'token',
        'admin_token',
        'api_key',
        'apikey',
        'authorization',
        'credit_card',
        'card_number',
        'cvv',
        'ssn',
        'private_key',
        'ciphertext',
        'cipher_meta',
        'passphrase',
        'key',
        'fragment',
    ];

    private const URL_PATTERNS = [
        // Bearer tokens
        '/(Bearer\s+)[^\s]+/i' => '$1[REDACTED]',

        // URLs with fragments (the fragment contains the encryption key)
        '/(https?:\/\/[^#\s]+)#[^\s]*/i' => '$1#[REDACTED]',

        // Secret URLs: /s/{token}
        '#(/s/)[A-Za-z0-9_-]{20,}#' => '$1[TOKEN]',

        // API secret URLs: /api/secrets/{token}
        '#(/api/secrets/)[A-Za-z0-9_-]{20,}#' => '$1[TOKEN]',

        // Admin verify URLs: /admin/verify/{token}
        '#(/admin/verify/)[A-Za-z0-9_-]{20,}#' => '$1[TOKEN]',

        // Superadmin verify URLs: /superadmin/verify/{token}
        '#(/superadmin/verify/)[A-Za-z0-9_-]{20,}#' => '$1[TOKEN]',

        // Base64-encoded data (potential ciphertext) - very long unbroken strings
        // Threshold at 200 to avoid redacting stack traces or long class names
        '/[A-Za-z0-9+\/=_-]{200,}/' => '[REDACTED_DATA]',
    ];

    public function __invoke(LogRecord $record): LogRecord
    {
        return $record->with(
            message: $this->sanitizeString($record->message),
            context: $this->sanitizeArray($record->context),
            extra: $this->sanitizeArray($record->extra)
        );
    }

    /**
     * @param array<array-key, mixed> $data
     * @return array<array-key, mixed>
     */
    private function sanitizeArray(array $data): array
    {
        foreach ($data as $key => $value) {
            $data[$key] = is_string($key) && $this->isSensitiveKey($key)
                ? '[REDACTED]'
                : $this->sanitizeValue($value);
        }

        return $data;
    }

    private function sanitizeValue(mixed $value): mixed
    {
        return match (true) {
            is_array($value) => $this->sanitizeArray($value),
            is_string($value) => $this->sanitizeString($value),
            $value instanceof Throwable => $this->sanitizeString($this->describeThrowable($value)),
            default => $value,
        };
    }

    private function sanitizeString(string $value): string
    {
        $patterns = [$this->jsonKeyPattern() => '$1[REDACTED]$2'] + self::URL_PATTERNS;

        foreach ($patterns as $pattern => $replacement) {
            $value = preg_replace($pattern, $replacement, $value) ?? $value;
        }

        return $value;
    }

    /** Cible la valeur texte de toute clé JSON contenant une clé sensible, comme isSensitiveKey() pour le contexte. */
    private function jsonKeyPattern(): string
    {
        $keys = implode('|', array_map(fn (string $key): string => preg_quote($key, '/'), self::SENSITIVE_KEYS));

        return '/("[^"]*(?:'.$keys.')[^"]*"\s*:\s*")(?:[^"\\\\]|\\\\.)*(")/i';
    }

    /**
     * Sans cela, l'objet exception atteindrait le formateur intact, message et trace compris.
     * Rendu comme le LineFormatter de Monolog pour que les fichiers de log gardent leur forme.
     */
    private function describeThrowable(Throwable $exception): string
    {
        $description = $this->describeSingleThrowable($exception);

        for ($previous = $exception->getPrevious(); $previous !== null; $previous = $previous->getPrevious()) {
            $description .= "\n[previous exception] {$this->describeSingleThrowable($previous)}";
        }

        return $description;
    }

    private function describeSingleThrowable(Throwable $exception): string
    {
        $class = $exception::class;

        return "[object] ({$class}(code: {$exception->getCode()}): {$exception->getMessage()}"
            ." at {$exception->getFile()}:{$exception->getLine()})\n[stacktrace]\n{$exception->getTraceAsString()}";
    }

    private function isSensitiveKey(string $key): bool
    {
        $lowerKey = strtolower($key);

        foreach (self::SENSITIVE_KEYS as $sensitiveKey) {
            if (str_contains($lowerKey, $sensitiveKey)) {
                return true;
            }
        }

        return false;
    }
}
