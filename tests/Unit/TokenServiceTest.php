<?php

namespace Tests\Unit;

use App\Services\TokenService;
use Tests\TestCase;

class TokenServiceTest extends TestCase
{
    private const PLAIN_TOKEN = 'abc';

    private const PLAIN_TOKEN_SHA256 = 'ba7816bf8f01cfea414140de5dae2223b00361a396177a9cb410ff61f20015ad';

    private TokenService $tokenService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tokenService = new TokenService();
    }

    /** Vérifie que le token public est composé de 32 caractères hexadécimaux (128 bits). */
    public function testPublicTokenIsThirtyTwoHexadecimalCharacters(): void
    {
        $token = $this->tokenService->generatePublicToken();

        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $token);
    }

    /** Vérifie que le token admin est composé de 32 caractères hexadécimaux et accompagné de son empreinte SHA-256. */
    public function testAdminTokenIsThirtyTwoHexadecimalCharactersWithItsSha256Hash(): void
    {
        $result = $this->tokenService->generateAdminToken();

        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $result['token']);
        $this->assertSame($this->tokenService->hashToken($result['token']), $result['hash']);
    }

    /** Vérifie que le token de magic link est composé de 32 caractères hexadécimaux et accompagné de son empreinte SHA-256. */
    public function testMagicLinkTokenIsThirtyTwoHexadecimalCharactersWithItsSha256Hash(): void
    {
        $result = $this->tokenService->generateMagicLinkToken();

        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $result['token']);
        $this->assertSame($this->tokenService->hashToken($result['token']), $result['hash']);
    }

    /** Vérifie que hashToken retourne l'empreinte SHA-256 hexadécimale connue d'une chaîne fixe. */
    public function testHashTokenReturnsKnownSha256Digest(): void
    {
        $hash = $this->tokenService->hashToken(self::PLAIN_TOKEN);

        $this->assertSame(self::PLAIN_TOKEN_SHA256, $hash);
    }

    /** Vérifie que verifyToken accepte le token correspondant à l'empreinte stockée. */
    public function testVerifyTokenReturnsTrueForMatchingToken(): void
    {
        $this->assertTrue($this->tokenService->verifyToken(self::PLAIN_TOKEN, self::PLAIN_TOKEN_SHA256));
    }

    /** Vérifie que verifyToken refuse un autre token que celui de l'empreinte stockée. */
    public function testVerifyTokenReturnsFalseForWrongToken(): void
    {
        $this->assertFalse($this->tokenService->verifyToken('abd', self::PLAIN_TOKEN_SHA256));
    }

    /** Vérifie que verifyToken refuse le bon token face à une empreinte altérée. */
    public function testVerifyTokenReturnsFalseForTamperedHash(): void
    {
        $this->assertFalse($this->tokenService->verifyToken(self::PLAIN_TOKEN, str_repeat('0', 64)));
    }
}
