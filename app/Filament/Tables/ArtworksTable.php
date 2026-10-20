<?php

namespace App\Filament\Tables;

use App\Filament\Actions\ArtworkReviewActions;
use App\Filament\Schemas\ArtworkFields;
use App\Models\Artwork;
use App\Support\Disks;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/** Listado de obras para ambos paneles; el admin ve además el artista y puede filtrar por él. */
final class ArtworksTable
{
    public static function configure(Table $table, bool $forAdmin): Table
    {
        return $table
            ->defaultSort('updated_at', 'desc')
            ->modifyQueryUsing(fn ($query) => $query->withCount('documents'))
            ->columns([
                ImageColumn::make('thumbnail')
                    ->label('')
                    ->getStateUsing(fn (Artwork $record) => Disks::absoluteUrl($record->image_url))
                    ->imageHeight(48),
                TextColumn::make('title')
                    ->label('Obra')
                    ->description(fn (Artwork $record) => $record->code)
                    ->searchable(['title', 'code'])
                    ->sortable(),
                TextColumn::make('artist.name')
                    ->label('Artista')
                    ->searchable()
                    ->sortable()
                    ->visible($forAdmin),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => Artwork::STATUS_LABELS[$state] ?? $state)
                    ->color(fn (string $state) => ArtworkFields::statusColor($state))
                    ->sortable(),
                TextColumn::make('availability')
                    ->label('Disponibilidad')
                    ->formatStateUsing(fn (string $state) => Artwork::AVAILABILITY_LABELS[$state] ?? $state),
                TextColumn::make('images_count')
                    ->label('Imágenes')
                    ->getStateUsing(fn (Artwork $record) => $record->images->count())
                    ->alignCenter(),
                TextColumn::make('documents_count')
                    ->label('Documentos')
                    ->alignCenter(),
                TextColumn::make('updated_at')
                    ->label('Actualizada')
                    ->since()
                    ->sortable(),
            ])
            ->filters(array_values(array_filter([
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options(Artwork::STATUS_LABELS),
                SelectFilter::make('availability')
                    ->label('Disponibilidad')
                    ->options(Artwork::AVAILABILITY_LABELS),
                $forAdmin
                    ? SelectFilter::make('artist_id')->label('Artista')->relationship('artist', 'name')->searchable()->preload()
                    : null,
            ])))
            ->recordActions($forAdmin
                ? [EditAction::make(), ActionGroup::make(ArtworkReviewActions::forAdmin())->label('Revisar')->button()]
                // El artista edita borradores y observadas; las demás solo las ve.
                : [EditAction::make(), ViewAction::make()->visible(fn (Artwork $record) => ! auth()->user()->can('update', $record))])
            ->emptyStateHeading('Todavía no hay obras')
            ->emptyStateDescription($forAdmin ? null : 'Registra tu primera obra con «Nueva obra».');
    }
}
