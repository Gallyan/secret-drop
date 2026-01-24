<?php

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('csp-nonce', fn () => Str::random(32));
    }

    public function boot(): void
    {
        Vite::useCspNonce(csp_nonce());
        Blade::directive('nonce', fn () => '<?php echo csp_nonce(); ?>');
    }
}
