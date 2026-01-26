<?php

namespace App\Http\Controllers;

use App\Http\Requests\RequestSuperAdminAccessRequest;
use App\Mail\SuperAdminMagicLinkMail;
use App\Models\MagicLink;
use App\Services\StatsService;
use App\Services\TokenService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class SuperAdminController extends Controller
{
    private const SESSION_KEY = 'super_admin_verified';

    public function __construct(
        private TokenService $tokenService,
        private StatsService $stats,
    ) {
    }

    public function index(Request $request): View|RedirectResponse
    {
        if ($request->session()->get(self::SESSION_KEY)) {
            return redirect()->route('superadmin.dashboard');
        }

        return view('superadmin.index');
    }

    public function requestAccess(RequestSuperAdminAccessRequest $request): View
    {
        $email = strtolower(trim($request->validated('email')));
        $superAdminEmail = strtolower(trim(config('app.super_admin_email', '')));

        if ($email === $superAdminEmail && $superAdminEmail !== '') {
            $tokenData = $this->tokenService->generateMagicLinkToken();

            MagicLink::create([
                'email_hash' => 'superadmin',
                'token_hash' => $tokenData['hash'],
                'expire_at' => now()->addMinutes(5),
            ]);

            $url = route('superadmin.verify', ['token' => $tokenData['token']]);
            Mail::to($email)
                ->locale(app()->getLocale())
                ->send(new SuperAdminMagicLinkMail($url));

            $this->stats->increment(StatsService::MAGIC_LINKS_REQUESTED);
        }

        return view('superadmin.access-sent');
    }

    public function verify(Request $request, string $token): View|RedirectResponse
    {
        $magicLink = MagicLink::findByToken($token);

        if (! $magicLink || $magicLink->email_hash !== 'superadmin') {
            return view('superadmin.invalid-link');
        }

        if (! $magicLink->isValid()) {
            return view('superadmin.invalid-link');
        }

        $magicLink->markAsUsed();
        $this->stats->increment(StatsService::MAGIC_LINKS_USED);

        $request->session()->regenerate();
        $request->session()->put(self::SESSION_KEY, true);
        $request->session()->put('super_admin_expires_at', now()->addHours(2)->timestamp);

        return redirect()->route('superadmin.dashboard');
    }

    private const VALID_PERIODS = ['7d', '30d', '90d', '1y', 'all'];

    public function dashboard(Request $request): View|RedirectResponse
    {
        if (! $this->isAuthenticated($request)) {
            return redirect()->route('superadmin.index');
        }

        $period = $request->input('period', '30d');
        if (! in_array($period, self::VALID_PERIODS, true)) {
            $period = '30d';
        }
        $stats = $this->stats->getStats($period);
        $startDate = $period === 'all' ? null : $stats['start_date'];
        $heatmapCreated = $this->stats->getHeatmap(StatsService::HEATMAP_SECRETS_CREATED);
        $heatmapRead = $this->stats->getHeatmap(StatsService::HEATMAP_SECRETS_READ);
        $avgFirstReadDelay = $this->stats->getAverageFirstReadDelay($startDate);
        $currentDiskUsage = $this->stats->getCurrentDiskUsage();

        return view('superadmin.dashboard', [
            'stats' => $stats,
            'period' => $period,
            'heatmapCreated' => $heatmapCreated,
            'heatmapRead' => $heatmapRead,
            'avgFirstReadDelay' => $avgFirstReadDelay,
            'currentDiskUsage' => $currentDiskUsage,
        ]);
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('superadmin.index');
    }

    private function isAuthenticated(Request $request): bool
    {
        if (! $request->session()->get(self::SESSION_KEY)) {
            return false;
        }

        $expiresAt = $request->session()->get('super_admin_expires_at');

        if ($expiresAt && $expiresAt < now()->timestamp) {
            $request->session()->forget(self::SESSION_KEY);
            $request->session()->forget('super_admin_expires_at');

            return false;
        }

        return true;
    }
}
