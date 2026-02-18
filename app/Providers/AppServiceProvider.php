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
        // Use the request host so assets never point to localhost when served from Railway/etc.
        $request = request();
        if ($request && $request->getHost()) {
            URL::forceRootUrl('https://' . $request->getHost());
        } elseif (config('app.url')) {
            $url = config('app.url');
            if (!str_contains($url, 'localhost')) {
                URL::forceRootUrl($url);
            }
        }
    }
}
