<?php

namespace App\Http\Controllers;

use App\Http\Requests\RequestSuperAdminAccessRequest;
use App\Mail\SuperAdminMagicLinkMail;
use App\Models\MagicLink;
use App\Services\StatsService;
use App\Services\TokenService;
use App\Support\StatsPages;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

use function Illuminate\Support\defer;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

class SuperAdminController extends Controller
{
    use Concerns\HasSessionAuth;

    protected const SESSION_KEY = 'super_admin_verified';
    protected const SESSION_EXPIRES_KEY = 'super_admin_expires_at';
    private const VALID_PERIODS = ['today', '7d', '30d', '90d', '1y', 'all'];

    private function sessionTtl(): int
    {
        return Config::integer('secrets.super_admin_session_ttl');
    }

    public function __construct(
        private TokenService $tokenService,
        private StatsService $stats,
    ) {
    }

    public function index(Request $request): View|RedirectResponse
    {
        if ($this->getSessionAuth($request)) {
            return redirect()->route('superadmin.dashboard');
        }

        return view('superadmin.index');
    }

    public function requestAccess(RequestSuperAdminAccessRequest $request): RedirectResponse
    {
        $email = strtolower(trim($request->email()));
        $locale = app()->getLocale();

        defer(fn () => $this->sendMagicLinkIfSuperAdmin($email, $locale));

        return redirect()->route('superadmin.accessSent');
    }

    /**
     * Runs after the response is sent, so matching, non-matching and
     * rate-limited emails all get the same response in the same time.
     */
    private function sendMagicLinkIfSuperAdmin(string $email, string $locale): void
    {
        $superAdminEmail = strtolower(trim(config_string('app.super_admin_email')));

        if ($superAdminEmail === '') {
            return;
        }

        if (! hash_equals($superAdminEmail, $email)) {
            return;
        }

        if ($this->recipientLimitReached('magic-link-recipient:super-admin')) {
            return;
        }

        $tokenData = $this->tokenService->generateMagicLinkToken();
        $url = route('superadmin.verify', ['locale' => $locale, 'token' => $tokenData['token']]);

        $issued = MagicLink::issueExclusively(
            MagicLink::SUPER_ADMIN_EMAIL_HASH,
            $tokenData['hash'],
            function () use ($email, $locale, $url): void {
                Mail::to($email)->locale($locale)->send(new SuperAdminMagicLinkMail($url));
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

        if (! $magicLink || ! $magicLink->isSuperAdmin()) {
            return view('superadmin.invalid-link');
        }

        if (! $magicLink->isValid()) {
            return view('superadmin.invalid-link');
        }

        if ($request->isMethod('GET')) {
            return view('superadmin.verify-confirm', [
                'token' => $token,
            ]);
        }

        if (! $magicLink->markAsUsed()) {
            return view('superadmin.invalid-link');
        }

        defer(fn () => $this->stats->incrementDailyAndHourly(StatsService::MAGIC_LINKS_USED));

        $request->session()->regenerate();
        $request->session()->put(self::SESSION_KEY, true);
        $request->session()->put(self::SESSION_EXPIRES_KEY, now()->addMinutes($this->sessionTtl())->timestamp);

        return redirect()->route('superadmin.dashboard');
    }

    public function dashboard(Request $request): View|RedirectResponse
    {
        if (! $this->getSessionAuth($request)) {
            return redirect()->route('superadmin.index');
        }

        $this->renewSessionExpiry($request);

        $data = $this->collectStats($request);
        $data['pageLabels'] = StatsPages::labels();

        return view('superadmin.dashboard', $data);
    }

    public function poll(Request $request): JsonResponse
    {
        if (! $this->getSessionAuth($request)) {
            return response()->json(['error' => 'unauthenticated'], 401);
        }

        return response()->json($this->collectStats($request));
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('superadmin.index');
    }

    /**
     * @return array<string, mixed>
     */
    private function collectStats(Request $request): array
    {
        $period = $request->input('period', '30d');

        if (! in_array($period, self::VALID_PERIODS, true)) {
            $period = '30d';
        }

        $stats = $this->stats->getStats($period);
        $startDate = $period === 'all' ? null : $stats['start_date'];
        $today = now()->toDateString();

        return [
            'stats' => $stats,
            'period' => $period,
            'hourly' => $period === 'today' ? [
                'created' => $this->stats->getHourlyBreakdown(StatsService::HEATMAP_SECRETS_CREATED, $today),
                'read' => $this->stats->getHourlyBreakdown(StatsService::HEATMAP_SECRETS_READ, $today),
                'magic_links_requested' => $this->stats->getHourlyBreakdown(StatsService::MAGIC_LINKS_REQUESTED, $today),
                'magic_links_used' => $this->stats->getHourlyBreakdown(StatsService::MAGIC_LINKS_USED, $today),
                'secrets_extended' => $this->stats->getHourlyBreakdown(StatsService::SECRETS_EXTENDED, $today),
                'errors_4xx' => $this->stats->getHourlyBreakdown(StatsService::HTTP_ERRORS_4XX, $today),
                'errors_5xx' => $this->stats->getHourlyBreakdown(StatsService::HTTP_ERRORS_5XX, $today),
            ] : null,
            'heatmapCreated' => $this->stats->getHeatmap(StatsService::HEATMAP_SECRETS_CREATED, $startDate),
            'heatmapRead' => $this->stats->getHeatmap(StatsService::HEATMAP_SECRETS_READ, $startDate),
            'avgFirstReadDelay' => $this->stats->getAverageFirstReadDelay($startDate),
            'currentDiskUsage' => $this->stats->getCurrentDiskUsage(),
            'pageviews' => $this->stats->getPageviews($startDate),
            'readRate' => $this->stats->getReadRate($startDate),
            'creatorConcentration' => $this->stats->getCreatorConcentration(),
            'systemHealth' => $this->stats->getSystemHealth(),
            'referrers' => $this->stats->getReferrers($startDate),
            'botStats' => $this->stats->getBotStats($startDate),
            'deviceStats' => $this->stats->getDeviceStats($startDate),
            'errorStats' => [
                'total_4xx' => $stats['totals'][StatsService::HTTP_ERRORS_4XX] ?? 0,
                'total_5xx' => $stats['totals'][StatsService::HTTP_ERRORS_5XX] ?? 0,
                'by_code' => $this->stats->getErrorCodeBreakdown($startDate),
                'by_route' => $this->stats->getErrorRoutes($startDate),
            ],
            'responseTime' => $this->stats->getResponseTimeP95($startDate),
            'avgSecretSize' => $this->stats->getAverageSecretSize($startDate),
        ];
    }
}
