<?php

namespace App\Providers;

use App\Http\Responses\LogoutResponse;
use Filament\Auth\Http\Responses\Contracts\LogoutResponse as FilamentLogoutResponse;
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
        // Los paneles no tienen login propio: al salir se vuelve al login único.
        $this->app->bind(FilamentLogoutResponse::class, LogoutResponse::class);
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

        // Portal de verificación: consultas públicas, con un tope que frena el barrido de números.
        RateLimiter::for('verify', fn (Request $request) => Limit::perMinute(30)->by($request->ip()));

        // Solicitudes sobre obras: freno a envíos repetidos o automatizados (sin CAPTCHA de terceros).
        RateLimiter::for('requests', fn (Request $request) => [
            Limit::perMinute(3)->by('minuto:'.$request->ip()),
            Limit::perDay(20)->by('dia:'.$request->ip()),
        ]);
    }
}
