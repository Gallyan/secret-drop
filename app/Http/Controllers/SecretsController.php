<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSecretRequest;
use App\Models\Secret;
use App\Services\SecretStorageService;
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

        $secretData = [
            'token' => $token,
            'admin_token' => $this->tokenService->generateAdminToken(),
            'type' => $validated['type'],
            'cipher_meta' => $validated['cipher_meta'],
            'usage_unique' => $validated['usage_unique'] ?? ($validated['type'] === 'text'),
            'max_views' => $validated['max_views'] ?? null,
            'expire_at' => $expireAt,
            'creator_email' => $validated['creator_email'] ?? null,
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

        return response()->json([
            'token' => $secret->token,
            'expire_at' => $expireAt->toIso8601String(),
        ], 201);
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

        $secret->incrementReadCount();

        if ($secret->shouldBeDestroyed()) {
            if ($secret->type === 'file' && $secret->file_path) {
                $this->storage->delete($secret->file_path);
            }
            $secret->destroyContent();
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
