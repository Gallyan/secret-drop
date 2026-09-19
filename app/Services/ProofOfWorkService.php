<?php

namespace App\Services;

use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

/** Generates and verifies SHA-256 proof-of-work challenges to gate rate-limited requests. */
class ProofOfWorkService
{
    private const CACHE_PREFIX = 'pow:';

    private const LOCK_SECONDS = 5;

    private const LOCK_WAIT_SECONDS = 3;

    /**
     * @return array{token: string, challenge: string, difficulty: int}
     */
    public function generate(string $identifier): array
    {
        $challenge = bin2hex(random_bytes(16));
        $token = bin2hex(random_bytes(16));
        $difficulty = Config::integer('pow.difficulty', 20);

        Cache::put(
            self::CACHE_PREFIX.$token,
            [
                'challenge' => $challenge,
                'difficulty' => $difficulty,
                'identifier' => $identifier,
                'created_at' => now()->timestamp,
            ],
            Config::integer('pow.ttl_seconds', 300)
        );

        return [
            'token' => $token,
            'challenge' => $challenge,
            'difficulty' => $difficulty,
        ];
    }

    public function verify(#[\SensitiveParameter] string $token, string $nonce, string $identifier): bool
    {
        $data = $this->consume($token);

        // Defense in depth: the entry is server-generated, but nothing guarantees
        // what the cache hands back, so every field is checked before use.
        if (! is_array($data)) {
            return false;
        }

        $challenge = $data['challenge'] ?? null;
        $difficulty = $data['difficulty'] ?? null;

        if (($data['identifier'] ?? null) !== $identifier) {
            return false;
        }

        if (! is_string($challenge) || ! ctype_xdigit($challenge) || strlen($challenge) % 2 !== 0) {
            return false;
        }

        if (! is_int($difficulty)) {
            return false;
        }

        // Validate nonce format: even-length hex string (1 to 16 bytes), so hex2bin never warns
        if (! preg_match('/^(?:[0-9a-f]{2}){1,16}$/i', $nonce)) {
            return false;
        }

        $hash = hash('sha256', hex2bin($challenge).hex2bin($nonce), true);

        if (! $this->hasLeadingZeroBits($hash, $difficulty)) {
            return false;
        }

        return true;
    }

    /**
     * Atomically fetches and deletes the challenge, so a token is single-use
     * even under concurrent requests. Any attempt burns the token, including
     * one with a wrong nonce: the client simply receives a new challenge.
     */
    private function consume(string $token): mixed
    {
        $cacheKey = self::CACHE_PREFIX.$token;

        try {
            return Cache::lock("{$cacheKey}:lock", self::LOCK_SECONDS)
                ->block(self::LOCK_WAIT_SECONDS, fn (): mixed => Cache::pull($cacheKey));
        } catch (LockTimeoutException) {
            return null;
        }
    }

    /**
     * Check that the first $bits bits of a binary hash string are zero.
     */
    private function hasLeadingZeroBits(string $hashBinary, int $bits): bool
    {
        $fullBytes = intdiv($bits, 8);
        $remainingBits = $bits % 8;

        // Check full zero bytes
        for ($i = 0; $i < $fullBytes; $i++) {
            if (ord($hashBinary[$i]) !== 0) {
                return false;
            }
        }

        // Check remaining bits in the next byte
        if ($remainingBits > 0) {
            $mask = 0xFF << (8 - $remainingBits);

            if ((ord($hashBinary[$fullBytes]) & $mask) !== 0) {
                return false;
            }
        }

        return true;
    }

    /**
     * Brute-force solve a challenge (for tests only).
     */
    public function solve(string $challenge, int $difficulty): string
    {
        $challengeBytes = hex2bin($challenge);

        for ($nonce = 0; $nonce < PHP_INT_MAX; $nonce++) {
            $nonceHex = str_pad(dechex($nonce), 8, '0', STR_PAD_LEFT);
            $hash = hash('sha256', $challengeBytes.hex2bin($nonceHex), true);

            if ($this->hasLeadingZeroBits($hash, $difficulty)) {
                return $nonceHex;
            }
        }

        throw new \RuntimeException('Could not solve PoW challenge');
    }
}
