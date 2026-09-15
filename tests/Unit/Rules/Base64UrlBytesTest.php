<?php

namespace Tests\Unit\Rules;

use App\Rules\Base64UrlBytes;
use Illuminate\Translation\PotentiallyTranslatedString;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class Base64UrlBytesTest extends TestCase
{
    private const INVALID_BASE64URL = 'La valeur doit être une chaîne Base64URL valide.';

    /**
     * @return array<string, array{0: mixed}>
     */
    public static function malformedValues(): array
    {
        return [
            'entier' => [123],
            'null' => [null],
            'tableau' => [['YWFh']],
            'chaîne vide' => [''],
            'caractère + du base64 standard' => ['YWF+YWFh'],
            'caractère / du base64 standard' => ['YWF/YWFh'],
            'padding =' => ['YWE='],
            'espace' => ['YWFh YWFh'],
            'longueur ≡ 1 mod 4 non décodable' => ['YWFhY'],
            'un seul caractère' => ['A'],
        ];
    }

    /** Vérifie qu'une valeur qui n'est pas du Base64URL décodable est refusée avec le message dédié. */
    #[DataProvider('malformedValues')]
    public function testRejectsMalformedValueWithInvalidBase64UrlMessage(mixed $value): void
    {
        $failures = $this->validate(new Base64UrlBytes(), $value);

        $this->assertSame([self::INVALID_BASE64URL], $failures);
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function wellFormedValues(): array
    {
        return [
            'un octet sans padding' => ['YQ'],
            'deux octets sans padding' => ['YWE'],
            'trois octets' => ['YWFh'],
            'alphabet URL-safe - et _' => ['-_-_'],
        ];
    }

    /** Vérifie qu'une valeur Base64URL valide passe sans contrainte de taille. */
    #[DataProvider('wellFormedValues')]
    public function testAcceptsWellFormedValueWithoutSizeConstraint(string $value): void
    {
        $failures = $this->validate(new Base64UrlBytes(), $value);

        $this->assertSame([], $failures);
    }

    /**
     * @return array<string, array{0: string, 1: list<string>}>
     */
    public static function exactByteLengthCases(): array
    {
        return [
            '11 octets' => ['YWFhYWFhYWFhYWE', ['Taille en octets invalide : 12 attendus, 11 reçus.']],
            '12 octets' => ['YWFhYWFhYWFhYWFh', []],
            '13 octets' => ['YWFhYWFhYWFhYWFhYQ', ['Taille en octets invalide : 12 attendus, 13 reçus.']],
        ];
    }

    /**
     * Vérifie que la taille exacte décodée est imposée.
     *
     * @param  list<string>  $expectedFailures
     */
    #[DataProvider('exactByteLengthCases')]
    public function testEnforcesExactDecodedByteLength(string $value, array $expectedFailures): void
    {
        $failures = $this->validate(new Base64UrlBytes(exactBytes: 12), $value);

        $this->assertSame($expectedFailures, $failures);
    }

    /**
     * @return array<string, array{0: string, 1: list<string>}>
     */
    public static function minByteLengthCases(): array
    {
        return [
            '15 octets' => ['YWFhYWFhYWFhYWFhYWFh', ['Taille minimale requise : 16 octets, 15 reçus.']],
            '16 octets' => ['YWFhYWFhYWFhYWFhYWFhYQ', []],
            '17 octets' => ['YWFhYWFhYWFhYWFhYWFhYWE', []],
        ];
    }

    /**
     * Vérifie que la taille minimale décodée est imposée.
     *
     * @param  list<string>  $expectedFailures
     */
    #[DataProvider('minByteLengthCases')]
    public function testEnforcesMinimumDecodedByteLength(string $value, array $expectedFailures): void
    {
        $failures = $this->validate(new Base64UrlBytes(minBytes: 16), $value);

        $this->assertSame($expectedFailures, $failures);
    }

    /** Vérifie qu'une valeur invalide en charset n'atteint pas le contrôle de taille. */
    public function testMalformedValueReportsOnlyTheFormatError(): void
    {
        $failures = $this->validate(new Base64UrlBytes(exactBytes: 12), 'invalid+chars/here==');

        $this->assertSame([self::INVALID_BASE64URL], $failures);
    }

    /**
     * @return list<string>
     */
    private function validate(Base64UrlBytes $rule, mixed $value): array
    {
        $failures = [];

        $rule->validate('value', $value, function (string $message) use (&$failures): PotentiallyTranslatedString {
            $failures[] = $message;

            return new PotentiallyTranslatedString($message, app('translator'));
        });

        return $failures;
    }
}
