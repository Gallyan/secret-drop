<?php

namespace App\Http\Controllers;

use App\Mail\MagicLinkMail;
use App\Models\MagicLink;
use App\Models\Secret;
use App\Services\SecretStorageService;
use App\Services\StatsService;
use App\Services\TokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class AdminController extends Controller
{
    private const SESSION_KEY = 'admin_email_hash';

    public function __construct(
        private TokenService $tokenService,
        private SecretStorageService $storage,
        private StatsService $stats,
    ) {
    }

    public function index(Request $request): View|RedirectResponse
    {
        if ($request->session()->has(self::SESSION_KEY)) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.index');
    }

    public function requestAccess(Request $request): View
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        $emailHash = MagicLink::hashEmail($validated['email']);

        $hasSecrets = Secret::where('creator_email_hash', $emailHash)->exists();

        if (! $hasSecrets) {
            return view('admin.access-sent');
        }

        $tokenData = $this->tokenService->generateMagicLinkToken();

        MagicLink::create([
            'email_hash' => $emailHash,
            'token_hash' => $tokenData['hash'],
            'expire_at' => now()->addMinutes(5),
        ]);

        $verifyUrl = route('admin.verify', ['token' => $tokenData['token']]);
        Mail::to($validated['email'])->send(new MagicLinkMail($verifyUrl));

        $this->stats->increment(StatsService::MAGIC_LINKS_REQUESTED);

        return view('admin.access-sent');
    }

    public function verify(Request $request, string $token): View|RedirectResponse
    {
        $magicLink = MagicLink::findByToken($token);

        if (! $magicLink) {
            return view('admin.invalid-link');
        }

        if (! $magicLink->isValid()) {
            return view('admin.invalid-link');
        }

        $magicLink->markAsUsed();
        $this->stats->increment(StatsService::MAGIC_LINKS_USED);

        $request->session()->put(self::SESSION_KEY, $magicLink->email_hash);

        return redirect()->route('admin.dashboard');
    }

    public function dashboard(Request $request): View|RedirectResponse
    {
        $emailHash = $request->session()->get(self::SESSION_KEY);

        if (! $emailHash) {
            return redirect()->route('admin.index');
        }

        $secrets = Secret::where('creator_email_hash', $emailHash)
            ->orderByDesc('created_at')
            ->get();

        return view('admin.dashboard', ['secrets' => $secrets]);
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->forget(self::SESSION_KEY);

        return redirect()->route('admin.index');
    }

    public function revoke(Request $request, string $id): JsonResponse
    {
        $emailHash = $request->session()->get(self::SESSION_KEY);

        if (! $emailHash) {
            return response()->json(['error' => 'unauthorized'], 403);
        }

        $secret = Secret::where('id', $id)
            ->where('creator_email_hash', $emailHash)
            ->first();

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

        $this->stats->increment(StatsService::SECRETS_REVOKED);

        return response()->json(['success' => true]);
    }

    public function extend(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'days' => ['required', 'integer', 'min:1', 'max:30'],
        ]);

        $emailHash = $request->session()->get(self::SESSION_KEY);

        if (! $emailHash) {
            return response()->json(['error' => 'unauthorized'], 403);
        }

        $secret = Secret::where('id', $id)
            ->where('creator_email_hash', $emailHash)
            ->first();

        if (! $secret) {
            return response()->json(['error' => 'not_found'], 404);
        }

        if ($secret->isRevoked()) {
            return response()->json(['error' => 'revoked'], 409);
        }

        $baseDate = $secret->expire_at->isPast() ? now() : $secret->expire_at;
        $secret->expire_at = $baseDate->addDays($validated['days']);
        $secret->save();

        $this->stats->increment(StatsService::SECRETS_EXTENDED);

        return response()->json([
            'success' => true,
            'expire_at' => $secret->expire_at->toIso8601String(),
        ]);
    }
}
