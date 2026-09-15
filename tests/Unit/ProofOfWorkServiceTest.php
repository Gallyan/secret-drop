<?php

namespace Tests\Unit;

use App\Services\ProofOfWorkService;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ProofOfWorkServiceTest extends TestCase
{
    private ProofOfWorkService $pow;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pow = new ProofOfWorkService();
        config(['pow.difficulty' => 4, 'pow.ttl_seconds' => 300]);
    }

    /** Vérifie que generate renvoie un jeton et un challenge hexadécimaux de 128 bits et la difficulté configurée. */
    public function testGenerateReturnsTokenChallengeAndConfiguredDifficulty(): void
    {
        $result = $this->pow->generate('test-identifier');

        $this->assertMatchesRegularExpression('/^[0-9a-f]{32}$/', $result['token']);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{32}$/', $result['challenge']);
        $this->assertSame(4, $result['difficulty']);
    }

    /** Vérifie que generate mémorise le challenge, la difficulté et l'identifiant sous le jeton. */
    public function testGenerateStoresChallengeForToken(): void
    {
        $this->travelTo('2026-09-15 10:00:00');

        $result = $this->pow->generate('test-identifier');

        $this->assertSame([
            'challenge' => $result['challenge'],
            'difficulty' => 4,
            'identifier' => 'test-identifier',
            'created_at' => 1789466400,
        ], Cache::get("pow:{$result['token']}"));
    }

    /** Vérifie que verify accepte un nonce qui résout le challenge. */
    public function testVerifyReturnsTrueForSolvingNonce(): void
    {
        $result = $this->pow->generate('test-identifier');
        $nonce = $this->pow->solve($result['challenge'], $result['difficulty']);

        $this->assertTrue($this->pow->verify($result['token'], $nonce, 'test-identifier'));
    }

    /** Vérifie que verify refuse un nonce dont le hash ne commence pas par les bits nuls requis. */
    public function testVerifyReturnsFalseForNonceThatDoesNotSolve(): void
    {
        config(['pow.difficulty' => 20]);
        $result = $this->pow->generate('test-identifier');

        $nonce = $this->nonceWithNonZeroFirstByte($result['challenge']);

        $this->assertFalse($this->pow->verify($result['token'], $nonce, 'test-identifier'));
    }

    /** Vérifie que verify refuse un jeton inconnu. */
    public function testVerifyReturnsFalseForUnknownToken(): void
    {
        $this->assertFalse($this->pow->verify('invalid-token', '00000000', 'test-identifier'));
    }

    /** Vérifie que verify refuse un challenge généré pour un autre identifiant. */
    public function testVerifyReturnsFalseForAnotherIdentifier(): void
    {
        $result = $this->pow->generate('identifier-1');
        $nonce = $this->pow->solve($result['challenge'], $result['difficulty']);

        $this->assertFalse($this->pow->verify($result['token'], $nonce, 'identifier-2'));
    }

    /** Vérifie que verify refuse un challenge dont la durée de vie est écoulée. */
    public function testVerifyReturnsFalseOnceChallengeTtlHasElapsed(): void
    {
        $this->freezeTime();
        config(['pow.ttl_seconds' => 30]);
        $result = $this->pow->generate('test-identifier');
        $nonce = $this->pow->solve($result['challenge'], $result['difficulty']);
        $this->travel(30)->seconds();

        $this->assertFalse($this->pow->verify($result['token'], $nonce, 'test-identifier'));
    }

    /** Vérifie que le jeton est consommé après une vérification réussie. */
    public function testVerifyConsumesToken(): void
    {
        $result = $this->pow->generate('test-identifier');
        $nonce = $this->pow->solve($result['challenge'], $result['difficulty']);

        $this->assertTrue($this->pow->verify($result['token'], $nonce, 'test-identifier'));
        $this->assertFalse($this->pow->verify($result['token'], $nonce, 'test-identifier'));
    }

    /** Vérifie que solve trouve un nonce dont le hash commence par les bits nuls demandés. */
    public function testSolveFindsNonceWithLeadingZeroBits(): void
    {
        $challenge = '00112233445566778899aabbccddeeff';

        $nonce = $this->pow->solve($challenge, 4);

        $hash = hash('sha256', hex2bin($challenge).hex2bin($nonce), true);
        $this->assertSame(0, ord($hash[0]) >> 4, 'Les 4 premiers bits doivent être nuls');
    }

    /** Vérifie que verify rejette un nonce mal formaté. */
    public function testVerifyRejectsMalformedNonce(): void
    {
        $result = $this->pow->generate('test-identifier');

        $this->assertFalse($this->pow->verify($result['token'], 'xyz!@#$%', 'test-identifier'));
        $this->assertFalse($this->pow->verify($result['token'], str_repeat('a', 34), 'test-identifier'));
        $this->assertFalse($this->pow->verify($result['token'], '', 'test-identifier'));
    }

    /** Vérifie qu'un nonce de longueur impaire est rejeté sans warning hex2bin. */
    public function testVerifyRejectsOddLengthNonceWithoutWarning(): void
    {
        $result = $this->pow->generate('test-identifier');

        set_error_handler(function (int $errno, string $errstr): bool {
            $this->fail("Unexpected PHP warning/notice: {$errstr}");
        });

        try {
            $this->assertFalse($this->pow->verify($result['token'], 'abc', 'test-identifier'));
            $this->assertFalse($this->pow->verify($result['token'], 'a', 'test-identifier'));
            $this->assertFalse($this->pow->verify($result['token'], str_repeat('a', 31), 'test-identifier'));
        } finally {
            restore_error_handler();
        }
    }

    private function nonceWithNonZeroFirstByte(string $challenge): string
    {
        $challengeBytes = (string) hex2bin($challenge);

        for ($candidate = 0; ; $candidate++) {
            $nonce = sprintf('%08x', $candidate);

            if (ord(hash('sha256', $challengeBytes.hex2bin($nonce), true)[0]) !== 0) {
                return $nonce;
            }
        }
    }
}
