<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        TrustProxies::at(Config::array('app.trusted_proxies'));

        Model::shouldBeStrict(! $this->app->isProduction());

        DB::prohibitDestructiveCommands($this->app->isProduction());

        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        $this->forceRootUrlFromAppUrl();

        RateLimiter::for('global', function (Request $request) {
            return Limit::perMinute(120)->by($request->ip());
        });

        RateLimiter::for('daily', function (Request $request) {
            return Limit::perDay(Config::integer('secrets.daily_limit_per_ip'))
                ->by($request->ip())
                ->response(function () {
                    return response()->json([
                        'error' => 'daily_limit_exceeded',
                        'message' => __('messages.daily_limit_exceeded'),
                    ], 429);
                });
        });

        Vite::useCspNonce(csp_nonce());
        Blade::directive('nonce', fn () => '<?php echo csp_nonce(); ?>');
    }

    /**
     * Builds absolute URLs (magic links, canonical, sitemap) from APP_URL instead of the request Host header.
     * Skipped in local so the dev server keeps working when APP_URL does not match its host and port.
     */
    private function forceRootUrlFromAppUrl(): void
    {
        if ($this->app->environment('local')) {
            return;
        }

        $appUrl = Config::string('app.url');

        if (! parse_url($appUrl, PHP_URL_SCHEME)) {
            return;
        }

        if (! parse_url($appUrl, PHP_URL_HOST)) {
            return;
        }

        URL::forceRootUrl($appUrl);
    }
}
