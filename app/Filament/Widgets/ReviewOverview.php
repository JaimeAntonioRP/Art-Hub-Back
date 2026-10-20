<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Artists\ArtistResource;
use App\Filament\Resources\Artworks\ArtworkResource;
use App\Filament\Resources\Users\UserResource;
use App\Models\Artist;
use App\Models\Artwork;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/** Indicadores operativos del panel admin (§16). No es analítica comercial (§29.7). */
class ReviewOverview extends StatsOverviewWidget
{
    /** Son conteos baratos: se muestran al abrir el escritorio, sin «Cargando…». */
    protected static bool $isLazy = false;

    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $byStatus = Artwork::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
        $pendingArtists = Artist::where('status', Artist::STATUS_PENDIENTE)->count();

        return [
            Stat::make('Obras por revisar', $byStatus[Artwork::STATUS_EN_REVISION] ?? 0)
                ->description('Enviadas por los artistas')
                ->color(($byStatus[Artwork::STATUS_EN_REVISION] ?? 0) ? 'info' : 'gray')
                ->url(ArtworkResource::getUrl('index', ['filters' => ['status' => ['value' => Artwork::STATUS_EN_REVISION]]])),
            Stat::make('Perfiles por revisar', $pendingArtists)
                ->description('Artistas pendientes de aprobación')
                ->color($pendingArtists ? 'info' : 'gray')
                ->url(ArtistResource::getUrl('index', ['filters' => ['status' => ['value' => Artist::STATUS_PENDIENTE]]])),
            Stat::make('Obras publicadas', $byStatus[Artwork::STATUS_PUBLICADO] ?? 0)
                ->description('Visibles en el catálogo'),
            Stat::make('Cuentas activas', User::where('is_active', true)->count())
                ->url(UserResource::getUrl('index')),
        ];
    }
}
