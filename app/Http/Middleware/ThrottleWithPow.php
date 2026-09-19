<?php

namespace App\Http\Middleware;

use App\Services\ProofOfWorkService;
use Carbon\CarbonInterface;
use Closure;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/** Rate-limits requests per IP and requires a proof-of-work once the threshold is exceeded. */
class ThrottleWithPow
{
    private const CACHE_PREFIX = 'throttle:';

    private const LOCK_SECONDS = 5;

    private const LOCK_WAIT_SECONDS = 3;

    public function __construct(
        private ProofOfWorkService $pow,
    ) {
    }

    /**
     * @param  string  $maxAttempts  Maximum attempts before requiring PoW
     * @param  string  $decayMinutes  Time window in minutes
     */
    public function handle(Request $request, Closure $next, string $maxAttempts = '5', string $decayMinutes = '1'): Response
    {
        $maxAttempts = (int) $maxAttempts;
        $decayMinutes = (int) $decayMinutes;

        $identifier = $this->getIdentifier($request);
        $cacheKey = self::CACHE_PREFIX.$identifier.':'.($request->route()?->getName() ?? $request->path());
        $ttl = now()->addMinutes($decayMinutes);

        $attempts = $this->hit($cacheKey, $ttl);

        if ($attempts <= $maxAttempts) {
            return $next($request);
        }

        // Rate limit exceeded — check for valid proof-of-work
        $powToken = $request->string('pow_token', $request->header('X-Pow-Token', ''))->toString();
        $powNonce = $request->string('pow_nonce', $request->header('X-Pow-Nonce', ''))->toString();

        if ($powToken !== '' && $powNonce !== '') {
            if ($this->pow->verify($powToken, $powNonce, $identifier)) {
                return $next($request);
            }
        }

        // Generate new PoW challenge
        $powData = $this->pow->generate($identifier);

        $retryAfter = $decayMinutes * 60;

        return $this->buildPowResponse($powData, $retryAfter, $request);
    }

    /**
     * Counts one hit in a fixed window, serialized by a lock so concurrent
     * requests cannot race between add() and increment(). Fails closed when
     * the lock cannot be acquired.
     */
    private function hit(string $cacheKey, CarbonInterface $ttl): int
    {
        $attempts = PHP_INT_MAX;

        try {
            Cache::lock("{$cacheKey}:lock", self::LOCK_SECONDS)
                ->block(self::LOCK_WAIT_SECONDS, function () use ($cacheKey, $ttl, &$attempts): void {
                    if (Cache::add($cacheKey, 1, $ttl)) {
                        $attempts = 1;

                        return;
                    }

                    $incremented = Cache::increment($cacheKey);

                    // A missing or expired key is re-created by increment() without TTL on some stores.
                    if ($incremented === false || $incremented <= 1) {
                        Cache::put($cacheKey, 1, $ttl);
                        $attempts = 1;

                        return;
                    }

                    $attempts = (int) $incremented;
                });
        } catch (LockTimeoutException) {
            return PHP_INT_MAX;
        }

        return $attempts;
    }

    private function getIdentifier(Request $request): string
    {
        return hash('sha256', $request->ip() ?? 'unknown');
    }

    /**
     * @param  array{token: string, challenge: string, difficulty: int}  $powData
     */
    private function buildPowResponse(array $powData, int $retryAfter, Request $request): Response
    {
        $data = [
            'error' => 'rate_limit_exceeded',
            'message' => __('messages.rate_limit_exceeded'),
            'pow_required' => true,
            'pow_token' => $powData['token'],
            'pow_challenge' => $powData['challenge'],
            'pow_difficulty' => $powData['difficulty'],
            'retry_after' => $retryAfter,
        ];

        if ($request->expectsJson()) {
            return response()->json($data, 429)
                ->header('Retry-After', (string) $retryAfter)
                ->header('X-Pow-Required', 'true');
        }

        // For form submissions, redirect back with PoW data in session
        return redirect()
            ->back()
            ->withInput()
            ->with('pow_required', true)
            ->with('pow_token', $powData['token'])
            ->with('pow_challenge', $powData['challenge'])
            ->with('pow_difficulty', $powData['difficulty'])
            ->withErrors(['pow' => __('messages.rate_limit_exceeded')]);
    }
}
