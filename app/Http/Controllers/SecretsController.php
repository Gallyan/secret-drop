<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSecretRequest;
use App\Models\Secret;
use App\Services\TokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\View\View;

class SecretsController extends Controller
{
    public function __construct(private TokenService $tokenService)
    {
    }

    public function create(): View
    {
        return view('secrets.create');
    }

    public function store(StoreSecretRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $expireAt = $this->calculateExpireAt($validated['expiration']);

        $secret = Secret::create([
            'token' => $this->tokenService->generatePublicToken(),
            'admin_token' => $this->tokenService->generateAdminToken(),
            'type' => $validated['type'],
            'cipher_meta' => $validated['cipher_meta'],
            'ciphertext' => $validated['ciphertext'] ?? null,
            'usage_unique' => $validated['usage_unique'] ?? true,
            'max_views' => $validated['max_views'] ?? null,
            'expire_at' => $expireAt,
            'creator_email' => $validated['creator_email'] ?? null,
        ]);

        return response()->json([
            'token' => $secret->token,
            'expire_at' => $expireAt->toIso8601String(),
        ], 201);
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
