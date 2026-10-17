<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Catch lazy-loading (N+1 queries) and typos in attribute names during development and tests.
        Model::shouldBeStrict(! $this->app->isProduction());

        // Batch jobs per minute, so all workers together stay under the email rate limit.
        RateLimiter::for('campaign-emails', function () {
            $perJob = max(1, (int) config('postbag.batch_size'));

            return Limit::perMinute(max(1, intdiv((int) config('postbag.emails_per_minute'), $perJob)));
        });

        // API: per token when authenticated, per IP otherwise.
        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(120)->by($request->user()?->currentAccessToken()?->getKey() ?? $request->ip()));

        // Login: per email and per IP, against password guessing.
        RateLimiter::for('login', fn (Request $request) => [
            Limit::perMinute(5)->by(mb_strtolower((string) $request->input('email')).'|'.$request->ip()),
            Limit::perMinute(20)->by($request->ip()),
        ]);
    }
}
