<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Artworks\ArtworkResource;
use App\Models\Artwork;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

/** Cola de revisión: obras enviadas por los artistas, la más antigua primero. */
class ArtworksToReview extends TableWidget
{
    /** Son conteos baratos: se muestran al abrir el escritorio, sin «Cargando…». */
    protected static bool $isLazy = false;

    protected static ?int $sort = 2;

    protected static ?string $heading = 'Obras por revisar';

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(Artwork::query()->where('status', Artwork::STATUS_EN_REVISION))
            ->defaultSort('updated_at')
            ->paginated([5, 10])
            ->columns([
                TextColumn::make('title')->label('Obra')->description(fn (Artwork $record) => $record->code),
                TextColumn::make('artist.name')->label('Artista'),
                TextColumn::make('updated_at')->label('Enviada')->since(),
            ])
            ->recordActions([
                Action::make('review')
                    ->label('Revisar')
                    ->url(fn (Artwork $record) => ArtworkResource::getUrl('edit', ['record' => $record])),
            ])
            ->emptyStateHeading('No hay obras esperando revisión');
    }
}
