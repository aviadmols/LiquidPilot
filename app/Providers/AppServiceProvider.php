<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (config('app.env') !== 'production') {
            return;
        }
        URL::forceScheme('https');
        $rootUrl = null;
        if (! $this->app->runningInConsole()) {
            $request = request();
            if ($request && $request->getHost()) {
                $rootUrl = 'https://' . $request->getHost();
            }
        }
        if (! $rootUrl && config('app.url') && ! str_contains(config('app.url'), 'localhost')) {
            $rootUrl = config('app.url');
            // Ensure HTTPS in production (avoid Mixed Content when APP_URL is http://)
            if (str_starts_with($rootUrl, 'http://')) {
                $rootUrl = 'https://' . substr($rootUrl, 7);
            }
        }
        if ($rootUrl) {
            URL::forceRootUrl(rtrim($rootUrl, '/'));
        }
    }
}
