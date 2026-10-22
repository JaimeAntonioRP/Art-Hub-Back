<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\SiteContent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Login único (§5): una sola página para todos los roles. Cada usuario sigue a su lugar:
 * el admin a /admin, el artista a /artista y el comprador al sitio público.
 * Los paneles de Filament no tienen login propio: mandan aquí a quien no inició sesión.
 */
class LoginController extends Controller
{
    private const MAX_ATTEMPTS = 6;

    public function show(Request $request): View|RedirectResponse
    {
        if ($user = $request->user()) {
            return redirect()->away($user->homeUrl());
        }

        $brand = SiteContent::forApi()['brand'];
        $logo = $brand['logo_url'] && str_starts_with($brand['logo_url'], '/')
            ? config('app.frontend_url').$brand['logo_url'] // rutas del sitio público
            : $brand['logo_url'];

        return view('auth.login', [
            'brand'    => $brand['name'],
            'tagline'  => $brand['tagline'],
            'logo'     => $logo,
            'frontend' => config('app.frontend_url'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ], [
            'email.required'    => 'Escribe tu correo.',
            'email.email'       => 'Revisa el correo: parece incompleto.',
            'password.required' => 'Escribe tu contraseña.',
        ]);

        // Freno a la fuerza bruta: 6 intentos fallidos por minuto por correo e IP.
        $key = 'login:'.strtolower($credentials['email']).'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            throw ValidationException::withMessages([
                'email' => 'Demasiados intentos. Vuelve a probar en '.RateLimiter::availableIn($key).' segundos.',
            ]);
        }

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::hit($key, 60);
            throw ValidationException::withMessages(['email' => 'El correo o la contraseña no son correctos.']);
        }
        RateLimiter::clear($key);

        /** @var User $user */
        $user = Auth::user();

        if (! $user->is_active) {
            Auth::logout();
            throw ValidationException::withMessages(['email' => 'Tu cuenta está desactivada. Escríbenos si crees que es un error.']);
        }

        $request->session()->regenerate();

        // Si venía de un enlace (por ejemplo, el de un correo), vuelve ahí, siempre que sea de su panel.
        $intended = $request->session()->pull('url.intended');
        $path = $intended ? parse_url($intended, PHP_URL_PATH) : null;
        $sameHost = $intended && parse_url($intended, PHP_URL_HOST) === $request->getHost();

        return redirect()->away($sameHost && $path && $user->mayVisitPath($path) ? $intended : $user->homeUrl());
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
