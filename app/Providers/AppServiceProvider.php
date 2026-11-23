<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
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
        RateLimiter::for('login', function (Request $request): Limit {
            $email = Str::transliterate(Str::lower($request->string('email')));

            return Limit::perMinute(5)->by('two-factor-login:'.$email.'|'.$request->ip());
        });

        RateLimiter::for('login-code', function (Request $request): Limit {
            $userId = $request->session()->get('auth.login.pending_user_id', 'none');

            return Limit::perMinute(5)->by('login-code:'.$request->ip().'|'.$userId);
        });
    }
}
