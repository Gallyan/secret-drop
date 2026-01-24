<?php

namespace App\Logging;

use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

class SanitizeProcessor implements ProcessorInterface
{
    private const SENSITIVE_KEYS = [
        'password',
        'password_confirmation',
        'secret',
        'token',
        'api_key',
        'apikey',
        'authorization',
        'credit_card',
        'card_number',
        'cvv',
        'ssn',
        'private_key',
    ];

    public function __invoke(LogRecord $record): LogRecord
    {
        $context = $this->sanitizeArray($record->context);
        $extra = $this->sanitizeArray($record->extra);
        $message = $this->sanitizeString($record->message);

        return $record->with(
            message: $message,
            context: $context,
            extra: $extra
        );
    }

    private function sanitizeArray(array $data): array
    {
        foreach ($data as $key => $value) {
            if ($this->isSensitiveKey($key)) {
                $data[$key] = '[REDACTED]';
            } elseif (is_array($value)) {
                $data[$key] = $this->sanitizeArray($value);
            } elseif (is_string($value)) {
                $data[$key] = $this->sanitizeString($value);
            }
        }

        return $data;
    }

    private function sanitizeString(string $value): string
    {
        $patterns = [
            '/("(?:password|secret|token|api_key|authorization)":\s*")[^"]*(")/i' => '$1[REDACTED]$2',
            '/(Bearer\s+)[^\s]+/i' => '$1[REDACTED]',
        ];

        foreach ($patterns as $pattern => $replacement) {
            $value = preg_replace($pattern, $replacement, $value) ?? $value;
        }

        return $value;
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
