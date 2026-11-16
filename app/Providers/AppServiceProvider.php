<?php

namespace App\Providers;

use App\Models\Application;
use App\Models\IssuedDocument;
use App\Policies\ApplicationPolicy;
use App\Policies\IssuedDocumentPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
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
        Gate::policy(Application::class, ApplicationPolicy::class);
        Gate::policy(IssuedDocument::class, IssuedDocumentPolicy::class);

        RateLimiter::for('password-otp', function (Request $request): Limit {
            $key = hash_hmac(
                'sha256',
                mb_strtolower((string) $request->input('email')).'|'.$request->ip(),
                (string) config('app.key'),
            );

            return Limit::perMinutes(15, 3)->by($key);
        });
    }
}
