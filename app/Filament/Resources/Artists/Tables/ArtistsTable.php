<?php

namespace App\Filament\Resources\Artists\Tables;

use App\Filament\Resources\Artists\ReviewActions;
use App\Filament\Resources\Artists\Schemas\ArtistStatus;
use App\Models\Artist;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ArtistsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('updated_at', 'desc')
            ->modifyQueryUsing(fn ($query) => $query->with('user')->withCount('artworks'))
            ->columns([
                ImageColumn::make('photo')
                    ->label('')
                    ->circular()
                    ->getStateUsing(fn (Artist $record) => self::photoForPanel($record)),
                TextColumn::make('name')
                    ->label('Artista')
                    ->description(fn (Artist $record) => $record->artistic_name)
                    ->searchable(['name', 'artistic_name'])
                    ->sortable(),
                TextColumn::make('user.email')
                    ->label('Cuenta')
                    ->placeholder('Sin cuenta')
                    ->toggleable(),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => Artist::STATUS_LABELS[$state] ?? $state)
                    ->color(fn (string $state) => ArtistStatus::color($state))
                    ->sortable(),
                TextColumn::make('artworks_count')
                    ->label('Obras')
                    ->numeric()
                    ->sortable(),
                IconColumn::make('featured')
                    ->label('Destacado')
                    ->boolean()
                    ->toggleable(),
                TextColumn::make('updated_at')
                    ->label('Actualizado')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options(Artist::STATUS_LABELS),
            ])
            ->recordActions([
                EditAction::make(),
                ActionGroup::make(ReviewActions::all())
                    ->label('Revisar')
                    ->button(),
            ]);
    }

    /** Las fotos antiguas son rutas del frontend (/artistas/…); en el panel se piden a ese dominio. */
    private static function photoForPanel(Artist $record): ?string
    {
        $url = $record->photo_url;

        return $url && str_starts_with($url, '/') && ! str_starts_with($url, '//')
            && ! str_starts_with($url, '/storage/')
            ? config('app.frontend_url').$url
            : $url;
    }
}
