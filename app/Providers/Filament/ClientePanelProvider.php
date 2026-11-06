<?php

namespace App\Providers\Filament;

use App\Filament\Cliente\Resources\MyRequests\MyRequestResource;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

/**
 * «Mi cuenta» del cliente: el estado de sus solicitudes. Entra por el login único
 * (/ingresar) y necesita su correo verificado.
 */
class ClientePanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('cliente')
            ->path('mi-cuenta')
            ->tap(fn (Panel $panel) => ArtHubBranding::apply($panel, 'Mi cuenta'))
            ->emailVerification()
            ->discoverResources(in: app_path('Filament/Cliente/Resources'), for: 'App\Filament\Cliente\Resources')
            ->homeUrl(fn () => MyRequestResource::getUrl())
            ->pages([])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
