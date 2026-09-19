<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSecretRequest;
use App\Models\MagicLink;
use App\Models\Secret;
use App\Services\SecretStorageService;
use App\Services\StatsService;
use App\Services\TokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

use function Illuminate\Support\defer;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SecretsController extends Controller
{
    private const READ_ID_PATTERN = '/^[0-9a-f]{32}$/';

    private const READ_ID_TTL_SECONDS = 86400;

    public function __construct(
        private TokenService $tokenService,
        private SecretStorageService $storage,
        private StatsService $stats,
    ) {
    }

    public function create(): View
    {
        return view('secrets.create');
    }

    public function store(StoreSecretRequest $request): JsonResponse
    {
        // Honeypot: bots fill hidden fields, humans don't — return fake success to avoid retries
        if ($request->filled('website')) {
            return response()->json([
                'token' => bin2hex(random_bytes(16)),
                'expire_at' => now()->addDays(7)->toIso8601String(),
            ], 201);
        }

        $type = $request->secretType();

        // Check file storage quota before accepting uploads
        if ($type->isFile() && $this->storage->isQuotaExceeded()) {
            return response()->json([
                'error' => 'service_unavailable',
                'message' => __('messages.storage_quota_exceeded'),
            ], 503);
        }

        $uploadBudgetKey = "upload-bytes:{$request->ip()}";
        $fileSize = $type->isFile() ? (int) $request->file('encrypted_file')->getSize() : 0;

        if ($this->exceedsDailyUploadBudget($uploadBudgetKey, $fileSize)) {
            return response()->json([
                'error' => 'daily_limit_exceeded',
                'message' => __('messages.daily_limit_exceeded'),
            ], 429);
        }

        $expireAt = $this->calculateExpireAt($request->expiration());
        $token = $this->tokenService->generatePublicToken();

        $creatorEmail = $request->creatorEmail();

        $secretData = [
            'token' => $token,
            'type' => $type,
            'cipher_meta' => $request->cipherMeta(),
            'max_views' => $request->maxViews(),
            'expire_at' => $expireAt,
            'creator_email_hash' => $creatorEmail ? MagicLink::hashEmail($creatorEmail) : null,
        ];

        if ($type->isText()) {
            $secretData['ciphertext'] = $request->ciphertext();
        } else {
            $secretData['file_path'] = $this->storage->store($token, $request->file('encrypted_file'));
        }

        $secret = Secret::create($secretData);

        if ($fileSize > 0) {
            RateLimiter::increment($uploadBudgetKey, 86400, $fileSize);
        }

        $hasPassphrase = $request->hasPassphrase();
        $splitMode = $request->isSplitMode();

        defer(fn () => $this->trackCreationStats($secret, $hasPassphrase, $splitMode, $fileSize ?: null));

        return response()->json([
            'token' => $secret->token,
            'expire_at' => $expireAt->toIso8601String(),
        ], 201);
    }

    private function exceedsDailyUploadBudget(string $key, int $fileSize): bool
    {
        $budgetMb = Config::integer('secrets.daily_upload_mb_per_ip');

        if ($fileSize === 0) {
            return false;
        }

        if ($budgetMb <= 0) {
            return false;
        }

        // The database cache store returns counters as numeric strings
        $consumed = RateLimiter::attempts($key);
        $consumedBytes = is_numeric($consumed) ? (int) $consumed : 0;

        return $consumedBytes + $fileSize > $budgetMb * 1024 * 1024;
    }

    private function trackCreationStats(
        Secret $secret,
        bool $hasPassphrase,
        bool $splitMode,
        ?int $fileSize = null,
    ): void {
        if ($secret->type->isText()) {
            $this->stats->increment(StatsService::SECRETS_CREATED_TEXT);

            $textSize = strlen((string) $secret->ciphertext);
            if ($textSize > 0) {
                $this->stats->increment(StatsService::TOTAL_TEXT_SIZE_BYTES, $textSize);
            }
        } else {
            $this->stats->increment(StatsService::SECRETS_CREATED_FILE);
            if ($fileSize !== null && $fileSize > 0) {
                $this->stats->increment(StatsService::TOTAL_FILE_SIZE_BYTES, $fileSize);
            }
        }

        if ($hasPassphrase) {
            $this->stats->increment(StatsService::SECRETS_WITH_PASSPHRASE);
        }

        if ($secret->max_views !== null) {
            $this->stats->increment(StatsService::SECRETS_WITH_MAX_VIEWS);
        }

        if ($splitMode) {
            $this->stats->increment(StatsService::SECRETS_SPLIT_MODE);
        }

        $this->stats->incrementHeatmap(StatsService::HEATMAP_SECRETS_CREATED);
    }

    public function show(#[\SensitiveParameter] string $token): View
    {
        return view('secrets.show', ['token' => $token]);
    }

    public function fetch(#[\SensitiveParameter] string $token): JsonResponse
    {
        $secret = Secret::where('token', $token)->first();

        if (! $secret || ! $secret->isAccessible()) {
            return response()->json(['error' => 'not_found'], 404);
        }

        $willBeDestroyed = $secret->max_views !== null
            && $secret->read_count + 1 >= $secret->max_views;

        $data = [
            'type' => $secret->type->value,
            'cipher_meta' => $secret->cipher_meta,
            'will_be_destroyed' => $willBeDestroyed,
            'single_use' => $secret->max_views === 1,
            'previous_fetches' => $secret->fetch_count,
        ];

        if ($secret->type->isText()) {
            // The ciphertext leaves the server here: count it as a fetch
            $secret->recordFetch();
            $data['ciphertext'] = $secret->ciphertext;
        }
        // For files, the fetch is counted on download; metadata (filename, mime, size) is encrypted in the payload

        return response()->json($data);
    }

    public function confirmRead(Request $request, #[\SensitiveParameter] string $token): JsonResponse
    {
        $readId = $request->input('read_id');

        if ($readId !== null && (! is_string($readId) || ! preg_match(self::READ_ID_PATTERN, $readId))) {
            return response()->json(['error' => 'invalid_read_id'], 422);
        }

        return DB::transaction(function () use ($token, $readId) {
            $secret = Secret::where('token', $token)->lockForUpdate()->first();

            if (! $secret) {
                return response()->json(['error' => 'not_found'], 404);
            }

            $readKey = $readId !== null ? "secret-read:{$token}:{$readId}" : null;

            // A retried confirmation of an already counted read gets the same answer, even once destroyed
            if ($readKey !== null && Cache::has($readKey)) {
                return response()->json(['success' => true]);
            }

            if (! $secret->isAccessible()) {
                return response()->json(['error' => 'not_found'], 404);
            }

            if ($readKey !== null && ! Cache::add($readKey, true, self::READ_ID_TTL_SECONDS)) {
                return response()->json(['success' => true]);
            }

            $isFirstRead = $secret->first_read_at === null;
            $delaySeconds = $isFirstRead ? (int) $secret->created_at->diffInSeconds(now()) : null;

            $secret->incrementReadCount();

            $maxViewsReached = false;
            if ($secret->shouldBeDestroyed()) {
                if ($secret->type->isFile() && $secret->file_path) {
                    $this->storage->delete($secret->file_path);
                }
                $secret->destroyContent();
                $maxViewsReached = $secret->hasReachedMaxViews();
            }

            defer(function () use ($isFirstRead, $delaySeconds, $maxViewsReached) {
                $this->stats->increment(StatsService::SECRETS_READ);
                $this->stats->incrementHeatmap(StatsService::HEATMAP_SECRETS_READ);

                if ($isFirstRead && $delaySeconds !== null) {
                    $this->stats->trackFirstReadDelay($delaySeconds);
                }

                if ($maxViewsReached) {
                    $this->stats->increment(StatsService::SECRETS_MAX_VIEWS_REACHED);
                }
            });

            return response()->json(['success' => true]);
        });
    }

    public function download(#[\SensitiveParameter] string $token): StreamedResponse|Response
    {
        $secret = Secret::where('token', $token)->first();

        if (! $secret || ! $secret->type->isFile() || ! $secret->isAccessible()) {
            return response()->view('secrets.not-found', [], 404);
        }

        $filePath = $secret->file_path;

        if (! $filePath || ! $this->storage->exists($filePath)) {
            return response()->view('secrets.not-found', [], 404);
        }

        $previousFetches = $secret->fetch_count;

        // The encrypted file leaves the server here: count it as a fetch
        $secret->recordFetch();

        $response = $this->storage->download($filePath);
        $response->headers->set('X-Previous-Fetches', (string) $previousFetches);

        return $response;
    }

    private function calculateExpireAt(string $expiration): \Carbon\Carbon
    {
        $hours = Config::integer(
            'secrets.expirations.'.$expiration,
            Config::integer('secrets.expirations.'.Config::string('secrets.default_expiration'))
        );

        return now()->addHours($hours);
    }
}
