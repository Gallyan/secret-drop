<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSecretRequest;
use App\Models\Secret;
use App\Services\SecretStorageService;
use App\Services\StatsService;
use App\Services\TokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SecretsController extends Controller
{
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
        $validated = $request->validated();

        $expireAt = $this->calculateExpireAt($validated['expiration']);
        $token = $this->tokenService->generatePublicToken();

        $creatorEmail = $validated['creator_email'] ?? null;

        $secretData = [
            'token' => $token,
            'admin_token' => $this->tokenService->generateAdminToken(),
            'type' => $validated['type'],
            'cipher_meta' => $validated['cipher_meta'],
            'usage_unique' => $validated['usage_unique'] ?? ($validated['type'] === 'text'),
            'max_views' => $validated['max_views'] ?? null,
            'expire_at' => $expireAt,
            'creator_email_hash' => $creatorEmail ? hash('sha256', strtolower(trim($creatorEmail))) : null,
        ];

        if ($validated['type'] === 'text') {
            $secretData['ciphertext'] = $validated['ciphertext'];
        } else {
            $file = $request->file('encrypted_file');
            $filePath = $this->storage->store($token, $file);

            $secretData['file_path'] = $filePath;
            $secretData['filename'] = $validated['filename'];
            $secretData['mime'] = $validated['mime'];
            $secretData['size'] = (int) $validated['size'];
        }

        $secret = Secret::create($secretData);

        $this->trackCreationStats($secret, $validated);

        return response()->json([
            'token' => $secret->token,
            'expire_at' => $expireAt->toIso8601String(),
        ], 201);
    }

    private function trackCreationStats(Secret $secret, array $validated): void
    {
        if ($secret->type === 'text') {
            $this->stats->increment(StatsService::SECRETS_CREATED_TEXT);
        } else {
            $this->stats->increment(StatsService::SECRETS_CREATED_FILE);
            $this->stats->increment(StatsService::TOTAL_FILE_SIZE_BYTES, $secret->size ?? 0);
        }

        if (! empty($validated['cipher_meta']['has_passphrase'])) {
            $this->stats->increment(StatsService::SECRETS_WITH_PASSPHRASE);
        }

        if ($secret->usage_unique) {
            $this->stats->increment(StatsService::SECRETS_SINGLE_USE);
        }

        if ($secret->max_views !== null) {
            $this->stats->increment(StatsService::SECRETS_WITH_MAX_VIEWS);
        }

        $this->stats->incrementHeatmap(StatsService::HEATMAP_SECRETS_CREATED);
    }

    public function show(string $token): View
    {
        return view('secrets.show', ['token' => $token]);
    }

    public function fetch(string $token): JsonResponse
    {
        $secret = Secret::where('token', $token)->first();

        if (! $secret) {
            return response()->json(['error' => 'not_found'], 404);
        }

        if (! $secret->isAccessible()) {
            return response()->json([
                'error' => 'unavailable',
                'reason' => $this->getInaccessibleReason($secret),
            ], 410);
        }

        $willBeDestroyed = $secret->usage_unique
            || ($secret->max_views !== null && $secret->read_count + 1 >= $secret->max_views);

        $data = [
            'type' => $secret->type,
            'cipher_meta' => $secret->cipher_meta,
            'will_be_destroyed' => $willBeDestroyed,
        ];

        if ($secret->type === 'text') {
            $data['ciphertext'] = $secret->ciphertext;
        } else {
            $data['filename'] = $secret->filename;
            $data['mime'] = $secret->mime;
            $data['size'] = $secret->size;
        }

        return response()->json($data);
    }

    public function confirmRead(string $token): JsonResponse
    {
        $secret = Secret::where('token', $token)->first();

        if (! $secret) {
            return response()->json(['error' => 'not_found'], 404);
        }

        if (! $secret->isAccessible()) {
            return response()->json([
                'error' => 'unavailable',
                'reason' => $this->getInaccessibleReason($secret),
            ], 410);
        }

        $isFirstRead = $secret->first_read_at === null;
        $createdAt = $secret->created_at;

        $secret->incrementReadCount();
        $this->stats->increment(StatsService::SECRETS_READ);
        $this->stats->incrementHeatmap(StatsService::HEATMAP_SECRETS_READ);

        if ($isFirstRead) {
            $delaySeconds = (int) $createdAt->diffInSeconds(now());
            $this->stats->trackFirstReadDelay($delaySeconds);
        }

        if ($secret->shouldBeDestroyed()) {
            if ($secret->type === 'file' && $secret->file_path) {
                $this->storage->delete($secret->file_path);
            }
            $secret->destroyContent();

            if ($secret->hasReachedMaxViews()) {
                $this->stats->increment(StatsService::SECRETS_MAX_VIEWS_REACHED);
            }
        }

        return response()->json(['success' => true]);
    }

    public function download(string $token): StreamedResponse|Response
    {
        $secret = Secret::where('token', $token)->first();

        if (! $secret || $secret->type !== 'file') {
            return response()->view('secrets.not-found', [], 404);
        }

        if (! $secret->isAccessible()) {
            return response('Secret indisponible', 410);
        }

        if (! $secret->file_path || ! $this->storage->exists($secret->file_path)) {
            return response('Fichier introuvable', 404);
        }

        return $this->storage->download($secret->file_path);
    }

    public function revoke(string $adminToken): JsonResponse
    {
        $secret = Secret::where('admin_token', $adminToken)->first();

        if (! $secret) {
            return response()->json(['error' => 'not_found'], 404);
        }

        if ($secret->isRevoked()) {
            return response()->json(['error' => 'already_revoked'], 409);
        }

        if ($secret->type === 'file' && $secret->file_path) {
            $this->storage->delete($secret->file_path);
        }

        $secret->revoked_at = now();
        $secret->destroyContent();

        return response()->json(['success' => true]);
    }

    private function getInaccessibleReason(Secret $secret): string
    {
        if ($secret->isRevoked()) {
            return 'revoked';
        }

        if ($secret->isExpired()) {
            return 'expired';
        }

        if ($secret->hasBeenRead()) {
            return 'already_read';
        }

        if ($secret->hasReachedMaxViews()) {
            return 'max_views';
        }

        return 'unknown';
    }

    private function calculateExpireAt(string $expiration): \Carbon\Carbon
    {
        return match ($expiration) {
            '1h' => now()->addHour(),
            '1d' => now()->addDay(),
            '7d' => now()->addDays(7),
            '30d' => now()->addDays(30),
            default => now()->addDays(7),
        };
    }
}
