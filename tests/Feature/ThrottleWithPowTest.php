<?php

namespace Tests\Feature;

use App\Services\ProofOfWorkService;
use Illuminate\Support\Sleep;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class ThrottleWithPowTest extends TestCase
{
    /** Seuil de la route POST /api/secrets (throttle.pow:3,1). */
    private const API_THRESHOLD = 3;

    /** Seuil des routes de demande de magic link (throttle.pow:3,10). */
    private const MAGIC_LINK_THRESHOLD = 3;

    private const CLIENT_IP = '198.51.100.10';

    protected function setUp(): void
    {
        parent::setUp();

        config(['pow.difficulty' => 8, 'pow.ttl_seconds' => 300]);
    }

    /** Vérifie que les requêtes sous la limite passent normalement. */
    public function testRequestsUnderLimitPassThrough(): void
    {
        for ($i = 0; $i < self::API_THRESHOLD; $i++) {
            $this->postSecret()->assertCreated();
        }
    }

    /** Vérifie qu'une requête au-delà de la limite renvoie 429 avec un challenge PoW et ses en-têtes. */
    public function testRequestOverLimitReturnsPowChallenge(): void
    {
        $this->exhaustApiLimit();

        $response = $this->postSecret();

        $response->assertTooManyRequests();
        $response->assertHeader('X-Pow-Required', 'true');
        $response->assertHeader('Retry-After', '60');
        $response->assertJson([
            'error' => 'rate_limit_exceeded',
            'message' => __('messages.rate_limit_exceeded'),
            'pow_required' => true,
            'pow_difficulty' => 8,
            'retry_after' => 60,
        ]);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{32}$/', $response->json('pow_token'));
        $this->assertMatchesRegularExpression('/^[0-9a-f]{32}$/', $response->json('pow_challenge'));
    }

    /** Vérifie qu'un PoW valide transmis dans le corps laisse passer la requête. */
    public function testValidPowInBodyAllowsRequest(): void
    {
        $challenge = $this->obtainChallenge();

        $response = $this->postSecret([
            'pow_token' => $challenge['pow_token'],
            'pow_nonce' => $this->solve($challenge),
        ]);

        $response->assertCreated();
    }

    /** Vérifie qu'un PoW valide transmis par les en-têtes X-Pow-Token et X-Pow-Nonce laisse passer la requête. */
    public function testValidPowInHeadersAllowsRequest(): void
    {
        $challenge = $this->obtainChallenge();

        $response = $this->withServerVariables(['REMOTE_ADDR' => self::CLIENT_IP])
            ->postJson('/api/secrets', $this->validSecretPayload(), [
                'X-Pow-Token' => $challenge['pow_token'],
                'X-Pow-Nonce' => $this->solve($challenge),
            ]);

        $response->assertCreated();
    }

    /** Vérifie que le compteur n'est pas remis à zéro après un PoW réussi. */
    public function testCounterDoesNotResetAfterValidPow(): void
    {
        $challenge = $this->obtainChallenge();
        $this->postSecret([
            'pow_token' => $challenge['pow_token'],
            'pow_nonce' => $this->solve($challenge),
        ])->assertCreated();

        $response = $this->postSecret();

        $response->assertTooManyRequests();
        $response->assertJsonPath('pow_required', true);
    }

    /** Vérifie qu'un nonce qui ne résout pas le challenge est refusé avec un nouveau challenge. */
    public function testInvalidNonceReturnsNewChallenge(): void
    {
        $challenge = $this->obtainChallenge();

        $response = $this->postSecret([
            'pow_token' => $challenge['pow_token'],
            'pow_nonce' => $this->nonceThatDoesNotSolve($challenge),
        ]);

        $response->assertTooManyRequests();
        $response->assertJsonPath('pow_required', true);
        $this->assertNotSame($challenge['pow_token'], $response->json('pow_token'));
    }

    /** Vérifie qu'un couple jeton/nonce déjà accepté ne peut pas être rejoué. */
    public function testReplayedPowIsRejected(): void
    {
        $challenge = $this->obtainChallenge();
        $proof = [
            'pow_token' => $challenge['pow_token'],
            'pow_nonce' => $this->solve($challenge),
        ];
        $this->postSecret($proof)->assertCreated();

        $response = $this->postSecret($proof);

        $response->assertTooManyRequests();
        $this->assertNotSame($challenge['pow_token'], $response->json('pow_token'));
    }

    /** Vérifie qu'un PoW calculé pour une autre IP est refusé. */
    public function testPowSolvedForAnotherIpIsRejected(): void
    {
        $challenge = $this->obtainChallenge();
        $otherIp = '203.0.113.20';
        $this->exhaustApiLimit($otherIp);

        $response = $this->postSecret([
            'pow_token' => $challenge['pow_token'],
            'pow_nonce' => $this->solve($challenge),
        ], $otherIp);

        $response->assertTooManyRequests();
        $response->assertJsonPath('pow_required', true);
    }

    /** Vérifie qu'un challenge résolu après l'expiration de sa durée de vie est refusé. */
    public function testExpiredChallengeIsRejected(): void
    {
        $this->freezeTime();
        config(['pow.ttl_seconds' => 30]);
        $challenge = $this->obtainChallenge();
        $this->travel(30)->seconds();

        $response = $this->postSecret([
            'pow_token' => $challenge['pow_token'],
            'pow_nonce' => $this->solve($challenge),
        ]);

        $response->assertTooManyRequests();
        $response->assertJsonPath('pow_required', true);
    }

    /** Vérifie que la fenêtre du compteur est fixe : toujours bloqué à 59 s, libéré à 60 s. */
    public function testCounterWindowIsFixedFromFirstHit(): void
    {
        $this->freezeTime();
        $this->exhaustApiLimit();
        $this->travel(59)->seconds();

        $this->postSecret()->assertTooManyRequests();

        $this->travel(1)->second();

        $this->postSecret()->assertCreated();
    }

    /** Vérifie le câblage sur la demande de magic link admin : retour au formulaire avec email saisi, erreur pow et challenge. */
    public function testAdminMagicLinkRequestOverLimitRedirectsBackWithPowChallenge(): void
    {
        Sleep::fake();

        for ($i = 0; $i < self::MAGIC_LINK_THRESHOLD; $i++) {
            $this->from('/fr/admin')->post('/fr/admin/request-access', ['email' => 'nobody@example.com']);
        }

        $response = $this->from('/fr/admin')->post('/fr/admin/request-access', ['email' => 'nobody@example.com']);

        $response->assertRedirect('/fr/admin');
        $response->assertSessionHasInput('email', 'nobody@example.com');
        $response->assertSessionHasErrors(['pow' => __('messages.rate_limit_exceeded')]);
        $response->assertSessionHas('pow_required', true);
        $powToken = session('pow_token');

        $form = $this->get('/fr/admin');

        $form->assertSee('value="nobody@example.com"', false);
        $form->assertSee("name=\"pow_token\" value=\"{$powToken}\"", false);
    }

    /** Vérifie qu'un PoW résolu depuis le formulaire admin laisse passer la demande de magic link. */
    public function testAdminMagicLinkRequestWithSolvedPowPasses(): void
    {
        Sleep::fake();

        for ($i = 0; $i <= self::MAGIC_LINK_THRESHOLD; $i++) {
            $this->post('/fr/admin/request-access', ['email' => 'nobody@example.com']);
        }

        $response = $this->post('/fr/admin/request-access', [
            'email' => 'nobody@example.com',
            'pow_token' => session('pow_token'),
            'pow_nonce' => app(ProofOfWorkService::class)->solve(session('pow_challenge'), session('pow_difficulty')),
        ]);

        $response->assertRedirect('/fr/admin/access-sent');
    }

    /** Vérifie le câblage du throttle PoW sur la demande de magic link superadmin. */
    public function testSuperAdminMagicLinkRequestOverLimitRequiresPow(): void
    {
        Sleep::fake();

        for ($i = 0; $i < self::MAGIC_LINK_THRESHOLD; $i++) {
            $this->post('/fr/superadmin/request-access', ['email' => 'nobody@example.com']);
        }

        $response = $this->post('/fr/superadmin/request-access', ['email' => 'nobody@example.com']);

        $response->assertSessionHas('pow_required', true);
    }

    private function exhaustApiLimit(string $ip = self::CLIENT_IP): void
    {
        for ($i = 0; $i < self::API_THRESHOLD; $i++) {
            $this->postSecret([], $ip)->assertCreated();
        }
    }

    /**
     * @return array{pow_token: string, pow_challenge: string, pow_difficulty: int}
     */
    private function obtainChallenge(): array
    {
        $this->exhaustApiLimit();

        $response = $this->postSecret()->assertTooManyRequests();

        return [
            'pow_token' => $response->json('pow_token'),
            'pow_challenge' => $response->json('pow_challenge'),
            'pow_difficulty' => $response->json('pow_difficulty'),
        ];
    }

    /**
     * @param  array{pow_token: string, pow_challenge: string, pow_difficulty: int}  $challenge
     */
    private function solve(array $challenge): string
    {
        return app(ProofOfWorkService::class)->solve($challenge['pow_challenge'], $challenge['pow_difficulty']);
    }

    /**
     * Returns the first nonce whose hash does not start with a zero byte, which
     * fails the difficulty of 8 bits configured for these tests.
     *
     * @param  array{pow_token: string, pow_challenge: string, pow_difficulty: int}  $challenge
     */
    private function nonceThatDoesNotSolve(array $challenge): string
    {
        $challengeBytes = (string) hex2bin($challenge['pow_challenge']);

        for ($candidate = 0; ; $candidate++) {
            $nonce = sprintf('%08x', $candidate);

            if (ord(hash('sha256', $challengeBytes.hex2bin($nonce), true)[0]) !== 0) {
                return $nonce;
            }
        }
    }

    /**
     * @param  array<string, mixed>  $extra
     * @return TestResponse<\Symfony\Component\HttpFoundation\Response>
     */
    private function postSecret(array $extra = [], string $ip = self::CLIENT_IP): TestResponse
    {
        return $this->withServerVariables(['REMOTE_ADDR' => $ip])
            ->postJson('/api/secrets', array_merge($this->validSecretPayload(), $extra));
    }

    /**
     * @return array<string, mixed>
     */
    private function validSecretPayload(): array
    {
        return [
            'type' => 'text',
            'ciphertext' => 'ZGRkZGRkZGRkZGRkZGRkZGRkZGRkZGRkZGRkZGRkZGQ',
            'cipher_meta' => [
                'alg' => 'AES-256-GCM',
                'iv' => 'YWFhYWFhYWFhYWFh',
                'version' => 1,
            ],
            'expiration' => '7d',
        ];
    }
}
