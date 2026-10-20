<?php

namespace App\Filament\Artista\Widgets;

use App\Filament\Artista\Pages\MiPerfil;
use App\Filament\Artista\Resources\Artworks\ArtworkResource;
use App\Models\Artist;
use App\Models\Artwork;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/** Indicadores del artista (§15): sus obras por estado y el estado de su perfil. */
class MyArtworksOverview extends StatsOverviewWidget
{
    /** Son conteos baratos: se muestran al abrir el escritorio, sin «Cargando…». */
    protected static bool $isLazy = false;

    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $artist = auth()->user()->artist;
        $byStatus = $artist
            ? $artist->artworks()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status')
            : collect();
        $observed = $byStatus[Artwork::STATUS_OBSERVADO] ?? 0;

        return [
            Stat::make('Obras registradas', $byStatus->sum())
                ->url(ArtworkResource::getUrl('index')),
            Stat::make('En revisión', $byStatus[Artwork::STATUS_EN_REVISION] ?? 0)
                ->description('El equipo de ArtHub las está revisando'),
            Stat::make('Con observaciones', $observed)
                ->description($observed ? 'Corrígelas y vuelve a enviarlas' : 'Nada pendiente')
                ->color($observed ? 'warning' : 'gray'),
            Stat::make('Publicadas', $byStatus[Artwork::STATUS_PUBLICADO] ?? 0)
                ->description('Visibles en el catálogo')
                ->color('success'),
            Stat::make('Mi perfil', Artist::STATUS_LABELS[$artist?->status ?? Artist::STATUS_PENDIENTE])
                ->url(MiPerfil::getUrl()),
        ];
    }

    protected function getColumns(): int
    {
        return 5;
    }
}
