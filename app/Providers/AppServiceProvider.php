<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
        Inertia::share([
            //....
            'urlPrev'    => fn() => (url()->previous() !== route('login') && url()->previous() !== '' && url()->previous() !== url()->current()) ? url()->previous() : 'empty',

        ]);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        JsonResource::withoutWrapping();

        // Force HTTPS in production (so generated URLs / asset paths use https://)
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        // AI assistant per-user rate limit (10 messages / minute)
        RateLimiter::for('ai-chat', function ($request) {
            return Limit::perMinute(10)->by(optional($request->user())->id ?: $request->ip());
        });

        // Automatically check permissions using Spatie Permission package
        Gate::before(function ($user, $ability) {
            // Administrator has access to everything
            if ($user->hasRole('administrator')) {
                return true;
            }

            // Check if user has the specific permission through their role
            // Note: Must use hasPermission() instead of can() to avoid infinite recursion
            // since can() goes back through Gate which triggers Gate::before again
            if ($user->hasPermission($ability)) {
                return true;
            }

            return null;
        });

        // Define Gates
        Gate::define('admin', function ($user) {
            return $user->hasRole('administrator');
        });

        // Gate::define('create-scholar-profile', function ($user) {
        //     return $user->hasRole('administrator') || $user->hasRole('moderator');
        // });
    }
}
