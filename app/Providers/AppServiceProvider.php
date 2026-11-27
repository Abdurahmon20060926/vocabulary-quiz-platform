<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
    }

    public function boot(): void
    {
        if (app()->environment('production')) {
            URL::forceScheme('https');
        }

        RateLimiter::for('runs', function (Request $request) {
            $key = $request->user()
                ? 'runs:user:' . $request->user()->id
                : 'runs:guest:' . ($request->cookie('guest_token') ?? 'new') . ':' . $request->ip();

            return Limit::perMinute(30)->by($key);
        });

        RateLimiter::for('votes', function (Request $request) {
            return Limit::perMinute(30)->by('votes:' . $request->user()?->id);
        });
    }
}
