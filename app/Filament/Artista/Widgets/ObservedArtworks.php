<?php

namespace App\Filament\Artista\Widgets;

use App\Filament\Artista\Resources\Artworks\ArtworkResource;
use App\Models\Artwork;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

/** Obras del artista con observaciones abiertas, con la última nota del equipo. */
class ObservedArtworks extends TableWidget
{
    /** Son conteos baratos: se muestran al abrir el escritorio, sin «Cargando…». */
    protected static bool $isLazy = false;

    protected static ?int $sort = 2;

    protected static ?string $heading = 'Obras por corregir';

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(Artwork::query()
                ->where('artist_id', auth()->user()->artist?->id ?? 0)
                ->where('status', Artwork::STATUS_OBSERVADO))
            ->paginated(false)
            ->columns([
                TextColumn::make('title')->label('Obra')->description(fn (Artwork $record) => $record->code),
                TextColumn::make('note')
                    ->label('Observación')
                    ->getStateUsing(fn (Artwork $record) => $record->reviewNotes()->whereNull('resolved_at')->latest('id')->value('body'))
                    ->wrap(),
            ])
            ->recordActions([
                Action::make('fix')
                    ->label('Corregir')
                    ->url(fn (Artwork $record) => ArtworkResource::getUrl('edit', ['record' => $record])),
            ])
            ->emptyStateHeading('No tienes obras con observaciones');
    }
}
