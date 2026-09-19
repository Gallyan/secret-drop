<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExtendSecretRequest;
use App\Http\Requests\RequestAdminAccessRequest;
use App\Mail\MagicLinkMail;
use App\Models\MagicLink;
use App\Models\Secret;
use App\Services\SecretStorageService;
use App\Services\StatsService;
use App\Services\TokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

use function Illuminate\Support\defer;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

class AdminController extends Controller
{
    use Concerns\HasSessionAuth;

    protected const SESSION_KEY = 'admin_email_hash';
    protected const SESSION_EXPIRES_KEY = 'admin_expires_at';

    private function sessionTtl(): int
    {
        return Config::integer('secrets.admin_session_ttl');
    }

    public function __construct(
        private TokenService $tokenService,
        private SecretStorageService $storage,
        private StatsService $stats,
    ) {
    }

    public function index(Request $request): View|RedirectResponse
    {
        if ($this->getSessionAuth($request)) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.index');
    }

    public function requestAccess(RequestAdminAccessRequest $request): RedirectResponse
    {
        $email = $request->email();
        $locale = app()->getLocale();

        defer(fn () => $this->sendMagicLinkIfOwner($email, $locale));

        return redirect()->route('admin.accessSent');
    }

    /**
     * Runs after the response is sent, so known, unknown and rate-limited
     * emails all get the same response in the same time.
     */
    private function sendMagicLinkIfOwner(string $email, string $locale): void
    {
        $emailHash = MagicLink::hashEmail($email);

        if (! Secret::where('creator_email_hash', $emailHash)->exists()) {
            return;
        }

        if ($this->recipientLimitReached("magic-link-recipient:{$emailHash}")) {
            return;
        }

        $tokenData = $this->tokenService->generateMagicLinkToken();
        $verifyUrl = route('admin.verify', ['locale' => $locale, 'token' => $tokenData['token']]);

        $issued = MagicLink::issueExclusively(
            $emailHash,
            $tokenData['hash'],
            function () use ($email, $locale, $verifyUrl): void {
                Mail::to($email)->locale($locale)->send(new MagicLinkMail($verifyUrl));
            },
        );

        if (! $issued) {
            return;
        }

        $this->stats->incrementDailyAndHourly(StatsService::MAGIC_LINKS_REQUESTED);
    }

    private function recipientLimitReached(string $key): bool
    {
        $maxPerHour = Config::integer('secrets.magic_link_max_per_recipient_per_hour');

        if ($maxPerHour <= 0) {
            return false;
        }

        if (RateLimiter::tooManyAttempts($key, $maxPerHour)) {
            return true;
        }

        RateLimiter::hit($key, 3600);

        return false;
    }

    public function verify(Request $request, string $locale, #[\SensitiveParameter] string $token): View|RedirectResponse
    {
        $magicLink = MagicLink::findByToken($token);

        if (! $magicLink) {
            return view('admin.invalid-link');
        }

        if ($magicLink->isSuperAdmin()) {
            return view('admin.invalid-link');
        }

        if (! $magicLink->isValid()) {
            return view('admin.invalid-link');
        }

        if ($request->isMethod('GET')) {
            return view('admin.verify-confirm', [
                'token' => $token,
            ]);
        }

        if (! $magicLink->markAsUsed()) {
            return view('admin.invalid-link');
        }

        defer(fn () => $this->stats->incrementDailyAndHourly(StatsService::MAGIC_LINKS_USED));

        $request->session()->regenerate();
        $request->session()->put(self::SESSION_KEY, $magicLink->email_hash);
        $request->session()->put(self::SESSION_EXPIRES_KEY, now()->addMinutes($this->sessionTtl())->timestamp);

        return redirect()->route('admin.dashboard');
    }

    public function dashboard(Request $request): View|RedirectResponse
    {
        $emailHash = $this->getSessionAuth($request);

        if (! $emailHash) {
            return redirect()->route('admin.index');
        }

        $this->renewSessionExpiry($request);

        $secrets = Secret::where('creator_email_hash', $emailHash)
            ->orderByDesc('created_at')
            ->paginate(5);

        return view('admin.dashboard', ['secrets' => $secrets]);
    }

    public function poll(Request $request): JsonResponse
    {
        $emailHash = $this->getSessionAuth($request);

        if (! $emailHash) {
            return response()->json(['error' => 'unauthenticated'], 401);
        }

        $page = $request->integer('page', 1);
        $knownIds = array_filter(explode(',', $request->string('known')->toString()));

        $secrets = Secret::where('creator_email_hash', $emailHash)
            ->orderByDesc('created_at')
            ->paginate(5, ['*'], 'page', $page);

        $newCardsHtml = [];
        foreach ($secrets as $secret) {
            if (! empty($knownIds) && ! in_array($secret->id, $knownIds, true)) {
                $newCardsHtml[$secret->id] = view('admin.secret-card', ['secret' => $secret])->render();
            }
        }

        return response()->json([
            'total' => $secrets->total(),
            'secrets' => $secrets->map(fn (Secret $secret) => [
                'id' => $secret->id,
                'read_count' => $secret->read_count,
                'fetch_count' => $secret->fetch_count,
                'max_views' => $secret->max_views,
                'first_read_at' => $secret->first_read_at?->toIso8601String(),
                'expire_at' => $secret->expire_at?->toIso8601String(),
                'is_revoked' => $secret->isRevoked(),
                'is_expired' => $secret->isExpired(),
                'has_reached_max_views' => $secret->hasReachedMaxViews(),
                'is_accessible' => $secret->isAccessible(),
            ]),
            'new_cards_html' => $newCardsHtml,
        ]);
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.index');
    }

    public function revoke(Request $request, string $locale, string $id): JsonResponse
    {
        $emailHash = $this->getSessionAuth($request);

        if (! $emailHash) {
            return response()->json(['error' => 'unauthenticated'], 401);
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

        if ($secret->hasReachedMaxViews()) {
            return response()->json(['error' => 'already_consumed'], 409);
        }

        if ($secret->type->isFile() && $secret->file_path) {
            $this->storage->delete($secret->file_path);
        }

        $secret->revoked_at = now();
        $secret->destroyContent();

        defer(fn () => $this->stats->increment(StatsService::SECRETS_REVOKED));

        return response()->json(['success' => true]);
    }

    public function extend(ExtendSecretRequest $request, string $locale, string $id): JsonResponse
    {
        $emailHash = $this->getSessionAuth($request);

        if (! $emailHash) {
            return response()->json(['error' => 'unauthenticated'], 401);
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

        if ($secret->isExpired()) {
            return response()->json(['error' => 'expired'], 409);
        }

        if ($secret->hasReachedMaxViews()) {
            return response()->json(['error' => 'already_consumed'], 409);
        }

        $baseDate = $secret->expire_at ?? now();
        $latestExpireAt = $secret->created_at->copy()->addHours($this->longestExpirationHours());
        $newExpireAt = $baseDate->copy()->addHours($request->hours())->min($latestExpireAt);

        if ($newExpireAt->lessThanOrEqualTo($baseDate)) {
            return response()->json(['error' => 'max_expiry_reached'], 409);
        }

        $secret->expire_at = $newExpireAt;
        $secret->save();

        defer(fn () => $this->stats->incrementDailyAndHourly(StatsService::SECRETS_EXTENDED));

        return response()->json([
            'success' => true,
            'expire_at' => $secret->expire_at->toIso8601String(),
        ]);
    }

    private function longestExpirationHours(): int
    {
        $expirations = array_filter(Config::array('secrets.expirations'), is_int(...));

        if ($expirations === []) {
            return 0;
        }

        return max($expirations);
    }
}
