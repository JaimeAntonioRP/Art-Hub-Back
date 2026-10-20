<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
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
        // El enlace de recuperación que pide la API abre la página del frontend.
        // Los paneles de Filament generan su propio enlace al panel.
        ResetPassword::createUrlUsing(fn ($user, string $token) => config('app.frontend_url')
            .'/restablecer?'.http_build_query(['token' => $token, 'email' => $user->getEmailForPasswordReset()]));

        // Login, registro y recuperación: 6 intentos por minuto por IP y correo.
        RateLimiter::for('auth', fn (Request $request) => Limit::perMinute(6)
            ->by(strtolower((string) $request->input('email')).'|'.$request->ip()));
    }
}
